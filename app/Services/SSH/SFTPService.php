<?php

namespace App\Services\SSH;

use App\Models\Connection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;
use RuntimeException;
use Throwable;

class SFTPService
{
    private const TIMEOUT = 15;

    // phpseclib3 SFTP type constants
    private const TYPE_REGULAR   = 1;
    private const TYPE_DIRECTORY = 2;
    private const TYPE_SYMLINK   = 3;

    public function listDirectory(Connection $connection, string $path): array
    {
        $sftp = $this->sftp($connection);
        $raw  = $sftp->rawlist($path);

        // Plain SFTP is denied by directories the login user can't read (e.g. root-owned
        // dirs on a box without a passwordless-sudo setup) — retry once, elevated.
        if ($raw === false) {
            return $this->listDirectoryElevated($connection, $path);
        }

        $entries = [];

        foreach ($raw as $name => $attrs) {
            if ($name === '.' || $name === '..') {
                continue;
            }

            $isDir = $this->isDirectory($attrs);
            $ext   = $isDir ? null : strtolower(pathinfo($name, PATHINFO_EXTENSION));

            $entries[] = [
                'name'        => $name,
                'type'        => $isDir ? 'dir' : 'file',
                'size'        => $attrs['size'] ?? 0,
                'modified'    => $attrs['mtime'] ?? 0,
                'permissions' => $this->formatPermissions($attrs['permissions'] ?? 0),
                'extension'   => $ext,
            ];
        }

        $this->sortEntries($entries);

        return $entries;
    }

    public function readFile(Connection $connection, string $path): array
    {
        $sftp = $this->sftp($connection);
        $stat = $sftp->stat($path);

        $content = $stat ? $sftp->get($path) : false;

        if (! $stat || $content === false) {
            return $this->finishReadFile($this->readFileElevated($connection, $path));
        }

        return $this->finishReadFile([
            'content'  => $content,
            'size'     => $stat['size'] ?? 0,
            'modified' => $stat['mtime'] ?? 0,
        ]);
    }

    private function finishReadFile(array $file): array
    {
        $content = $file['content'];

        // Reject binary files
        if (str_contains(substr($content, 0, 8000), "\x00")) {
            throw new RuntimeException("Arquivo binário não pode ser aberto no editor.");
        }

        if (! mb_check_encoding($content, 'UTF-8')) {
            $file['content'] = mb_convert_encoding($content, 'UTF-8', 'auto');
        }

        return $file;
    }

    /**
     * Fallback for directories the login user can't read via plain SFTP — re-lists via
     * an elevated `find`, authenticating sudo with the connection's stored password
     * when it has one (see sudoExec()). Machine-readable output (tab-separated, one
     * find -printf directive per column) instead of parsing `ls -la` text.
     */
    private function listDirectoryElevated(Connection $connection, string $path): array
    {
        $ssh = $this->ssh($connection);
        $ssh->setTimeout(15);

        $safePath = $this->posixQuote($path);
        $inner = "find {$safePath} -mindepth 1 -maxdepth 1 -printf '%f\t%y\t%s\t%T@\t%m\n' 2>/dev/null; echo \"__LP_EXIT__$?\"";

        $output = $this->sudoExec($ssh, $connection, $inner);
        $ssh->disconnect();

        if (! preg_match('/__LP_EXIT__(\d+)\s*$/', trim($output), $m) || $m[1] !== '0') {
            throw new RuntimeException("Não foi possível listar '{$path}'. " . $this->sudoErrorDetail($output));
        }

        $body = preg_replace('/__LP_EXIT__\d+\s*$/', '', $output);

        $entries = [];

        foreach (explode("\n", $body) as $line) {
            $line = rtrim($line, "\r");
            if ($line === '') {
                continue;
            }

            $parts = explode("\t", $line);
            if (count($parts) < 5) {
                continue;
            }

            [$name, $type, $size, $mtime, $mode] = $parts;
            $isDir = $type === 'd';

            $entries[] = [
                'name'        => $name,
                'type'        => $isDir ? 'dir' : 'file',
                'size'        => (int) $size,
                'modified'    => (int) (float) $mtime,
                'permissions' => $this->permsFromOctal($mode, $isDir),
                'extension'   => $isDir ? null : strtolower(pathinfo($name, PATHINFO_EXTENSION)),
            ];
        }

        $this->sortEntries($entries);

        return $entries;
    }

