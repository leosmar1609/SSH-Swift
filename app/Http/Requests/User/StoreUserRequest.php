<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_admin' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'         => 'Este e-mail já está em uso.',
            'password.min'         => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed'   => 'A confirmação de senha não confere.',
        ];
    }
}
