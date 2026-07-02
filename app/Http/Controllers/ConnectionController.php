<?php

namespace App\Http\Controllers;

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
            $request->only(['name', 'host', 'port', 'username', 'startup_script']),
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
        $this->service->update(
            $connection,
            $request->only(['name', 'host', 'port', 'username', 'startup_script']),
            $request->file('ssh_key'),
        );

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

    public function testLive(Request $request): JsonResponse
    {
        $request->validate([
            'host'           => ['required', 'string'],
            'port'           => ['required', 'integer'],
            'username'       => ['required', 'string'],
            'ssh_key'        => ['required', 'file'],
            'startup_script' => ['nullable', 'string'],
        ]);

        $keyFile = $request->file('ssh_key');
        $keyPath = $keyFile->storeAs('ssh_keys/temp', uniqid() . '_' . $keyFile->getClientOriginalName());

        $connection = new Connection([
            'host'           => $request->input('host'),
            'port'           => (int) $request->input('port', 22),
            'username'       => $request->input('username'),
            'ssh_key_path'   => $keyPath,
            'startup_script' => $request->input('startup_script'),
        ]);

        $result = $this->sshService->testConnection($connection);

        \Illuminate\Support\Facades\Storage::delete($keyPath);

        return response()->json($result);
    }
}
