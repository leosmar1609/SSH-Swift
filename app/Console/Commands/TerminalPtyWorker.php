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
 */
class TerminalPtyWorker extends Command
{
    protected $signature = 'terminal:pty-worker {connectionId} {userId} {--cols=80} {--rows=24} {--cwd=}';

    protected $description = 'Processo interno: mantém uma sessão SSH interativa (PTY) para uma aba de terminal.';

    public function handle(TerminalPtyService $service): int
    {
        $connection = Connection::withoutGlobalScopes()
            ->where('id', (int) $this->argument('connectionId'))
            ->where('user_id', (int) $this->argument('userId'))
            ->first();

        if (! $connection) {
            fwrite(STDOUT, "\x00ERROR Conexão não encontrada.\n");

            return self::FAILURE;
        }

        try {
            $service->run(
                $connection,
                (int) $this->option('cols'),
                (int) $this->option('rows'),
                $this->option('cwd'),
            );
        } catch (Throwable $e) {
            fwrite(STDOUT, "\x00ERROR " . $e->getMessage() . "\n");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
