<?php

namespace App\Http\Requests\Connection;

use App\Services\SSH\SshKeyLoader;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class ConnectionAuthRules
{
    /**
     * Validation rules shared by every entry point that accepts SSH
     * credentials (Store/UpdateConnectionRequest, ConnectionController::testLive,
     * TerminalController::quickConnect) — kept in one place so the
     * key-vs-password branching can't drift between them.
     */
    /**
     * @param bool $isCreate True when there's no existing stored credential to fall
     *                       back on (create, testLive, quick-connect) — the chosen
     *                       auth method's credential is required. False on update,
     *                       where leaving the field blank means "keep the current one".
     */
    public static function rules(bool $isCreate): array
    {
        return [
            'auth_type'      => ['required', 'string', 'in:key,password'],
            'ssh_key'        => [$isCreate ? 'required_if:auth_type,key' : 'nullable', 'file', 'max:512', self::keyMustLoad(...)],
            'key_passphrase' => ['nullable', 'string', 'max:255'],
            'password'       => [$isCreate ? 'required_if:auth_type,password' : 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Parses the uploaded key (with the passphrase sent alongside it) so a key
     * that won't work — protected without passphrase, wrong passphrase, a
     * public key, unsupported format — is rejected with a clear message
     * instead of being saved and only failing later on connect.
     */
    private static function keyMustLoad(string $attribute, mixed $value, \Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        try {
            SshKeyLoader::load((string) file_get_contents($value->getRealPath()), request()->input('key_passphrase'));
        } catch (RuntimeException $e) {
            $fail($e->getMessage());
        }
    }

    public static function messages(): array
    {
        return [
            'auth_type.required'        => 'Selecione o método de autenticação.',
            'auth_type.in'              => 'Método de autenticação inválido.',
            'ssh_key.required_if'       => 'A chave SSH é obrigatória para este método de autenticação.',
            'ssh_key.file'              => 'O campo chave SSH deve ser um arquivo válido.',
            'ssh_key.max'               => 'A chave SSH não pode ultrapassar 512KB.',
            'password.required_if'      => 'A senha é obrigatória para este método de autenticação.',
            'key_passphrase.max'        => 'A senha da chave não pode ultrapassar 255 caracteres.',
        ];
    }
}
