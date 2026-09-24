<?php
/**
 * SETUP TEMPORÁRIO — APAGAR APÓS USO
 * Acesso: http://seudominio.com/setup.php?token=TECHIAPANEL2026
 */

if (($_GET['token'] ?? '') !== 'TECHIAPANEL2026') {
    http_response_code(403);
    die('<h1>403 Forbidden</h1>');
}

define('LARAVEL_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $user = \App\Models\User::firstOrCreate(
        ['email' => 'admin@techiapanel.local'],
        [
            'name'     => 'Admin',
            'password' => 'password123',
            'is_admin' => true,
        ]
    );

    echo '<pre style="font-family:monospace;padding:20px">';
    echo '✅ Admin criado com sucesso!' . PHP_EOL;
    echo '   E-mail : admin@techiapanel.local' . PHP_EOL;
    echo '   Senha  : password123' . PHP_EOL . PHP_EOL;
    echo '⚠️  IMPORTANTE: Altere a senha após o primeiro login!' . PHP_EOL;
    echo '⚠️  APAGUE ESTE ARQUIVO: public/setup.php' . PHP_EOL;
    echo '</pre>';

    // Auto-deleta o arquivo
    unlink(__FILE__);

    echo '<p style="color:green;font-family:monospace;padding:0 20px">✅ Arquivo setup.php removido automaticamente.</p>';

} catch (\Throwable $e) {
    echo '<pre style="color:red;padding:20px">Erro: ' . htmlspecialchars($e->getMessage()) . '</pre>';
}
