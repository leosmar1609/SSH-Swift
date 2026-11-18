<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Websocket;
use Workerman\Timer;
use Workerman\Worker;

/**
 * WebSocket gateway for interactive SSH terminal tabs.
 *
 *   Browser <--WS--> this process <--loopback TCP--> `terminal:pty-worker` (one per tab) <--SSH--> server
 *
 * This process never talks SSH itself (a slow login would freeze every other
 * tab): it authenticates the tab (one-time token from "Abrir Terminal"),
 * spawns the worker, and relays bytes. Everything here is event-driven —
 * nothing may block, since one process serves all tabs.
 *
 * Browser protocol:
 *   → text  {"type":"input","data":"<base64>"} | {"type":"resize","cols":N,"rows":N} | {"type":"ping"}
 *   ← binary  raw terminal output (UTF-8 bytes; xterm decodes them)
 *   ← text  {"type":"error","message":"…"} | {"type":"closed"} | {"type":"pong"}
 *
 * Must be kept running as a long-lived process (locally: its own terminal
 * window; in production: a service — NSSM on Windows, supervisor/systemd on Linux).
 */
class TerminalServe extends Command
{
    protected $signature = 'terminal:serve {--port=}';

    protected $description = 'Sobe o servidor WebSocket de terminais SSH interativos (PTY).';

    /** Worker must report its port within this time. */
    private const WORKER_START_TIMEOUT = 25;

    /** Grace period for a worker to exit on its own after its tab closed. */
    private const WORKER_EXIT_GRACE = 5;

    /** Keystrokes typed before the worker channel is up are buffered, up to this. */
    private const MAX_PENDING_INPUT = 65536;

    /**
     * @var array<int, array{
     *   ws: TcpConnection, process: Process, secret: string, ipc: ?AsyncTcpConnection,
     *   stdout: string, startedAt: float, gotOutput: bool, pending: string, closing: bool
     * }>
     */
    private array $sessions = [];

    /** @var array<int, array> auth data between the WS handshake and the "connected" event */
    private array $auth = [];

    public function handle(): int
    {
        $port = (int) ($this->option('port') ?: config('terminal.ws_port'));

        $worker = new Worker("websocket://0.0.0.0:{$port}");
        $worker->count = 1;
        $worker->name = 'techiapanel-terminal';

        $worker->onWebSocketConnect = function (TcpConnection $ws, Request $request) {
            $this->authenticate($ws, $request);
        };

        $worker->onWebSocketConnected = function (TcpConnection $ws) {
            $this->startSession($ws);
        };

        $worker->onMessage = function (TcpConnection $ws, string $data) {
            $this->handleMessage($ws, $data);
        };

        $worker->onClose = function (TcpConnection $ws) {
            unset($this->auth[$ws->id]);
            $this->endSession($ws->id);
        };

        $worker->onWorkerStart = function () {
            Timer::add(0.05, fn () => $this->pumpWorkers());
        };

        $this->info("Terminal WS server ouvindo na porta {$port}...");

        Worker::runAll();

        return self::SUCCESS;
    }

    // ── Browser side ──────────────────────────────────────────────────────

    private function authenticate(TcpConnection $ws, Request $request): void
    {
        $token = (string) $request->get('token', '');
        $payload = $token !== '' ? Cache::pull("terminal_token:{$token}") : null;

        if (! is_array($payload)) {
            $this->auth[$ws->id] = ['error' => 'Sessão inválida ou expirada. Feche esta aba e abra o terminal novamente.'];

            return;
        }

        // The web route already requires admin to issue a token; re-check here
        // so the WS gateway doesn't rely solely on that (and catches an admin
        // demoted in the token's few seconds of validity).
        if (! User::whereKey((int) $payload['user_id'])->where('is_admin', true)->exists()) {
            $this->auth[$ws->id] = ['error' => 'Acesso ao terminal restrito a administradores.'];

            return;
        }

        $this->auth[$ws->id] = [
            'user_id'       => (int) $payload['user_id'],
            'connection_id' => (int) $payload['connection_id'],
            'cwd'           => $payload['cwd'] ?? null,
            'cols'          => min(500, max(10, (int) $request->get('cols', 80))),
            'rows'          => min(200, max(5, (int) $request->get('rows', 24))),
        ];
    }

