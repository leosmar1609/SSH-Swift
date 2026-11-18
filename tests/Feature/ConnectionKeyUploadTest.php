<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\User;
use App\Services\SSH\SshKeyLoader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Crypt\Common\Formats\Keys\PuTTY;
use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\RSA;
use Tests\TestCase;

/**
 * Key upload on the connection form: every key that's accepted must actually
 * load later (SSH/SFTP/terminal), and keys that won't are rejected up front.
 */
class ConnectionKeyUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        PuTTY::setVersion(2);
        parent::tearDown();
    }

    private function upload(string $name, string $keyContent, ?string $passphrase = null)
    {
        $admin = User::factory()->create(['is_admin' => true]);

        return $this->actingAs($admin)->post('/connections', array_filter([
            'name'           => $name,
            'host'           => '127.0.0.1',
            'port'           => 22,
            'username'       => 'root',
            'auth_type'      => 'key',
            'ssh_key'        => UploadedFile::fake()->createWithContent("minha chave ({$name}).ppk", $keyContent),
            'key_passphrase' => $passphrase,
        ]));
    }

    private function assertSavedAndLoadable(string $name): Connection
    {
        $connection = Connection::withoutGlobalScopes()->where('name', $name)->first();
        $this->assertNotNull($connection, "{$name} não foi salvo");
        SshKeyLoader::fromConnection($connection); // throws if it won't load

        return $connection;
    }

    public static function unencryptedKeys(): array
    {
        return [
            'ppk v2 rsa'     => [2, 'rsa'],
            'ppk v2 ed25519' => [2, 'ed25519'],
            'ppk v3 rsa'     => [3, 'rsa'],
            'ppk v3 ed25519' => [3, 'ed25519'],
        ];
    }

    /** @dataProvider unencryptedKeys */
    public function test_ppk_without_passphrase_is_saved_and_loads(int $version, string $type): void
    {
        PuTTY::setVersion($version);
        $key = $type === 'rsa' ? RSA::createKey(2048) : EC::createKey('Ed25519');

        $this->upload('k', $key->toString('PuTTY'))->assertSessionHasNoErrors();
        $this->assertSavedAndLoadable('k');
    }

    public function test_ppk_with_double_converted_line_endings_and_bom_loads(): void
    {
        PuTTY::setVersion(3);
        // phpseclib writes CRLF already; converting again yields "\r\r\n".
        $ppk = "\xEF\xBB\xBF" . str_replace("\n", "\r\n", EC::createKey('Ed25519')->toString('PuTTY'));

        $this->upload('crlf', $ppk)->assertSessionHasNoErrors();
        $this->assertSavedAndLoadable('crlf');
    }

    public function test_passphrase_protected_ppk(): void
    {
        $ppk = RSA::createKey(2048)->withPassword('s3gr3d0')->toString('PuTTY'); // v2: no sodium needed

        $this->upload('sem', $ppk)->assertSessionHasErrors(['ssh_key' => 'Esta chave é protegida por senha. Preencha o campo "Senha da chave".']);
        $this->upload('errada', $ppk, 'outra')->assertSessionHasErrors(['ssh_key' => 'Senha da chave incorreta.']);
        $this->assertSame(0, Connection::withoutGlobalScopes()->count());

        $this->upload('certa', $ppk, 's3gr3d0')->assertSessionHasNoErrors();
        $connection = $this->assertSavedAndLoadable('certa');
        $this->assertSame('s3gr3d0', $connection->key_passphrase);
        $this->assertNotSame('s3gr3d0', $connection->getRawOriginal('key_passphrase'), 'senha da chave gravada sem criptografia');
    }

    public function test_public_key_is_rejected(): void
    {
        $public = RSA::createKey(2048)->getPublicKey()->toString('OpenSSH');

        $this->upload('pub', $public)->assertSessionHasErrors(['ssh_key' => 'O arquivo enviado é uma chave PÚBLICA (ex.: .pub). Envie a chave PRIVADA.']);
    }

    public function test_passphrase_can_be_added_later_on_edit(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $path  = 'ssh_keys/old.ppk';
        Storage::put($path, RSA::createKey(2048)->withPassword('abc')->toString('PuTTY'));

        $connection = Connection::create([
            'user_id' => $admin->id, 'name' => 'old', 'host' => '127.0.0.1', 'port' => 22,
            'username' => 'root', 'auth_type' => 'key', 'ssh_key_path' => $path,
        ]);

        $form = ['name' => 'old', 'host' => '127.0.0.1', 'port' => 22, 'username' => 'root', 'auth_type' => 'key'];

        $this->actingAs($admin)->put("/connections/{$connection->id}", $form + ['key_passphrase' => 'xyz'])
            ->assertSessionHasErrors(['key_passphrase' => 'Senha da chave incorreta.']);

        $this->actingAs($admin)->put("/connections/{$connection->id}", $form + ['key_passphrase' => 'abc'])
            ->assertSessionHasNoErrors();
        $this->assertSavedAndLoadable('old');

        // Blank on a later edit keeps it.
        $this->actingAs($admin)->put("/connections/{$connection->id}", $form)->assertSessionHasNoErrors();
        $this->assertSame('abc', $connection->fresh()->key_passphrase);
    }
}
