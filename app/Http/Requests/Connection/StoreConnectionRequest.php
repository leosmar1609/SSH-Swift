<?php

namespace App\Http\Requests\Connection;

use Illuminate\Foundation\Http\FormRequest;

class StoreConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:100'],
            'host'           => ['required', 'string', 'max:255'],
            'port'           => ['required', 'integer', 'min:1', 'max:65535'],
            'username'       => ['required', 'string', 'max:100'],
            'ssh_key'        => ['required', 'file', 'max:512'],
            'startup_script' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'O nome da conexão é obrigatório.',
            'host.required'     => 'O host/IP é obrigatório.',
            'port.required'     => 'A porta SSH é obrigatória.',
            'port.integer'      => 'A porta deve ser um número inteiro.',
            'port.min'          => 'A porta deve ser no mínimo 1.',
            'port.max'          => 'A porta deve ser no máximo 65535.',
            'username.required' => 'O usuário SSH é obrigatório.',
            'ssh_key.required'  => 'A chave SSH é obrigatória.',
            'ssh_key.file'      => 'O campo chave SSH deve ser um arquivo válido.',
            'ssh_key.max'       => 'A chave SSH não pode ultrapassar 512KB.',
        ];
    }
}