    /**
     * Fallback for files the login user can't read via plain SFTP — fetches
     * size/mtime and content (base64-encoded over the wire, so binary-safe and
     * immune to marker-collision issues a text-based transfer would risk)
     * through an elevated shell instead.
     */
    private function readFileElevated(Connection $connection, string $path): array
    {
        $ssh = $this->ssh($connection);
        $ssh->setTimeout(15);
        $safePath = $this->posixQuote($path);

        // One exec() call for both stat and content, rather than two — "__LP_SEP__"
        // can't collide with the base64 blob after it (base64's alphabet has no
        // underscores), so splitting on it is unambiguous.
        $output = $this->sudoExec($ssh, $connection,
            "stat -c '%s %Y' {$safePath} 2>/dev/null; echo '__LP_SEP__'; base64 {$safePath} 2>/dev/null"
        );
        $ssh->disconnect();

        [$statPart, $b64Part] = array_pad(explode('__LP_SEP__', $output, 2), 2, '');
        $stat = array_pad(array_map('trim', explode(' ', trim($statPart))), 2, null);

        if (! is_numeric($stat[0])) {
            throw new RuntimeException("Arquivo não encontrado ou sem permissão: {$path}. " . $this->sudoErrorDetail($statPart));
        }

        $content = $this->decodeSudoBase64($b64Part);

        if ($content === false) {
            throw new RuntimeException("Sem permissão para ler: {$path}. " . $this->sudoErrorDetail($b64Part));
        }

        return [
            'content'  => $content,
            'size'     => (int) $stat[0],
            'modified' => (int) $stat[1],
        ];
    }

    public function writeFile(Connection $connection, string $path, string $content): void
    {
        // Stage in /tmp where the SSH user always has write access
        $tempPath = '/tmp/.lp_' . bin2hex(random_bytes(6));

        $sftp = $this->sftp($connection);
        if (! $sftp->put($tempPath, $content)) {
            throw new RuntimeException('Não foi possível criar arquivo temporário em /tmp.');
        }

        // Copy to final destination with sudo (handles root-owned files)
        $ssh = $this->ssh($connection);
        $ssh->setTimeout(15);

        $safeTmp  = $this->posixQuote($tempPath);
        $safeDest = $this->posixQuote($path);

        $output = $this->sudoExec($ssh, $connection,
            "if cat {$safeTmp} > {$safeDest}; then echo '__LP_OK__'; else echo '__LP_ERR__'; fi; rm -f {$safeTmp}"
        );

        $ssh->disconnect();

        if (! str_contains($output, '__LP_OK__')) {
            throw new RuntimeException("Não foi possível salvar '{$path}'. " . $this->sudoErrorDetail($output));
        }
    }

    public function searchFiles(Connection $connection, string $basePath, string $query): array
    {
        $ssh = $this->ssh($connection);
        $ssh->setTimeout(20);

        $safeBase  = $this->posixQuote($basePath);
        $safeName  = $this->posixQuote('*' . $query . '*');
        $cmd       = "find {$safeBase} -name {$safeName} -not -path '*/\\.git/*' -type f 2>/dev/null | head -100";

        $output = $ssh->exec($cmd);
        $ssh->disconnect();

        $results = [];

        foreach (array_filter(array_map('trim', explode("\n", $output))) as $filePath) {
            $results[] = [
                'path'      => $filePath,
                'name'      => basename($filePath),
                'directory' => dirname($filePath),
                'extension' => strtolower(pathinfo($filePath, PATHINFO_EXTENSION)),
            ];
        }

        return $results;
    }


    public function copyFile(Connection $connection, string $from, string $to): void
    {
        $ssh = $this->ssh($connection);
        $ssh->setTimeout(15);
        $output = $this->sudoExec($ssh, $connection, 'cp -r ' . $this->posixQuote($from) . ' ' . $this->posixQuote($to) . ' && echo "__LP_OK__"');
        $ssh->disconnect();

        if (! str_contains($output, '__LP_OK__')) {
            throw new RuntimeException("Não foi possível copiar '{$from}'. " . $this->sudoErrorDetail($output));
        }
    }

    public function createFile(Connection $connection, string $path): void
    {
        $ssh = $this->ssh($connection);
        $ssh->setTimeout(10);
        $output = $this->sudoExec($ssh, $connection, 'touch ' . $this->posixQuote($path) . ' && echo "__LP_OK__"');
        $ssh->disconnect();

        if (! str_contains($output, '__LP_OK__')) {
            throw new RuntimeException("Não foi possível criar '{$path}'. " . $this->sudoErrorDetail($output));
        }
    }

