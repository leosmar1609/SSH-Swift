<?php

namespace App\Policies;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConnectionPolicy
{
    use HandlesAuthorization;

    /**
     * LeoPanel é privado — toda action é permitida sem restrições de usuário nesta fase.
     * Quando autenticação multi-usuário for implementada, refinar este Policy.
     */
    public function before(?User $user): bool
    {
        return true;
    }
}
