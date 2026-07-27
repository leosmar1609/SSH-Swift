<?php

namespace App\Services\SSH;

use App\Models\Connection;
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
        $sftp   = $this->sftp($connection);
        $raw    = $sftp->rawlist($path);

        if ($raw === false) {
            throw new RuntimeException("Não foi possível listar '{$path}'. Verifique permissões.");
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

        return $entries;
    }

    public function readFile(Connection $connection, string $path): array
    {
        $sftp = $this->sftp($connection);
        $stat = $sftp->stat($path);

        if (! $stat) {
            throw new RuntimeException("Arquivo não encontrado: {$path}");
        }

        $content = $sftp->get($path);

        if ($content === false) {
            throw new RuntimeException("Sem permissão para ler: {$path}");
        }

        // Reject binary files
        if (str_contains(substr($content, 0, 8000), "\x00")) {
            throw new RuntimeException("Arquivo binário não pode ser aberto no editor.");
        }

        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        }

        return [
            'content'  => $content,
            'size'     => $stat['size'] ?? 0,
            'modified' => $stat['mtime'] ?? 0,
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

        $safeTmp  = escapeshellarg($tempPath);
        $safeDest = escapeshellarg($path);

        $output = $ssh->exec(
            "if sudo bash -c \"cat {$safeTmp} > {$safeDest}\"; then echo '__LP_OK__'; else echo '__LP_ERR__'; fi; sudo rm -f {$safeTmp}"
        );

        $ssh->disconnect();

        if (! str_contains($output, '__LP_OK__')) {
            throw new RuntimeException("Não foi possível salvar '{$path}'. Verifique permissões.");
        }
    }

    public function searchFiles(Connection $connection, string $basePath, string $query): array
    {
        $ssh = $this->ssh($connection);
        $ssh->setTimeout(20);

        $safeBase  = escapeshellarg($basePath);
        $safeName  = escapeshellarg('*' . $query . '*');
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

    public function tailFile(Connection $connection, string $path, int $offset): array
    {
        $sftp = $this->sftp($connection);
        $size = $sftp->filesize($path);

        if ($size === false) {
            throw new RuntimeException("Arquivo não encontrado: {$path}");
        }

        // First load: start from last 200 KB so the viewer opens fast
        if ($offset === 0 && $size > 204800) {
            $offset = $size - 204800;
        }

        // File was rotated/truncated
        if ($offset > $size) {
            $offset = 0;
        }

        if ($offset >= $size) {
            return ['content' => '', 'size' => $size, 'offset' => $offset];
        }

        $content = $sftp->get($path, false, $offset, $size - $offset);

        if ($content === false) {
            throw new RuntimeException("Sem permissão para ler: {$path}");
        }

        return [
            'content' => $content,
            'size'    => $size,
            'offset'  => $offset + strlen($content),
        ];
    }

    public function copyFile(Connection $connection, string $from, string $to): void
    {
        $ssh = $this->ssh($connection);
        $ssh->setTimeout(15);
        $output = $ssh->exec('sudo cp -r ' . escapeshellarg($from) . ' ' . escapeshellarg($to) . ' && echo "__LP_OK__"');
        $ssh->disconnect();

        if (! str_contains($output, '__LP_OK__')) {
            throw new RuntimeException("Não foi possível copiar '{$from}'.");
        }
    }

    public function createFile(Connection $connection, string $path): void
    {
        $ssh = $this->ssh($connection);
        $ssh->setTimeout(10);
        $output = $ssh->exec('sudo touch ' . escapeshellarg($path) . ' && echo "__LP_OK__"');
        $ssh->disconnect();

        if (! str_contains($output, '__LP_OK__')) {
            throw new RuntimeException("Não foi possível criar '{$path}'.");
        }
    }

    public function createDirectory(Connection $connection, string $path): void
    {
        $ssh = $this->ssh($connection);
        $ssh->setTimeout(10);
        $output = $ssh->exec('sudo mkdir -p ' . escapeshellarg($path) . ' && echo "__LP_OK__"');
        $ssh->disconnect();

        if (! str_contains($output, '__LP_OK__')) {
            throw new RuntimeException("Não foi possível criar o diretório '{$path}'.");
        }
    }

    public function rename(Connection $connection, string $from, string $to): void
    {
        $ssh = $this->ssh($connection);
        $ssh->setTimeout(10);
        $output = $ssh->exec('sudo mv ' . escapeshellarg($from) . ' ' . escapeshellarg($to) . ' && echo "__LP_OK__"');
        $ssh->disconnect();

        if (! str_contains($output, '__LP_OK__')) {
            throw new RuntimeException("Não foi possível renomear.");
        }
    }

    public function delete(Connection $connection, string $path): void
    {
        $ssh = $this->ssh($connection);
        $ssh->setTimeout(15);
        $output = $ssh->exec('sudo rm -rf ' . escapeshellarg($path) . ' && echo "__LP_OK__"');
        $ssh->disconnect();

        if (! str_contains($output, '__LP_OK__')) {
            throw new RuntimeException("Não foi possível remover '{$path}'.");
        }
    }

    private function sftp(Connection $connection): SFTP
    {
        $sftp = new SFTP($connection->host, $connection->port, self::TIMEOUT);

        if (! $sftp->login($connection->username, $this->loadKey($connection))) {
            throw new RuntimeException('Falha na autenticação SFTP. Verifique usuário e chave SSH.');
        }

        return $sftp;
    }

    private function ssh(Connection $connection): SSH2
    {
        $ssh = new SSH2($connection->host, $connection->port, self::TIMEOUT);

        if (! $ssh->login($connection->username, $this->loadKey($connection))) {
            throw new RuntimeException('Falha na autenticação SSH.');
        }

        return $ssh;
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
