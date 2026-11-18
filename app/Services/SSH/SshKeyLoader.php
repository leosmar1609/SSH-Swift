<?php

namespace App\Services\SSH;

use App\Models\Connection;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;
use RuntimeException;
use Throwable;

/**
 * The one place private keys get parsed — used at upload time (to reject a
 * key that won't work before the connection is saved) and by every SSH/SFTP/
 * terminal session.
 *
 * Accepts OpenSSH, PEM and PuTTY (.ppk v2/v3) keys, optionally passphrase-
 * protected. Normalizes what phpseclib chokes on (see normalize()).
 */
final class SshKeyLoader
{
    public static function fromConnection(Connection $connection): PrivateKey
    {
        if (empty($connection->ssh_key_path)) {
            throw new RuntimeException('Nenhuma chave SSH configurada para esta conexão.');
        }

        if (! Storage::exists($connection->ssh_key_path)) {
            throw new RuntimeException('Arquivo de chave SSH não encontrado. Recadastre a chave.');
        }

        return self::load(Storage::get($connection->ssh_key_path), $connection->key_passphrase);
    }

    public static function load(string $content, ?string $passphrase = null): PrivateKey
    {
        $content    = self::normalize($content);
        $passphrase = ($passphrase === null || $passphrase === '') ? null : $passphrase;
        $encrypted  = self::isEncrypted($content);

        if ($encrypted && $passphrase === null) {
            throw new RuntimeException('Esta chave é protegida por senha. Preencha o campo "Senha da chave".');
        }

        try {
            $key = PublicKeyLoader::load($content, $passphrase ?? false);
        } catch (Throwable) {
            throw new RuntimeException($encrypted
                ? 'Senha da chave incorreta.'
                : 'Chave SSH inválida ou em formato não suportado. Use uma chave privada OpenSSH, PEM ou PuTTY (.ppk).');
        }

        if (! $key instanceof PrivateKey) {
            throw new RuntimeException('O arquivo enviado é uma chave PÚBLICA (ex.: .pub). Envie a chave PRIVADA.');
        }

        return $key;
    }

    /**
     * Strips a UTF-8 BOM and converts line endings to LF — including "\r\r\n",
     * what a CRLF file becomes after a second LF->CRLF conversion (FTP in ASCII
     * mode, git autocrlf), which phpseclib can't parse. Plain CRLF it handles.
     */
    public static function normalize(string $content): string
    {
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        return trim(str_replace("\r", "\n", preg_replace('/\r+\n/', "\n", $content))) . "\n";
    }

    private static function isEncrypted(string $content): bool
    {
        // PuTTY: "Encryption: aes256-cbc" (vs "Encryption: none")
        if (preg_match('/^Encryption:\s*(\S+)/mi', $content, $m)) {
            return strtolower($m[1]) !== 'none';
        }

        // PEM: "Proc-Type: 4,ENCRYPTED" / "BEGIN ENCRYPTED PRIVATE KEY"
        if (str_contains($content, 'ENCRYPTED')) {
            return true;
        }

        // OpenSSH: binary body starts "openssh-key-v1\0" + length-prefixed cipher name.
        if (preg_match('/-----BEGIN OPENSSH PRIVATE KEY-----(.+?)-----END/s', $content, $m)) {
            $bin = base64_decode(preg_replace('/\s+/', '', $m[1]), true);
            $magic = "openssh-key-v1\0";

            if ($bin !== false && str_starts_with($bin, $magic) && strlen($bin) >= strlen($magic) + 4) {
                $len = unpack('N', substr($bin, strlen($magic), 4))[1];

                return substr($bin, strlen($magic) + 4, $len) !== 'none';
            }
        }

        return false;
    }
}
