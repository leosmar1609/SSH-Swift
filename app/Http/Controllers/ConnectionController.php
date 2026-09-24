<?php

namespace App\Http\Controllers;

use App\Http\Requests\Connection\ConnectionAuthRules;
use App\Http\Requests\Connection\StoreConnectionRequest;
use App\Http\Requests\Connection\UpdateConnectionRequest;
use App\Models\Connection;
use App\Services\ConnectionService;
use App\Services\SSH\SSHService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConnectionController extends Controller
{
    public function __construct(
        private readonly ConnectionService $service,
        private readonly SSHService $sshService,
    ) {}

    public function index(): View
    {
        $connections = $this->service->all();

        return view('connections.index', compact('connections'));
    }

    public function create(): View
    {
        return view('connections.create');
    }

    public function store(StoreConnectionRequest $request): RedirectResponse
    {
        $this->service->store(
            $request->only(['name', 'host', 'port', 'username', 'auth_type', 'password', 'startup_script']),
            $request->file('ssh_key'),
        );

        return redirect()
            ->route('connections.index')
            ->with('success', 'Conexão cadastrada com sucesso.');
    }

    public function edit(Connection $connection): View
    {
        return view('connections.edit', compact('connection'));
    }

    public function update(UpdateConnectionRequest $request, Connection $connection): RedirectResponse
    {
        $data = $request->only(['name', 'host', 'port', 'username', 'auth_type', 'password', 'startup_script']);

        // Blank password on update means "keep the current one" — same semantics as
        // leaving the ssh_key file input empty, handled separately below via the file arg.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $this->service->update($connection, $data, $request->file('ssh_key'));

        return redirect()
            ->route('connections.index')
            ->with('success', 'Conexão atualizada com sucesso.');
    }

    public function destroy(Connection $connection): RedirectResponse
    {
        $this->service->destroy($connection);

        return redirect()
            ->route('connections.index')
            ->with('success', 'Conexão removida com sucesso.');
    }

    public function duplicate(Connection $connection): RedirectResponse
    {
        $this->service->duplicate($connection);

        return redirect()
            ->route('connections.index')
            ->with('success', 'Conexão duplicada com sucesso.');
    }

    public function test(Connection $connection): JsonResponse
    {
        $result = $this->service->testConnection($connection);

        return response()->json($result);
    }

    /**
     * "Conectar por IP" — creates a real Connection (auto-named from
     * username@host, no extra typing required) exactly like the normal
     * create form, then hands back the Explorer URL so it opens straight
     * into the file browser instead of the connections list.
     */
    public function quickConnect(Request $request): JsonResponse
    {
        $request->validate(array_merge([
            'host'     => ['required', 'string', 'max:255'],
            'port'     => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:100'],
        ], ConnectionAuthRules::rules(isCreate: true)), ConnectionAuthRules::messages());

        $data = [
            'name'      => $request->input('username') . '@' . $request->input('host'),
            'host'      => $request->input('host'),
            'port'      => (int) $request->input('port'),
            'username'  => $request->input('username'),
            'auth_type' => $request->input('auth_type'),
        ];

        if ($data['auth_type'] === 'password') {
            $data['password'] = $request->input('password');
        }

        $connection = $this->service->store($data, $request->file('ssh_key'));

        return response()->json([
            'url' => route('explorer.show', $connection),
        ]);
    }

    public function testLive(Request $request): JsonResponse
    {
        $request->validate(array_merge([
            'host'           => ['required', 'string'],
            'port'           => ['required', 'integer'],
            'username'       => ['required', 'string'],
            'startup_script' => ['nullable', 'string'],
        ], ConnectionAuthRules::rules(isCreate: true)), ConnectionAuthRules::messages());

        $authType = $request->input('auth_type');
        $keyPath  = null;

        $data = [
            'host'           => $request->input('host'),
            'port'           => (int) $request->input('port', 22),
            'username'       => $request->input('username'),
            'auth_type'      => $authType,
            'startup_script' => $request->input('startup_script'),
        ];

        if ($authType === 'password') {
            $data['password'] = $request->input('password');
        } else {
            $keyFile = $request->file('ssh_key');
            $keyPath = $keyFile->storeAs('ssh_keys/temp', uniqid() . '_' . $keyFile->getClientOriginalName());
            $data['ssh_key_path'] = $keyPath;
        }

        $connection = new Connection($data);

        $result = $this->sshService->testConnection($connection);

        if ($keyPath !== null) {
            \Illuminate\Support\Facades\Storage::delete($keyPath);
        }

        return response()->json($result);
    }
}
