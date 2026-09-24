@extends('layouts.app')

@section('title', 'Nova Conexão')

@section('content')

<div class="lp-page-header">
    <div>
        <a href="{{ route('connections.index') }}"
           style="color:var(--lp-text-muted);text-decoration:none;font-size:13px;display:inline-flex;align-items:center;gap:.3rem;margin-bottom:.6rem">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
        <h1 class="lp-page-title">Nova Conexão SSH</h1>
        <p class="lp-page-subtitle">Cadastre um servidor Linux para gerenciamento via SSH.</p>
    </div>
</div>

<form action="{{ route('connections.store') }}" method="POST" enctype="multipart/form-data" id="connectionForm">
    @csrf

    <div class="lp-form-section">

        {{-- Seção: Identificação --}}
        <div class="mb-4">
            <h6 style="color:var(--lp-text-muted);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:1.1rem">
                Identificação
            </h6>

            <div class="mb-3">
                <label class="lp-form-label" for="name">Nome da Conexão</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    class="lp-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                    value="{{ old('name') }}"
                    placeholder="Ex: Servidor de Produção"
                    autofocus
                >
                @error('name')
                    <div class="lp-invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <hr style="border-color:var(--lp-border-muted);margin:1.5rem 0">

        {{-- Seção: Acesso --}}
        <div class="mb-4">
            <h6 style="color:var(--lp-text-muted);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:1.1rem">
                Acesso SSH
            </h6>

            <div class="row g-3">
                <div class="col-md-7">
                    <label class="lp-form-label" for="host">Host / IP</label>
                    <input
                        type="text"
                        id="host"
                        name="host"
                        class="lp-input {{ $errors->has('host') ? 'is-invalid' : '' }}"
                        value="{{ old('host') }}"
                        placeholder="192.168.1.10 ou server.dominio.com"
                    >
                    @error('host')
                        <div class="lp-invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-2">
                    <label class="lp-form-label" for="port">Porta</label>
                    <input
                        type="number"
                        id="port"
                        name="port"
                        class="lp-input {{ $errors->has('port') ? 'is-invalid' : '' }}"
                        value="{{ old('port', 22) }}"
                        min="1"
                        max="65535"
                    >
                    @error('port')
                        <div class="lp-invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="lp-form-label" for="username">Usuário SSH</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="lp-input {{ $errors->has('username') ? 'is-invalid' : '' }}"
                        value="{{ old('username') }}"
                        placeholder="root"
                    >
                    @error('username')
                        <div class="lp-invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mt-3">
                <label class="lp-form-label">Método de Autenticação</label>
                <div class="lp-auth-toggle" role="group">
                    <label class="lp-auth-toggle-option">
                        <input type="radio" name="auth_type" value="key" {{ old('auth_type', 'key') === 'key' ? 'checked' : '' }}>
                        <i class="bi bi-key-fill"></i> Chave SSH
                    </label>
                    <label class="lp-auth-toggle-option">
                        <input type="radio" name="auth_type" value="password" {{ old('auth_type') === 'password' ? 'checked' : '' }}>
                        <i class="bi bi-shield-lock-fill"></i> Usuário e Senha
                    </label>
                </div>
                @error('auth_type')
                    <div class="lp-invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mt-3" id="authFieldKey">
                <label class="lp-form-label" for="ssh_key">Chave Privada SSH</label>
                <input
                    type="file"
                    id="ssh_key"
                    name="ssh_key"
                    class="lp-file-input {{ $errors->has('ssh_key') ? 'is-invalid' : '' }}"
                    accept=".pem,.ppk,.key,application/octet-stream"
                >
                <div class="lp-form-hint">
                    <i class="bi bi-shield-lock me-1"></i>
                    Aceito: <code style="background:rgba(255,255,255,.06);padding:.1rem .3rem;border-radius:3px;font-size:11px">.pem</code>
                    <code style="background:rgba(255,255,255,.06);padding:.1rem .3rem;border-radius:3px;font-size:11px">.ppk</code>
                    <code style="background:rgba(255,255,255,.06);padding:.1rem .3rem;border-radius:3px;font-size:11px">id_rsa</code>
                    — A chave é armazenada de forma segura no servidor.
                </div>
                @error('ssh_key')
                    <div class="lp-invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mt-3" id="authFieldPassword" style="display:none">
                <label class="lp-form-label" for="password">Senha SSH</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="lp-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                    placeholder="Senha do usuário no servidor"
                    autocomplete="new-password"
                >
                @error('password')
                    <div class="lp-invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <hr style="border-color:var(--lp-border-muted);margin:1.5rem 0">

        {{-- Seção: Startup Script --}}
        <div class="mb-1">
            <h6 style="color:var(--lp-text-muted);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:1.1rem">
                Startup Script <span style="font-weight:400;text-transform:none;letter-spacing:0;margin-left:.3rem;color:var(--lp-text-subtle)">(opcional)</span>
            </h6>

            <label class="lp-form-label" for="startup_script">
                Comandos executados automaticamente ao conectar
            </label>
            <textarea
                id="startup_script"
                name="startup_script"
                class="lp-input lp-textarea {{ $errors->has('startup_script') ? 'is-invalid' : '' }}"
                placeholder="sudo su -&#10;cd /var/www/html"
                rows="5"
            >{{ old('startup_script') }}</textarea>
            <div class="lp-form-hint">
                <i class="bi bi-info-circle me-1"></i>
                Uma instrução por linha. Executadas em sequência após a autenticação SSH.
            </div>
            @error('startup_script')
                <div class="lp-invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

    </div>

    {{-- Ações --}}
    <div class="d-flex align-items-center gap-3 mt-4">

        <button type="button" id="btnTest" class="btn-lp-secondary">
            <i class="bi bi-plug-fill"></i>
            Testar Conexão
        </button>

        <div id="testStatus" style="font-size:13px;color:var(--lp-text-muted)"></div>

        <div class="ms-auto d-flex gap-2">
            <a href="{{ route('connections.index') }}" class="btn-lp-secondary">Cancelar</a>
            <button type="submit" class="btn-lp-primary">
                <i class="bi bi-floppy2-fill"></i>
                Salvar Conexão
            </button>
        </div>
    </div>

    {{-- Resultado do teste --}}
    <div id="testResultBox" class="lp-test-result mt-3" style="display:none"></div>