    private function startSession(TcpConnection $ws): void
    {
        $auth = $this->auth[$ws->id] ?? ['error' => 'Sessão inválida.'];
        unset($this->auth[$ws->id]);

        if (isset($auth['error'])) {
            $this->sendJson($ws, ['type' => 'error', 'message' => $auth['error']]);
            $ws->close();

            return;
        }

        $command = [
            PHP_BINARY,
            base_path('artisan'),
            'terminal:pty-worker',
            (string) $auth['connection_id'],
            (string) $auth['user_id'],
            '--cols=' . $auth['cols'],
            '--rows=' . $auth['rows'],
        ];

        if (! empty($auth['cwd'])) {
            $command[] = '--cwd=' . $auth['cwd'];
        }

        $secret = bin2hex(random_bytes(32));
        $process = new Process($command, base_path(), ['LP_TERMINAL_SECRET' => $secret]);
        $process->setTimeout(null);

        try {
            $process->start();
        } catch (Throwable $e) {
            Log::error('terminal:serve — falha ao iniciar pty-worker: ' . $e->getMessage());
            $this->sendJson($ws, ['type' => 'error', 'message' => 'Não foi possível iniciar a sessão de terminal.']);
            $ws->close();

            return;
        }

        $id = $ws->id;
        $this->sessions[$id] = [
            'ws' => $ws, 'process' => $process, 'secret' => $secret, 'ipc' => null,
            'stdout' => '', 'startedAt' => microtime(true), 'gotOutput' => false,
            'pending' => '', 'closing' => false,
        ];

        // Backpressure: if the browser can't keep up (huge `cat`), stop reading
        // from the worker until the WS buffer drains — the worker then blocks on
        // its socket write and stops reading SSH, which slows the server down.
        $ws->maxSendBufferSize = 4 * 1024 * 1024;
        $ws->onBufferFull = function () use ($id) {
            $this->sessions[$id]['ipc']?->pauseRecv();
        };
        $ws->onBufferDrain = function () use ($id) {
            $this->sessions[$id]['ipc']?->resumeRecv();
        };
    }

    private function handleMessage(TcpConnection $ws, string $data): void
    {
        $msg = json_decode($data, true);
        if (! is_array($msg) || ! isset($msg['type'])) {
            return;
        }

        if ($msg['type'] === 'ping') {
            $this->sendJson($ws, ['type' => 'pong']);

            return;
        }

        if (! isset($this->sessions[$ws->id])) {
            return;
        }

        if ($msg['type'] === 'input' && isset($msg['data'])) {
            $bytes = base64_decode((string) $msg['data'], true);
            if ($bytes !== false && $bytes !== '') {
                // A NUL keystroke (Ctrl+Space / Ctrl+@) would be read by the
                // worker as the start of a control line; it's useless in a
                // shell anyway, so drop it rather than desync the channel.
                $this->sendToWorker($ws->id, str_replace("\x00", '', $bytes));
            }

            return;
        }

        if ($msg['type'] === 'resize') {
            $cols = min(500, max(10, (int) ($msg['cols'] ?? 80)));
            $rows = min(200, max(5, (int) ($msg['rows'] ?? 24)));
            $this->sendToWorker($ws->id, "\x00RESIZE {$cols} {$rows}\n");
        }
    }

    // ── Worker side ───────────────────────────────────────────────────────

    private function sendToWorker(int $id, string $bytes): void
    {
        if ($bytes === '' || ! isset($this->sessions[$id])) {
            return;
        }

        $ipc = $this->sessions[$id]['ipc'];

        if ($ipc && $ipc->getStatus() === TcpConnection::STATUS_ESTABLISHED) {
            $ipc->send($bytes);

            return;
        }

        // Not connected yet: buffer (bounded) and flush on connect.
        if (strlen($this->sessions[$id]['pending']) + strlen($bytes) <= self::MAX_PENDING_INPUT) {
            $this->sessions[$id]['pending'] .= $bytes;
        }
    }

