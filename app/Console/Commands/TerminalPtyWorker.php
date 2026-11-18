<?php

namespace App\Console\Commands;

use App\Models\Connection;
use App\Services\Terminal\TerminalPtyService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Spawned as a child process by `terminal:serve`, one per open terminal tab.
 * Never invoked directly by a user — the connection/user ids come from the
 * WS server after it validates the tab's one-time auth token.
 *
 * Talks to `terminal:serve` over a loopback TCP socket, NOT over its
 * STDIN/STDOUT pipes: on Windows PHP can't do non-blocking reads on a pipe
 * (stream_select()/stream_set_blocking() don't work on them), so a loop
 * waiting for keystrokes on STDIN blocked and SSH output only came through
 * when the next key arrived. Sockets are pollable on every OS.
 *
 * Handshake: listen on 127.0.0.1:<random port>, print "\x00PORT <port>\n" on
 * STDOUT, accept exactly one connection, and require it to send the per-session
 * secret (passed by the parent in the LP_TERMINAL_SECRET env var) as its first
 * line — so no other local process can hijack the shell.
 */
class TerminalPtyWorker extends Command
{
    protected $signature = 'terminal:pty-worker {connectionId} {userId} {--cols=80} {--rows=24} {--cwd=}';

    protected $description = 'Processo interno: mantém uma sessão SSH interativa (PTY) para uma aba de terminal.';

    private const ACCEPT_TIMEOUT = 15;

    public function handle(TerminalPtyService $service): int
    {
        $secret = (string) getenv('LP_TERMINAL_SECRET');
        if (strlen($secret) < 32) {
            $this->stdoutControl('ERROR Worker iniciado sem segredo de sessão.');

            return self::FAILURE;
        }

        $server = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if (! $server) {
            $this->stdoutControl("ERROR Não foi possível abrir o canal interno: {$errstr}");

            return self::FAILURE;
        }

        $port = (int) substr(strrchr(stream_socket_get_name($server, false), ':'), 1);
        $this->stdoutControl("PORT {$port}");

        $socket = @stream_socket_accept($server, self::ACCEPT_TIMEOUT);
        fclose($server);

        if (! $socket || ! $this->handshake($socket, $secret)) {
            return self::FAILURE;
        }

        $connection = Connection::withoutGlobalScopes()
            ->where('id', (int) $this->argument('connectionId'))
            ->where('user_id', (int) $this->argument('userId'))
            ->first();

        if (! $connection) {
            fwrite($socket, "\x00ERROR Conexão não encontrada.\n");
            fclose($socket);

            return self::FAILURE;
        }

        try {
            $service->run(
                $connection,
                $socket,
                (int) $this->option('cols'),
                (int) $this->option('rows'),
                $this->option('cwd'),
            );
        } catch (Throwable $e) {
            @fwrite($socket, "\x00ERROR " . $e->getMessage() . "\n");
            @fclose($socket);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /** First line from the parent must be the session secret. */
    private function handshake($socket, string $secret): bool
    {
        stream_set_timeout($socket, 5);
        $line = fgets($socket, 256);

        if ($line === false || ! hash_equals($secret, rtrim($line, "\r\n"))) {
            fclose($socket);

            return false;
        }

        return true;
    }

    private function stdoutControl(string $message): void
    {
        fwrite(STDOUT, "\x00{$message}\n");
        fflush(STDOUT);
    }
}