    public function createDirectory(Connection $connection, string $path): void
    {
        $ssh = $this->ssh($connection);
        $ssh->setTimeout(10);
        $output = $this->sudoExec($ssh, $connection, 'mkdir -p ' . $this->posixQuote($path) . ' && echo "__LP_OK__"');
        $ssh->disconnect();

        if (! str_contains($output, '__LP_OK__')) {
            throw new RuntimeException("Não foi possível criar o diretório '{$path}'. " . $this->sudoErrorDetail($output));
        }
    }

    public function rename(Connection $connection, string $from, string $to): void
    {
        $ssh = $this->ssh($connection);
        $ssh->setTimeout(10);
        $output = $this->sudoExec($ssh, $connection, 'mv ' . $this->posixQuote($from) . ' ' . $this->posixQuote($to) . ' && echo "__LP_OK__"');
        $ssh->disconnect();

        if (! str_contains($output, '__LP_OK__')) {
            throw new RuntimeException("Não foi possível renomear. " . $this->sudoErrorDetail($output));
        }
    }

    public function delete(Connection $connection, string $path): void
    {
        $ssh = $this->ssh($connection);
        $ssh->setTimeout(15);
        $output = $this->sudoExec($ssh, $connection, 'rm -rf ' . $this->posixQuote($path) . ' && echo "__LP_OK__"');
        $ssh->disconnect();

        if (! str_contains($output, '__LP_OK__')) {
            throw new RuntimeException("Não foi possível remover '{$path}'. " . $this->sudoErrorDetail($output));
        }
    }

    private function sftp(Connection $connection): SFTP
    {
        $sftp = new SFTP($connection->host, $connection->port, self::TIMEOUT);

        if (! $sftp->login($connection->username, $this->loadCredential($connection))) {
            throw new RuntimeException('Falha na autenticação SFTP. Verifique usuário e senha/chave SSH.');
        }

        return $sftp;
    }

    private function ssh(Connection $connection): SSH2
    {
        $ssh = new SSH2($connection->host, $connection->port, self::TIMEOUT);

        if (! $ssh->login($connection->username, $this->loadCredential($connection))) {
            throw new RuntimeException('Falha na autenticação SSH.');
        }

        return $ssh;
    }

    /**
     * Runs a single command as root over a plain exec channel. Authenticates `sudo`
     * with the connection's own stored password when it has one (password-auth
     * connections) — feeding it via stdin through `sudo -S`, never as an argument —
     * which also fixes the create/delete/rename/etc. operations below for any server
     * whose sudoers actually requires a password instead of NOPASSWD. When there's no
     * stored password (key-auth connections), this degrades to the previous
     * NOPASSWD-only behaviour: `sudo -S` reads an empty stdin, which is a no-op
     * whenever sudo doesn't need a password and fails clearly when it does.
     *
     * Deliberately does NOT request a PTY: phpseclib3's exec() stops collecting
     * output the moment a PTY is requested (it switches to a fire-and-forget mode
     * meant for interactive read()/write() use, returning bool(true) instead of the
     * command's output) — incompatible with the simple "run it, get the string
     * back" usage every caller here relies on.
     */
    private function sudoExec(SSH2 $ssh, Connection $connection, string $command): string
    {
        $password = $connection->auth_type === 'password' ? (string) $connection->password : '';
        $stdin    = base64_encode($password . "\n");

        $output = $ssh->exec(sprintf(
            'echo %s | base64 -d | sudo -S -p %s -- bash -c %s',
            $this->posixQuote($stdin),
            $this->posixQuote(''),
            $this->posixQuote($command),
        ));

        $stderr = $ssh->getStdError();
        if ($stderr !== '') {
            Log::warning('SFTPService: sudo stderr', [
                'host' => $connection->host,
                'stderr' => $stderr,
            ]);
        }

        return $output;
    }

