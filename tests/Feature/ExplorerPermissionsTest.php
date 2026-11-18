<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Non-admins get the Explorer read-only and no terminal; admins keep both.
 * Enforced on the routes (not just by hiding buttons), which is what this checks.
 */
class ExplorerPermissionsTest extends TestCase
{
    use RefreshDatabase;

    /** Every Explorer route that changes something on the remote server. */
    private const WRITE_ROUTES = [
        ['post',   'file/save',    ['path' => '/tmp/x', 'content' => 'x']],
        ['post',   'fs/file',      ['path' => '/tmp/x']],
        ['post',   'fs/dir',       ['path' => '/tmp/x']],
        ['post',   'fs/rename',    ['from' => '/tmp/x', 'to' => '/tmp/y']],
        ['delete', 'fs/delete',    ['path' => '/tmp/x']],
        ['post',   'fs/duplicate', ['path' => '/tmp/x']],
    ];

    private function connectionFor(User $user): Connection
    {
        // Port 1 on loopback: refused instantly, so admin requests that get past
        // the middleware fail fast at the SSH step instead of hanging.
        return Connection::create([
            'user_id'   => $user->id,
            'name'      => 'test',
            'host'      => '127.0.0.1',
            'port'      => 1,
            'username'  => 'root',
            'auth_type' => 'password',
            'password'  => 'secret',
        ]);
    }

    public function test_non_admin_cannot_change_files(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $connection = $this->connectionFor($user);

        foreach (self::WRITE_ROUTES as [$method, $uri, $payload]) {
            $this->actingAs($user)
                ->{$method . 'Json'}("/explorer/{$connection->id}/{$uri}", $payload)
                ->assertStatus(403)
                ->assertJson(['success' => false, 'message' => 'Acesso restrito a administradores.']);
        }
    }

    public function test_admin_passes_the_permission_check_on_file_changes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $connection = $this->connectionFor($admin);

        foreach (self::WRITE_ROUTES as [$method, $uri, $payload]) {
            $status = $this->actingAs($admin)
                ->{$method . 'Json'}("/explorer/{$connection->id}/{$uri}", $payload)
                ->status();

            $this->assertNotSame(403, $status, "{$uri} blocked an admin");
        }
    }

    public function test_terminal_is_admin_only(): void
    {
        $user  = User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($user)
            ->get("/connections/{$this->connectionFor($user)->id}/terminal")
            ->assertForbidden();

        $this->actingAs($admin)
            ->get("/connections/{$this->connectionFor($admin)->id}/terminal")
            ->assertOk();
    }

    public function test_explorer_page_is_read_only_for_non_admin(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get("/explorer/{$this->connectionFor($user)->id}")
            ->assertOk()
            ->assertSee('class="lp-readonly"', false)
            ->assertSee('isAdmin      : false', false);
    }
}
