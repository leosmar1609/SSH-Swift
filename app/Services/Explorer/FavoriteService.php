<?php

namespace App\Services\Explorer;

use App\Models\Connection;
use App\Models\Favorite;
use Illuminate\Database\Eloquent\Collection;

class FavoriteService
{
    public function getForConnection(Connection $connection): Collection
    {
        return $connection->favorites;
    }

    public function add(Connection $connection, string $path, ?string $label = null): Favorite
    {
        return $connection->favorites()->updateOrCreate(
            ['path' => $path],
            ['label' => $label ?: basename($path)],
        );
    }

    public function remove(Connection $connection, string $path): bool
    {
        return (bool) $connection->favorites()->where('path', $path)->delete();
    }

    public function isFavorite(Connection $connection, string $path): bool
    {
        return $connection->favorites()->where('path', $path)->exists();
    }
}
