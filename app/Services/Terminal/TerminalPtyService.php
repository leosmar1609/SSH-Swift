<?php

namespace App\Services\Terminal;

use App\Models\Connection;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;
use RuntimeException;
use Throwable;

/**
 * Runs as the body of the `terminal:pty-worker` artisan command — one process
 * per open terminal tab. Talks phpseclib3 to the remote host over an
 * interactive PTY shell, and relays raw bytes over its own STDIN/STDOUT,
 * which the `terminal:serve` Workerman server pipes to/from the browser.
 *
 * Protocol on STDIN/STDOUT: raw terminal bytes, except a chunk starting with
 * "\x00" which is a control line from the parent ("\x00RESIZE <cols> <rows>\n")
 * or to the parent ("\x00ERROR <message>\n") — NUL never occurs in normal
 * keystrokes, so it's a safe-enough sentinel for this internal, single-hop pipe.
 */
class TerminalPtyService
{
    private string $inputBuffer = '';

    public function run(Connection $connection, int $cols, int $rows, ?string $cwd = null): void
    {
        stream_set_blocking(STDIN, false);

        $ssh = new ResizableSSH2($connection->host, $connection->port, 10);
        $ssh->setTerminal('xterm-256color');
        $ssh->setWindowSize($cols, $rows);

        try {
            $key = $this->loadKey($connection);
        } catch (Throwable $e) {
            $this->writeControl('ERROR ' . $e->getMessage());

            return;
        }

        if (! $ssh->login($connection->username, $key)) {
            $this->writeControl('ERROR Falha na autenticação SSH.');

            return;
        }

        try {
            $ssh->openShell();
        } catch (Throwable $e) {
            $this->writeControl('ERROR Não foi possível abrir o shell: ' . $e->getMessage());

            return;
        }

        foreach ($connection->startup_script_lines as $line) {
            $ssh->write($line . "\n");
        }

        if (! empty($cwd)) {
            $ssh->write('cd ' . escapeshellarg($cwd) . "\n");
        }

        $ssh->setTimeout(0.01);

        while (true) {
            $read = [STDIN];
            $write = $except = [];

            if (stream_select($read, $write, $except, 0, 5000) > 0) {
                $chunk = fread(STDIN, 65536);

                // An empty read only means EOF (parent closed our stdin) when feof()
                // agrees — stream_select() can report STDIN as "ready" with nothing
                // to actually read yet (notably on Windows), so that alone isn't EOF.
                if ($chunk === false || ($chunk === '' && feof(STDIN))) {
                    break;
                }

                if ($chunk !== '') {
                    $this->handleInput($ssh, $chunk);
                }
            }

            if (! $ssh->isConnected()) {
                break;
            }

            try {
                $out = $ssh->read('', SSH2::READ_NEXT);
            } catch (Throwable) {
                continue;
            }

            if ($out !== false && $out !== '') {
                fwrite(STDOUT, $out);
                fflush(STDOUT);
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
            if ($this->inputBuffer[0] !== "\x00") {
                $this->writeToShell($ssh, $this->inputBuffer);
                $this->inputBuffer = '';

                return;
            }

            $nl = strpos($this->inputBuffer, "\n");

            if ($nl === false) {
                return; // control line not fully buffered yet — wait for more
            }

            $line = substr($this->inputBuffer, 0, $nl);
            $this->inputBuffer = substr($this->inputBuffer, $nl + 1);

            if (preg_match('/^\x00RESIZE (\d+) (\d+)/', $line, $m)) {
                $ssh->resizeTerminal((int) $m[1], (int) $m[2]);
            }
        }
    }

    private function writeToShell(ResizableSSH2 $ssh, string $bytes): void
    {
        // write() shares the same timeout as the fast-polling read() loop (10ms),
        // which is too tight for a reliable write — give it real breathing room,
        // and don't let an occasional slow write kill the whole session.
        $ssh->setTimeout(5);

        try {
            $ssh->write($bytes);
        } catch (Throwable) {
            // dropped keystrokes on a transient hiccup — not fatal
        } finally {
            $ssh->setTimeout(0.01);
        }
    }

    private function writeControl(string $message): void
    {
        fwrite(STDOUT, "\x00" . $message . "\n");
        fflush(STDOUT);
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
}
