<?php

namespace App\Services;

use App\Models\Connection;
use App\Repositories\Interfaces\ConnectionRepositoryInterface;
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
        }

        return $this->repository->update($connection, $data);
    }

    public function destroy(Connection $connection): bool
    {
        $this->deleteSSHKey($connection);

        return $this->repository->delete($connection);
    }

    public function testConnection(Connection $connection): array
    {
        return $this->sshService->testConnection($connection);
    }

    private function storeSSHKey(UploadedFile $file): string
    {
        $filename = Str::uuid() . '_' . $file->getClientOriginalName();

        return $file->storeAs('ssh_keys', $filename);
    }

    private function deleteSSHKey(Connection $connection): void
    {
        if (! empty($connection->ssh_key_path) && Storage::exists($connection->ssh_key_path)) {
            Storage::delete($connection->ssh_key_path);
        }
    }
}
