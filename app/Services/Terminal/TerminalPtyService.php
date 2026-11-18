<?php

namespace App\Services\Terminal;

use App\Models\Connection;
use App\Services\SSH\SshKeyLoader;
use phpseclib3\Net\SSH2;
use RuntimeException;
use Throwable;

/**
 * Runs as the body of the `terminal:pty-worker` artisan command — one process
 * per open terminal tab. Talks phpseclib3 to the remote host over an
 * interactive PTY shell, and relays raw bytes over a loopback socket to the
 * `terminal:serve` Workerman server (see TerminalPtyWorker for why a socket).
 *
 * Protocol on the socket: raw terminal bytes, except a line starting with
 * "\x00", which is a control line — from the parent "\x00RESIZE <cols> <rows>\n",
 * to the parent "\x00ERROR <message>\n". NUL never occurs in normal keystrokes,
 * so it's a safe sentinel for this internal, single-hop channel.
 */
class TerminalPtyService
{
    /** How long one SSH read waits for output; also the worst-case keystroke latency. */
    private const POLL_SECONDS = 0.02;

    /** Keeps NAT/firewalls between us and the server from dropping an idle session. */
    private const SSH_KEEPALIVE_SECONDS = 30;

    private string $inputBuffer = '';

    /** @param resource $socket */
    public function run(Connection $connection, $socket, int $cols, int $rows, ?string $cwd = null): void
    {
        stream_set_blocking($socket, false);

        $ssh = new ResizableSSH2($connection->host, $connection->port, 10);
        $ssh->setTerminal('xterm-256color');
        $ssh->setWindowSize(max(10, $cols), max(5, $rows));
        $ssh->setKeepAlive(self::SSH_KEEPALIVE_SECONDS);

        try {
            $credential = $connection->auth_type === 'password'
                ? $this->loadPassword($connection)
                : $this->loadKey($connection);
        } catch (Throwable $e) {
            $this->sendControl($socket, 'ERROR ' . $e->getMessage());

            return;
        }

        try {
            $loggedIn = $ssh->login($connection->username, $credential);
        } catch (Throwable $e) {
            $this->sendControl($socket, 'ERROR Não foi possível conectar em ' . $connection->host . ':' . $connection->port . ' — ' . $e->getMessage());

            return;
        }

        if (! $loggedIn) {
            $this->sendControl($socket, 'ERROR Falha na autenticação SSH.');

            return;
        }

        try {
            $ssh->openShell();
        } catch (Throwable $e) {
            $this->sendControl($socket, 'ERROR Não foi possível abrir o shell: ' . $e->getMessage());

            return;
        }

        foreach ($connection->startup_script_lines as $line) {
            $this->writeToShell($ssh, $line . "\n");
        }

        if (! empty($cwd)) {
            $this->writeToShell($ssh, 'cd ' . escapeshellarg($cwd) . "\n");
        }

        $ssh->setTimeout(self::POLL_SECONDS);

        try {
            $this->loop($ssh, $socket);
        } finally {
            try {
                $ssh->disconnect();
            } catch (Throwable) {
                // already gone
            }
            @fclose($socket);
        }
    }

    /** @param resource $socket */
    private function loop(ResizableSSH2 $ssh, $socket): void
    {
        while (true) {
            // 1) Keystrokes / resizes from the browser (never blocks).
            $read = [$socket];
            $write = $except = null;

            if (@stream_select($read, $write, $except, 0, 0) > 0) {
                $chunk = fread($socket, 65536);

                if ($chunk === false || ($chunk === '' && feof($socket))) {
                    return; // tab closed
                }

                if ($chunk !== '') {
                    $this->handleInput($ssh, $chunk);
                }
            }

            // 2) Output from the server (waits at most POLL_SECONDS).
            if (! $ssh->isConnected() || ! $ssh->isShellOpen()) {
                return; // `exit`, server closed the session, network died
            }

            try {
                $out = $ssh->read('', SSH2::READ_NEXT);
            } catch (Throwable) {
                if (! $ssh->isConnected()) {
                    return;
                }
                continue;
            }

            if (is_string($out) && $out !== '') {
                if (! $this->writeAll($socket, $out)) {
                    return; // parent went away
                }
            }
        }
    }

    private function handleInput(ResizableSSH2 $ssh, string $chunk): void
    {
        // A single fread() can coalesce a resize control line with keystrokes
        // that arrived right after it — buffer and only pull out complete
        // "\x00RESIZE <cols> <rows>\n" lines, forwarding everything else
        // (including whatever follows on the same read) untouched.
        $this->inputBuffer .= $chunk;

        while ($this->inputBuffer !== '') {
            $nul = strpos($this->inputBuffer, "\x00");

            if ($nul === false) {
                $this->writeToShell($ssh, $this->inputBuffer);
                $this->inputBuffer = '';

                return;
            }

            if ($nul > 0) {
                $this->writeToShell($ssh, substr($this->inputBuffer, 0, $nul));
                $this->inputBuffer = substr($this->inputBuffer, $nul);
            }

            $nl = strpos($this->inputBuffer, "\n");

            if ($nl === false) {
                return; // control line not fully buffered yet — wait for more
            }

            $line = substr($this->inputBuffer, 0, $nl);
            $this->inputBuffer = substr($this->inputBuffer, $nl + 1);

            if (preg_match('/^\x00RESIZE (\d+) (\d+)/', $line, $m)) {
                try {
                    $ssh->resizeTerminal(max(10, (int) $m[1]), max(5, (int) $m[2]));
                } catch (Throwable) {
                    // resize lost on a hiccup — not fatal
                }
            }
        }
    }

    private function writeToShell(ResizableSSH2 $ssh, string $bytes): void
    {
        // write() shares the same timeout as the fast-polling read() loop, which
        // is too tight for a reliable write (e.g. a big paste filling the SSH
        // window) — give it real breathing room, and don't let an occasional
        // slow write kill the whole session.
        $ssh->setTimeout(10);

        try {
            $ssh->write($bytes);
        } catch (Throwable) {
            // dropped keystrokes on a transient hiccup — not fatal
        } finally {
            $ssh->setTimeout(self::POLL_SECONDS);
        }
    }

    /**
     * Writes everything to the (non-blocking) socket. Waiting here while the
     * parent's buffers are full is the backpressure that keeps a flood of output
     * (`cat` of a big log) from piling up in memory: we stop reading SSH, so SSH
     * flow control slows the server down.
     *
     * @param resource $socket
     */
    private function writeAll($socket, string $data): bool
    {
        while ($data !== '') {
            $n = @fwrite($socket, $data);

            if ($n === false) {
                return false;
            }

            if ($n === 0) {
                $read = $except = null;
                $write = [$socket];
                if (@stream_select($read, $write, $except, 30) === 0) {
                    return false; // parent stuck for 30s — give up
                }
                if (feof($socket)) {
                    return false;
                }
                continue;
            }

            $data = substr($data, $n);
        }

        return true;
    }

    /** @param resource $socket */
    private function sendControl($socket, string $message): void
    {
        $this->writeAll($socket, "\x00" . $message . "\n");
    }

    private function loadPassword(Connection $connection): string
    {
        if (empty($connection->password)) {
            throw new RuntimeException('Nenhuma senha configurada para esta conexão.');
        }

        return $connection->password;
    }

    private function loadKey(Connection $connection): mixed
    {
        return SshKeyLoader::fromConnection($connection);
    }
}
