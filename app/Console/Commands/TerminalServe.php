<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Throwable;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Timer;
use Workerman\Worker;

/**
 * WebSocket gateway for interactive SSH terminal tabs.
 *
 * Browser <--WS(JSON, base64 bytes)--> this process <--pipes--> one
 * `terminal:pty-worker` child process per tab, which does the actual
 * phpseclib3 PTY session. This process never talks to SSH directly — it
 * only authenticates the tab (one-time token from the "Abrir Terminal"
 * button) and relays bytes between the browser and the right child process.
 *
 * Must be kept running as a long-lived process (locally: run in its own
 * terminal window; in production: managed by supervisor/systemd), same as
 * a queue worker.
 */
class TerminalServe extends Command
{
    protected $signature = 'terminal:serve {--port=}';

    protected $description = 'Sobe o servidor WebSocket de terminais SSH interativos (PTY).';

    /** @var array<int, array{connection: TcpConnection, process: Process, input: InputStream, gotOutput: bool}> */
    private array $sessions = [];

    public function handle(): int
    {
        $port = (int) ($this->option('port') ?: config('terminal.ws_port'));

        $worker = new Worker("websocket://0.0.0.0:{$port}");
        $worker->count = 1;
        $worker->name = 'leopanel-terminal';

        $worker->onWebSocketConnect = function (TcpConnection $connection, Request $request) {
            $this->authenticate($connection, $request);
        };

        $worker->onWebSocketConnected = function (TcpConnection $connection) {
            $this->startSession($connection);
        };

        $worker->onMessage = function (TcpConnection $connection, string $data) {
            $this->handleMessage($connection, $data);
        };

        $worker->onClose = function (TcpConnection $connection) {
            $this->endSession($connection->id);
        };

        $worker->onWorkerStart = function () {
            Timer::add(0.01, function () {
                $this->pumpSessions();
            });
        };

        $this->info("Terminal WS server ouvindo na porta {$port}...");

        Worker::runAll();

        return self::SUCCESS;
    }

    private function authenticate(TcpConnection $connection, Request $request): void
    {
        $token = (string) $request->get('token', '');
        $payload = $token !== '' ? Cache::pull("terminal_token:{$token}") : null;

        if (! is_array($payload)) {
            $connection->terminalAuthError = 'Sessão inválida ou expirada. Feche esta aba e abra o terminal novamente.';

            return;
        }

        $connection->terminalUserId = (int) $payload['user_id'];
        $connection->terminalConnectionId = (int) $payload['connection_id'];
        $connection->terminalCwd = $payload['cwd'] ?? null;
        $connection->terminalCols = max(10, (int) $request->get('cols', 80));
        $connection->terminalRows = max(5, (int) $request->get('rows', 24));
    }

    private function startSession(TcpConnection $connection): void
    {
        if (isset($connection->terminalAuthError)) {
            $this->sendJson($connection, ['type' => 'error', 'message' => $connection->terminalAuthError]);
            $connection->close();

            return;
        }

        $command = [
            PHP_BINARY,
            base_path('artisan'),
            'terminal:pty-worker',
            (string) $connection->terminalConnectionId,
            (string) $connection->terminalUserId,
            '--cols=' . $connection->terminalCols,
            '--rows=' . $connection->terminalRows,
        ];

        if (! empty($connection->terminalCwd)) {
            $command[] = '--cwd=' . $connection->terminalCwd;
        }

        $input = new InputStream();
        $process = new Process($command, base_path());
        $process->setInput($input);
        $process->setTimeout(null);

        try {
            $process->start();
        } catch (Throwable $e) {
            Log::error('terminal:serve — falha ao iniciar pty-worker: ' . $e->getMessage());
            $this->sendJson($connection, ['type' => 'error', 'message' => 'Não foi possível iniciar a sessão de terminal.']);
            $connection->close();

            return;
        }

        $this->sessions[$connection->id] = [
            'connection' => $connection,
            'process' => $process,
            'input' => $input,
            'gotOutput' => false,
        ];
    }

    private function handleMessage(TcpConnection $connection, string $data): void
    {
        if (! isset($this->sessions[$connection->id])) {
            return;
        }

        $msg = json_decode($data, true);
        if (! is_array($msg) || ! isset($msg['type'])) {
            return;
        }

        $input = $this->sessions[$connection->id]['input'];

        if ($msg['type'] === 'input' && isset($msg['data'])) {
            $bytes = base64_decode((string) $msg['data'], true);
            if ($bytes !== false && $bytes !== '') {
                $input->write($bytes);
            }

            return;
        }

        if ($msg['type'] === 'resize') {
            $cols = max(10, (int) ($msg['cols'] ?? 80));
            $rows = max(5, (int) ($msg['rows'] ?? 24));
            $input->write("\x00RESIZE {$cols} {$rows}\n");
        }
    }

    private function pumpSessions(): void
    {
        foreach ($this->sessions as $id => $session) {
            $process = $session['process'];

            $out = $process->getIncrementalOutput();
            $err = $process->getIncrementalErrorOutput();

            if ($err !== '') {
                Log::warning("terminal:serve — pty-worker#{$id} stderr: {$err}");
            }

            if ($out !== '') {
                if (! $session['gotOutput'] && $out[0] === "\x00") {
                    // First bytes ever from this worker and it's a control frame -> startup failure.
                    $message = trim(preg_replace('/^\x00ERROR\s?/', '', $out));
                    $this->sendJson($session['connection'], ['type' => 'error', 'message' => $message !== '' ? $message : 'Falha ao iniciar o terminal.']);
                } else {
                    $this->sendJson($session['connection'], ['type' => 'output', 'data' => base64_encode($out)]);
                }

                $this->sessions[$id]['gotOutput'] = true;
            }

            if (! $process->isRunning()) {
                $this->sendJson($session['connection'], ['type' => 'closed']);
                $session['connection']->close();
                $this->endSession($id);
            }
        }
    }

    private function endSession(int $id): void
    {
        if (! isset($this->sessions[$id])) {
            return;
        }

        $session = $this->sessions[$id];

        try {
            $session['input']->close();
            if ($session['process']->isRunning()) {
                $session['process']->stop(2);
            }
        } catch (Throwable) {
            // process already gone — nothing to clean up
        }

        unset($this->sessions[$id]);
    }

    private function sendJson(TcpConnection $connection, array $payload): void
    {
        $connection->send(json_encode($payload));
    }
}
