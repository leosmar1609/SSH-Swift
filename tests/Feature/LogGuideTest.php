<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O Guia do Log é compartilhado com toda a empresa: precisa abrir para
 * qualquer usuário logado e nunca expor credenciais nem caminhos internos.
 */
class LogGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_login(): void
    {
        $this->get('/guia-log')->assertRedirect('/login');
    }

    public function test_common_user_can_open_it(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/guia-log')
            ->assertOk()
            ->assertSee('Guia do Log da API')
            ->assertSee('lg-sample-data', false);
    }

    public function test_example_log_has_no_secrets_or_internal_paths(): void
    {
        $user = User::factory()->create();
        $html = $this->actingAs($user)->get('/guia-log')->getContent();

        $this->assertStringContainsString('SEU_TOKEN_AQUI', $html);
        $this->assertStringNotContainsString('YXBpbmVnb2NpYWNhb', $html); // token real da doc
        $this->assertStringNotContainsString('/var/www', $html);
        $this->assertStringNotContainsString('.php:', $html);
    }
}
