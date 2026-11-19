<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortcutReorderTest extends TestCase
{
    use RefreshDatabase;

    private function connectionWithShortcuts(User $user, array $names): Connection
    {
        $connection = Connection::create([
            'user_id' => $user->id, 'name' => 'srv', 'host' => '127.0.0.1', 'port' => 1,
            'username' => 'root', 'auth_type' => 'password', 'password' => 'x',
        ]);

        foreach ($names as $i => $name) {
            $connection->shortcuts()->create(['name' => $name, 'path' => "/$name", 'icon' => 'bi-folder', 'sort_order' => $i]);
        }

        return $connection;
    }

    private function order(Connection $connection): array
    {
        return $connection->shortcuts()->get()->pluck('name')->all();
    }

    public function test_saves_new_order(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $conn = $this->connectionWithShortcuts($user, ['a', 'b', 'c']);
        [$a, $b, $c] = $conn->shortcuts()->pluck('id')->all();

        $this->actingAs($user)
            ->postJson("/explorer/{$conn->id}/shortcuts/reorder", ['ids' => [$c, $a, $b]])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertSame(['c', 'a', 'b'], $this->order($conn));
    }

    public function test_ignores_foreign_ids_and_keeps_missing_ones_at_the_end(): void
    {
        $user  = User::factory()->create();
        $conn  = $this->connectionWithShortcuts($user, ['a', 'b', 'c']);
        $other = $this->connectionWithShortcuts($user, ['x']);
        [$a, $b, $c] = $conn->shortcuts()->pluck('id')->all();
        $x = $other->shortcuts()->value('id');

        $this->actingAs($user)
            ->postJson("/explorer/{$conn->id}/shortcuts/reorder", ['ids' => [$x, $c, $b]])
            ->assertOk();

        $this->assertSame(['c', 'b', 'a'], $this->order($conn));
        $this->assertSame(0, (int) $other->shortcuts()->value('sort_order'));
    }

    public function test_rejects_invalid_payload(): void
    {
        $user = User::factory()->create();
        $conn = $this->connectionWithShortcuts($user, ['a']);

        $this->actingAs($user)->postJson("/explorer/{$conn->id}/shortcuts/reorder", ['ids' => 'nope'])
            ->assertStatus(422);
    }
}
