<?php

namespace App\Services;

use App\Models\Connection;
use App\Repositories\Interfaces\ConnectionRepositoryInterface;
use App\Services\SSH\SshKeyLoader;
use App\Services\SSH\SSHService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConnectionService
{
    public function __construct(
        private readonly ConnectionRepositoryInterface $repository,
        private readonly SSHService $sshService,
    ) {}

    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function find(int $id): Connection
    {
        return $this->repository->find($id);
    }

    public function store(array $data, ?UploadedFile $keyFile = null): Connection
    {
        if ($keyFile !== null) {
            $data['ssh_key_path'] = $this->storeSSHKey($keyFile);
        }

        $data['user_id'] = auth()->id();

        return $this->repository->create($data);
    }

    public function update(Connection $connection, array $data, ?UploadedFile $keyFile = null): Connection
    {
        if ($keyFile !== null) {
            $this->deleteSSHKey($connection);
            $data['ssh_key_path'] = $this->storeSSHKey($keyFile);
            // New key: its passphrase is whatever came with it (none = clear the old one).
            $data['key_passphrase'] = $data['key_passphrase'] ?? null;
        } elseif (empty($data['key_passphrase'])) {
            // Same key, passphrase left blank: keep the stored one.
            unset($data['key_passphrase']);
        }

        return $this->repository->update($connection, $data);
    }

    public function destroy(Connection $connection): bool
    {
        $this->deleteSSHKey($connection);

        return $this->repository->delete($connection);
    }

    public function duplicate(Connection $connection): Connection
    {
        $data = $connection->only(['host', 'port', 'username', 'auth_type', 'password', 'key_passphrase', 'startup_script']);
        $data['name']    = $connection->name . ' - copy';
        $data['user_id'] = auth()->id();

        if (! empty($connection->ssh_key_path) && Storage::exists($connection->ssh_key_path)) {
            $newPath = 'ssh_keys/' . Str::uuid() . '_' . basename($connection->ssh_key_path);
            Storage::copy($connection->ssh_key_path, $newPath);
            $data['ssh_key_path'] = $newPath;
        }

        return $this->repository->create($data);
    }

    public function testConnection(Connection $connection): array
    {
        return $this->sshService->testConnection($connection);
    }

    private function storeSSHKey(UploadedFile $file): string
    {
        // Keep the original name recognizable but filesystem/column-safe.
        $original = preg_replace('/[^A-Za-z0-9._-]+/', '_', $file->getClientOriginalName());
        $path     = 'ssh_keys/' . Str::uuid() . '_' . substr($original, -100);

        // Stored normalized (LF endings, no BOM) — see SshKeyLoader.
        Storage::put($path, SshKeyLoader::normalize((string) file_get_contents($file->getRealPath())));

        return $path;
    }

    private function deleteSSHKey(Connection $connection): void
    {
        if (! empty($connection->ssh_key_path) && Storage::exists($connection->ssh_key_path)) {
            Storage::delete($connection->ssh_key_path);
        }
    }
}
