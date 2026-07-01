@extends('layouts.app')

@section('title', 'Novo Usuário')

@section('content')

<div class="lp-page-header">
    <div>
        <h1 class="lp-page-title">Novo Usuário</h1>
        <p class="lp-page-subtitle">Crie um novo acesso ao LeoPanel</p>
    </div>
    <a href="{{ route('users.index') }}" class="btn-lp-secondary">
        <i class="bi bi-arrow-left"></i>
        Voltar
    </a>
</div>

<div class="lp-form-section" style="max-width:560px">
    <form method="POST" action="{{ route('users.store') }}">
        @csrf

        <div class="mb-4">
            <label class="lp-form-label" for="name">Nome</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
                class="lp-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                placeholder="Nome completo"
            >
            @error('name')
                <div class="lp-invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label class="lp-form-label" for="email">E-mail</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                class="lp-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                placeholder="usuario@exemplo.com"
            >
            @error('email')
                <div class="lp-invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label class="lp-form-label" for="password">Senha</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                class="lp-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                placeholder="Mínimo 8 caracteres"
            >
            @error('password')
                <div class="lp-invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label class="lp-form-label" for="password_confirmation">Confirmar Senha</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                class="lp-input"
                placeholder="Repita a senha"
            >
        </div>

        <div class="mb-4">
            <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-size:13.5px;color:var(--lp-text)">
                <input
                    type="checkbox"
                    name="is_admin"
                    value="1"
                    {{ old('is_admin') ? 'checked' : '' }}
                    style="accent-color:var(--lp-blue);width:16px;height:16px;cursor:pointer"
                >
                <span>
                    Administrador
                    <span style="color:var(--lp-text-muted);font-size:12px;margin-left:.3rem">— acesso ao CRUD de usuários</span>
                </span>
            </label>
        </div>

        <div style="display:flex;gap:.75rem;padding-top:.5rem">
            <button type="submit" class="btn-lp-primary">
                <i class="bi bi-person-plus"></i>
                Criar Usuário
            </button>
            <a href="{{ route('users.index') }}" class="btn-lp-secondary">Cancelar</a>
        </div>
    </form>
</div>

@endsection