    /**
     * PHP's escapeshellarg() quotes according to the LOCAL OS running this process
     * (cmd.exe-style double quotes on Windows), but the command it builds always
     * targets a REMOTE POSIX shell — so on a Windows dev machine it silently
     * produces syntax bash never intended (nested "$?" pre-expanding a layer too
     * early, "--" boundaries drifting, etc.). This is the standard OS-independent
     * POSIX single-quote escape instead: close the quote, emit a literal quote,
     * reopen it, for every embedded ' — safe for any byte sequence.
     */
    private function posixQuote(string $arg): string
    {
        return "'" . str_replace("'", "'\\''", $arg) . "'";
    }

    /**
     * Surfaces sudo's own explanation (e.g. "claude is not in the sudoers file",
     * a bad-password rejection, etc.) instead of a dead-end generic message —
     * strips our own __LP_* markers first so only sudo/shell output is shown.
     */
    private function sudoErrorDetail(string $output): string
    {
        $clean = trim(preg_replace('/__LP_[A-Z]+__\d*/', '', $output));

        return $clean !== '' ? "Detalhe: {$clean}" : 'Verifique permissões.';
    }

    /**
     * Strict base64_decode, but tolerant of whitespace — a PTY-backed exec channel
     * (see sudoExec()) can translate the base64 output's line endings to CRLF, and
     * strict decoding otherwise rejects the embedded \r as an invalid character.
     */
    private function decodeSudoBase64(string $raw): string|false
    {
        return base64_decode(preg_replace('/\s+/', '', $raw), true);
    }

    private function loadCredential(Connection $connection): mixed
    {
        if ($connection->auth_type === 'password') {
            if (empty($connection->password)) {
                throw new RuntimeException('Nenhuma senha configurada para esta conexão.');
            }

            return $connection->password;
        }

        return $this->loadKey($connection);
    }

    private function loadKey(Connection $connection): mixed
    {
        if (empty($connection->ssh_key_path) || ! Storage::exists($connection->ssh_key_path)) {
            throw new RuntimeException('Chave SSH não encontrada. Recadastre a conexão.');
        }

        try {
            return PublicKeyLoader::load(Storage::get($connection->ssh_key_path));
        } catch (Throwable) {
            throw new RuntimeException('Chave SSH inválida ou corrompida.');
        }
    }

    private function isDirectory(array $attrs): bool
    {
        if (isset($attrs['type'])) {
            return $attrs['type'] === self::TYPE_DIRECTORY;
        }

        // Fallback: check permission bits (040000 = directory)
        return isset($attrs['permissions']) && ($attrs['permissions'] & 0170000) === 0040000;
    }

    private function formatPermissions(int $mode): string
    {
        $perms = '';
        $perms .= ($mode & 0040000) ? 'd' : (($mode & 0120000) === 0120000 ? 'l' : '-');
        $perms .= ($mode & 00400) ? 'r' : '-';
        $perms .= ($mode & 00200) ? 'w' : '-';
        $perms .= ($mode & 00100) ? 'x' : '-';
        $perms .= ($mode & 00040) ? 'r' : '-';
        $perms .= ($mode & 00020) ? 'w' : '-';
        $perms .= ($mode & 00010) ? 'x' : '-';
        $perms .= ($mode & 00004) ? 'r' : '-';
        $perms .= ($mode & 00002) ? 'w' : '-';
        $perms .= ($mode & 00001) ? 'x' : '-';

        return $perms;
    }

    /** Same as formatPermissions(), but from find's `%m` (octal perm bits only, no type bits). */
    private function permsFromOctal(string $octal, bool $isDir): string
    {
        $bits = str_split(str_pad(substr($octal, -3), 3, '0', STR_PAD_LEFT));
        $map  = ['---', '--x', '-w-', '-wx', 'r--', 'r-x', 'rw-', 'rwx'];

        $perms = $isDir ? 'd' : '-';
        foreach ($bits as $digit) {
            $perms .= $map[(int) $digit] ?? '---';
        }

        return $perms;
    }

    private function sortEntries(array &$entries): void
    {
        usort($entries, function (array $a, array $b): int {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'dir' ? -1 : 1;
            }

            // Hidden files (dotfiles) go after regular
            $aHidden = str_starts_with($a['name'], '.');
            $bHidden = str_starts_with($b['name'], '.');
            if ($aHidden !== $bHidden) {
                return $aHidden ? 1 : -1;
            }

            return strcasecmp($a['name'], $b['name']);
        });
    }

    private function humanSize(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024) {
                return round($bytes, 1) . ' ' . $unit;
            }
            $bytes /= 1024;
        }

        return round($bytes, 1) . ' TB';
    }
}
