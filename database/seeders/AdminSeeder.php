<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@leopanel.local'],
            [
                'name'     => 'Admin',
                'password' => 'password123',
                'is_admin' => true,
            ]
        );
    }
}
