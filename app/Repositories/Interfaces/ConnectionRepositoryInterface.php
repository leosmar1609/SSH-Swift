<?php

namespace App\Repositories\Interfaces;

use App\Models\Connection;
use Illuminate\Database\Eloquent\Collection;

interface ConnectionRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): Connection;

    public function create(array $data): Connection;

    public function update(Connection $connection, array $data): Connection;

    public function delete(Connection $connection): bool;
}
