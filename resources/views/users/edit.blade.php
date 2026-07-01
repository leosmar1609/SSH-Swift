@extends('layouts.app')

@section('title', 'Editar Usuário')

@section('content')

<div class="lp-page-header">
    <div>
        <h1 class="lp-page-title">Editar Usuário</h1>
        <p class="lp-page-subtitle">{{ $user->email }}</p>
    </div>
    <a href="{{ route('users.index') }}" class="btn-lp-secondary">
        <i class="bi bi-arrow-left"></i>
        Voltar
    </a>
</div>

<div class="lp-form-section" style="max-width:560px">
    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="lp-form-label" for="name">Nome</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name', $user->name) }}"
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
                value="{{ old('email', $user->email) }}"
                required
                class="lp-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                placeholder="usuario@exemplo.com"
            >
            @error('email')
                <div class="lp-invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div style="border-top:1px solid var(--lp-border);margin:.5rem 0 1.5rem;padding-top:1.5rem">
            <p style="font-size:12px;color:var(--lp-text-muted);margin-bottom:1rem">
                Deixe em branco para manter a senha atual.
            </p>

            <div class="mb-4">
                <label class="lp-form-label" for="password">Nova Senha</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="lp-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                    placeholder="Mínimo 8 caracteres"
                >
                @error('password')
                    <div class="lp-invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="lp-form-label" for="password_confirmation">Confirmar Nova Senha</label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    class="lp-input"
                    placeholder="Repita a nova senha"
                >
            </div>
        </div>

        @if($user->id !== auth()->id())
            <div class="mb-4">
                <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-size:13.5px;color:var(--lp-text)">
                    <input
                        type="checkbox"
                        name="is_admin"
                        value="1"
                        {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}
                        style="accent-color:var(--lp-blue);width:16px;height:16px;cursor:pointer"
                    >
                    <span>
                        Administrador
                        <span style="color:var(--lp-text-muted);font-size:12px;margin-left:.3rem">— acesso ao CRUD de usuários</span>
                    </span>
                </label>
            </div>
        @else
            <div class="mb-4" style="background:rgba(88,166,255,.06);border:1px solid rgba(88,166,255,.2);border-radius:7px;padding:.75rem 1rem;font-size:12.5px;color:var(--lp-text-muted)">
                <i class="bi bi-info-circle me-1" style="color:var(--lp-blue)"></i>
                Você não pode alterar sua própria role de administrador.
            </div>
        @endif

        <div style="display:flex;gap:.75rem;padding-top:.5rem">
            <button type="submit" class="btn-lp-primary">
                <i class="bi bi-check-lg"></i>
                Salvar Alterações
            </button>
            <a href="{{ route('users.index') }}" class="btn-lp-secondary">Cancelar</a>
        </div>
    </form>
</div>

@endsection
