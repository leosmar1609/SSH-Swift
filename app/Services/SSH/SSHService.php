<?php

namespace App\Services\SSH;

use App\Models\Connection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Exception\ConnectionClosedException;
use phpseclib3\Exception\NoSupportedAlgorithmsException;
use phpseclib3\Exception\UnableToConnectException;
use phpseclib3\Net\SSH2;
use RuntimeException;
use Throwable;

class SSHService
{
    private const CONNECT_TIMEOUT = 10;

    public function connect(Connection $connection): SSH2
    {
        $ssh = new SSH2($connection->host, $connection->port, self::CONNECT_TIMEOUT);

        $credential = $connection->auth_type === 'password'
            ? $this->loadPassword($connection)
            : $this->loadPrivateKey($connection);

        if (! $ssh->login($connection->username, $credential)) {
            throw new RuntimeException($this->resolveAuthError($ssh));
        }

        return $ssh;
    }

    public function testConnection(Connection $connection): array
    {
        try {
            $ssh = $this->connect($connection);

            // Timeout para evitar que comandos interativos (ex: sudo su -) bloqueiem indefinidamente
            $ssh->setTimeout(15);

            $lines = array_values(array_filter(
                array_map('trim', $connection->startup_script_lines),
                fn(string $l) => $l !== '',
            ));

            $lines[] = 'echo "__LP_DIR__$(pwd)__LP_END__"';

            // Piped via base64 so that interactive commands like "sudo su -" receive
            // the subsequent lines as stdin (same behaviour as a real terminal session).
            $b64    = base64_encode(implode("\n", $lines));
            $output = $ssh->exec("echo {$b64} | base64 -d | bash");

            $ssh->disconnect();

            preg_match('/__LP_DIR__(.+?)__LP_END__/', $output, $m);
            $currentDirectory = isset($m[1]) ? trim($m[1]) : null;

            return [
                'success'   => true,
                'message'   => 'Conectado com sucesso.',
                'directory' => $currentDirectory,
            ];
        } catch (UnableToConnectException $e) {
            $message = "Host indisponível: não foi possível conectar a {$connection->host}:{$connection->port}. Verifique o host e a porta.";

            Log::warning('SSH: unable to connect', [
                'host'  => $connection->host,
                'port'  => $connection->port,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $message];

        } catch (ConnectionClosedException $e) {
            $message = 'A conexão foi encerrada inesperadamente pelo servidor. Verifique as configurações SSH do servidor.';

            Log::warning('SSH: connection closed', [
                'host'  => $connection->host,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $message];

        } catch (NoSupportedAlgorithmsException $e) {
            $message = 'Nenhum algoritmo SSH em comum com o servidor. O servidor pode estar usando configuração muito restritiva.';

            Log::warning('SSH: no supported algorithms', [
                'host'  => $connection->host,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $message];

        } catch (RuntimeException $e) {
            Log::warning('SSH: connection failed', [
                'host'  => $connection->host,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];

        } catch (Throwable $e) {
            Log::error('SSH: unexpected error', [
                'host'  => $connection->host,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['success' => false, 'message' => 'Erro inesperado ao conectar. Verifique os logs para mais detalhes.'];
        }
    }

    private function loadPassword(Connection $connection): string
    {
        if (empty($connection->password)) {
            throw new RuntimeException('Nenhuma senha configurada para esta conexão.');
        }

        return $connection->password;
    }

    private function loadPrivateKey(Connection $connection): mixed
    {
        if (empty($connection->ssh_key_path)) {
            throw new RuntimeException('Nenhuma chave SSH configurada para esta conexão.');
        }

        if (! Storage::exists($connection->ssh_key_path)) {
            throw new RuntimeException('Arquivo de chave SSH não encontrado. Recadastre a chave.');
        }

        $keyContent = Storage::get($connection->ssh_key_path);

        try {
            return PublicKeyLoader::load($keyContent);
        } catch (Throwable $e) {
            throw new RuntimeException('Chave SSH inválida ou corrompida. Verifique o arquivo enviado.');
        }
    }

    private function resolveAuthError(SSH2 $ssh): string
    {
        $lastError = $ssh->getLastError();

        if (str_contains($lastError, 'Unable to connect')) {
            return "Host indisponível ou porta incorreta ({$ssh->getServerIdentification()}).";
        }

        if (str_contains(strtolower($lastError), 'timeout')) {
            return 'Timeout: o servidor não respondeu a tempo. Verifique o host e a porta.';
        }

        if (str_contains(strtolower($lastError), 'authentication')) {
            return 'Falha na autenticação. Verifique o usuário e a senha/chave SSH.';
        }

        return 'Falha ao autenticar. Usuário, senha ou chave SSH inválidos.';
    }
}