</form>

@endsection

@push('scripts')
<script>
    const authFieldKey      = document.getElementById('authFieldKey');
    const authFieldPassword = document.getElementById('authFieldPassword');
    const sshKeyInput       = document.getElementById('ssh_key');
    const passwordInput     = document.getElementById('password');

    function syncAuthFields() {
        const authType = document.querySelector('input[name="auth_type"]:checked')?.value ?? 'key';
        const isPassword = authType === 'password';

        authFieldKey.style.display      = isPassword ? 'none' : '';
        authFieldPassword.style.display = isPassword ? '' : 'none';

        // Disable the hidden field's input so it's excluded from FormData/form submit —
        // otherwise both ssh_key and password would post together.
        sshKeyInput.disabled   = isPassword;
        passwordInput.disabled = !isPassword;
    }

    document.querySelectorAll('input[name="auth_type"]').forEach(el => {
        el.addEventListener('change', syncAuthFields);
    });
    syncAuthFields();

    document.getElementById('btnTest').addEventListener('click', async () => {
        const btn    = document.getElementById('btnTest');
        const status = document.getElementById('testStatus');
        const box    = document.getElementById('testResultBox');
        const form   = document.getElementById('connectionForm');

        const formData = new FormData(form);

        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat lp-spin"></i> Testando…';
        status.textContent = '';
        box.style.display  = 'none';
        box.className      = 'lp-test-result mt-3';
        box.innerHTML      = '';

        try {
            const { data } = await axios.post('{{ route('connections.test.live') ?? '' }}', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            box.style.display = 'block';
            box.classList.add('success');
            box.innerHTML = `
                <div style="color:#3fb950;font-weight:600;margin-bottom:.4rem">
                    <i class="bi bi-check-circle-fill me-1"></i> ${data.message}
                </div>
                ${data.directory ? `<div style="color:var(--lp-text-muted)">Diretório atual: <span style="color:var(--lp-blue)">${data.directory}</span></div>` : ''}
            `;
        } catch (err) {
            const msg = err.response?.data?.message || 'Falha ao conectar. Verifique os dados informados.';
            box.style.display = 'block';
            box.classList.add('error');
            box.innerHTML = `
                <div style="color:var(--lp-red);font-weight:600">
                    <i class="bi bi-x-circle-fill me-1"></i> ${msg}
                </div>
            `;
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plug-fill"></i> Testar Conexão';
        }
    });
</script>
@endpush
