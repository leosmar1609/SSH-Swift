<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_admin;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_admin' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'       => 'Este e-mail já está em uso.',
            'password.min'       => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'A confirmação de senha não confere.',
        ];
    }
}
