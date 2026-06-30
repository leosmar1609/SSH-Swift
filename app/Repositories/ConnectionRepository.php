<?php

namespace App\Repositories;

use App\Models\Connection;
use App\Repositories\Interfaces\ConnectionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ConnectionRepository implements ConnectionRepositoryInterface
{
    public function all(): Collection
    {
        return Connection::orderBy('name')->get();
    }

    public function find(int $id): Connection
    {
        return Connection::findOrFail($id);
    }

    public function create(array $data): Connection
    {
        return Connection::create($data);
    }

    public function update(Connection $connection, array $data): Connection
    {
        $connection->update($data);

        return $connection->fresh();
    }

    public function delete(Connection $connection): bool
    {
        return (bool) $connection->delete();
    }
}