    /**
     * Polls the workers' process pipes — only used for the startup handshake
     * ("\x00PORT n" / "\x00ERROR …") and to drain stderr so a chatty worker
     * can never block on a full pipe. The terminal data itself flows over the
     * event-driven socket.
     */
    private function pumpWorkers(): void
    {
        foreach ($this->sessions as $id => $session) {
            if ($session['closing']) {
                continue;
            }

            $process = $session['process'];

            try {
                $err = $process->getIncrementalErrorOutput();
                if ($err !== '') {
                    Log::warning("terminal:serve — pty-worker#{$id} stderr: " . trim($err));
                }

                if ($session['ipc'] !== null) {
                    continue; // channel up: nothing else to poll
                }

                $this->sessions[$id]['stdout'] .= $process->getIncrementalOutput();
                $stdout = $this->sessions[$id]['stdout'];

                if (preg_match('/\x00ERROR ([^\n]*)\n/', $stdout, $m)) {
                    $this->failSession($id, $m[1]);
                    continue;
                }

                if (preg_match('/\x00PORT (\d+)\n/', $stdout, $m)) {
                    $this->connectWorker($id, (int) $m[1]);
                    continue;
                }

                if (! $process->isRunning()) {
                    Log::error("terminal:serve — pty-worker#{$id} saiu antes de abrir o canal", [
                        'exit' => $process->getExitCode(),
                        'stdout' => $stdout,
                        'stderr' => $process->getErrorOutput(),
                    ]);
                    $this->failSession($id, 'O processo do terminal encerrou inesperadamente. Veja o log do servidor.');
                    continue;
                }

                if (microtime(true) - $session['startedAt'] > self::WORKER_START_TIMEOUT) {
                    $this->failSession($id, 'O processo do terminal demorou demais para iniciar.');
                }
            } catch (Throwable $e) {
                Log::error("terminal:serve — erro no pty-worker#{$id}: " . $e->getMessage());
                $this->failSession($id, 'Erro interno no terminal.');
            }
        }
    }

    private function connectWorker(int $id, int $port): void
    {
        $ipc = new AsyncTcpConnection("tcp://127.0.0.1:{$port}");
        $this->sessions[$id]['ipc'] = $ipc;

        $ipc->onConnect = function (AsyncTcpConnection $ipc) use ($id) {
            if (! isset($this->sessions[$id])) {
                $ipc->close();

                return;
            }

            $ipc->send($this->sessions[$id]['secret'] . "\n");

            if ($this->sessions[$id]['pending'] !== '') {
                $ipc->send($this->sessions[$id]['pending']);
                $this->sessions[$id]['pending'] = '';
            }
        };

        $ipc->onMessage = function (AsyncTcpConnection $ipc, string $data) use ($id) {
            $session = $this->sessions[$id] ?? null;
            if (! $session) {
                return;
            }

            // Before any terminal output, a leading NUL is a control line from
            // the worker — i.e. a startup failure (bad credentials, host down…).
            if (! $session['gotOutput'] && str_starts_with($data, "\x00")) {
                $message = preg_match('/^\x00ERROR ([^\n]*)/', $data, $m) ? $m[1] : 'Falha ao iniciar o terminal.';
                $this->failSession($id, $message);

                return;
            }

            $this->sessions[$id]['gotOutput'] = true;
            $this->sendBinary($session['ws'], $data);
        };

        $ipc->onClose = function () use ($id) {
            $session = $this->sessions[$id] ?? null;
            if (! $session || $session['closing']) {
                return;
            }

            // Shell ended (`exit`, server closed it, network dropped).
            $this->sendJson($session['ws'], ['type' => 'closed']);
            $session['ws']->close();
            $this->endSession($id);
        };

        $ipc->onError = function ($ipc, $code, $message) use ($id) {
            Log::warning("terminal:serve — canal interno #{$id}: {$message}");
        };

        $ipc->connect();
    }

    // ── Teardown ──────────────────────────────────────────────────────────

    private function failSession(int $id, string $message): void
    {
        $session = $this->sessions[$id] ?? null;
        if (! $session) {
            return;
        }

        $this->sendJson($session['ws'], ['type' => 'error', 'message' => $message]);
        $session['ws']->close();
        $this->endSession($id);
    }

    /**
     * Never blocks: closing the channel makes the worker see EOF, disconnect
     * SSH cleanly and exit by itself; only a worker still alive after the grace
     * period gets killed. (The old code called Process::stop(2) inline, which
     * could freeze every other tab for up to 2s.)
     */
    private function endSession(int $id): void
    {
        $session = $this->sessions[$id] ?? null;
        if (! $session || $session['closing']) {
            return;
        }

        $this->sessions[$id]['closing'] = true;
        $session['ipc']?->close();

        Timer::add(self::WORKER_EXIT_GRACE, function () use ($id) {
            $process = $this->sessions[$id]['process'] ?? null;
            unset($this->sessions[$id]);

            try {
                if ($process && $process->isRunning()) {
                    $process->stop(0);
                }
            } catch (Throwable) {
                // already gone
            }
        }, [], false);
    }

    // ── Sending ───────────────────────────────────────────────────────────

    private function sendBinary(TcpConnection $ws, string $bytes): void
    {
        $ws->websocketType = Websocket::BINARY_TYPE_ARRAYBUFFER;
        $ws->send($bytes);
    }

    private function sendJson(TcpConnection $ws, array $payload): void
    {
        $ws->websocketType = Websocket::BINARY_TYPE_BLOB; // "\x81" = text frame
        $ws->send(json_encode($payload));
    }
}
