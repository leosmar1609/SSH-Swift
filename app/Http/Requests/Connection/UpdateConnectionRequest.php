<?php

namespace App\Http\Requests\Connection;

use App\Models\Connection;
use App\Services\SSH\SshKeyLoader;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Validator;
use RuntimeException;

class UpdateConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'name'           => ['required', 'string', 'max:100'],
            'host'           => ['required', 'string', 'max:255'],
            'port'           => ['required', 'integer', 'min:1', 'max:65535'],
            'username'       => ['required', 'string', 'max:100'],
            'startup_script' => ['nullable', 'string', 'max:5000'],
        ], ConnectionAuthRules::rules(isCreate: false));
    }

    /**
     * Passphrase typed without uploading a new key = "this is the passphrase of
     * the key already saved" (e.g. it was saved without one). Check it against
     * that key; with a new upload, ConnectionAuthRules already checks the pair.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $connection = $this->route('connection');

                if ($validator->errors()->isNotEmpty()
                    || $this->input('auth_type') !== 'key'
                    || $this->hasFile('ssh_key')
                    || blank($this->input('key_passphrase'))
                    || ! $connection instanceof Connection
                    || empty($connection->ssh_key_path)
                    || ! Storage::exists($connection->ssh_key_path)) {
                    return;
                }

                try {
                    SshKeyLoader::load(Storage::get($connection->ssh_key_path), $this->input('key_passphrase'));
                } catch (RuntimeException $e) {
                    $validator->errors()->add('key_passphrase', $e->getMessage());
                }
            },
        ];
    }

    public function messages(): array
    {
        return array_merge([
            'name.required'     => 'O nome da conexão é obrigatório.',
            'host.required'     => 'O host/IP é obrigatório.',
            'port.required'     => 'A porta SSH é obrigatória.',
            'port.integer'      => 'A porta deve ser um número inteiro.',
            'port.min'          => 'A porta deve ser no mínimo 1.',
            'port.max'          => 'A porta deve ser no máximo 65535.',
            'username.required' => 'O usuário SSH é obrigatório.',
        ], ConnectionAuthRules::messages());
    }
}
