@extends('layouts.app')

@section('title', 'Editar — ' . $connection->name)

@section('content')

<div class="lp-page-header">
    <div>
        <a href="{{ route('connections.index') }}"
           style="color:var(--lp-text-muted);text-decoration:none;font-size:13px;display:inline-flex;align-items:center;gap:.3rem;margin-bottom:.6rem">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
        <h1 class="lp-page-title">Editar Conexão</h1>
        <p class="lp-page-subtitle">{{ $connection->name }}</p>
    </div>
</div>

<form action="{{ route('connections.update', $connection) }}" method="POST" enctype="multipart/form-data" id="editForm">
    @csrf
    @method('PUT')

    <div class="lp-form-section">

        {{-- Identificação --}}
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
                    value="{{ old('name', $connection->name) }}"
                    autofocus
                >
                @error('name')
                    <div class="lp-invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <hr style="border-color:var(--lp-border-muted);margin:1.5rem 0">

        {{-- Acesso SSH --}}
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
                        value="{{ old('host', $connection->host) }}"
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
                        value="{{ old('port', $connection->port) }}"
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
                        value="{{ old('username', $connection->username) }}"
                    >
                    @error('username')
                        <div class="lp-invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mt-3">
                <label class="lp-form-label" for="ssh_key">
                    Chave Privada SSH
                    @if($connection->ssh_key_path)
                        <span class="lp-badge ms-2" style="font-size:10.5px">
                            <i class="bi bi-key-fill"></i> Chave cadastrada
                        </span>
                    @endif
                </label>

                @if($connection->ssh_key_path)
                    <div class="lp-alert lp-alert-warning mb-2" style="padding:.65rem .9rem;font-size:12.5px">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Envie um novo arquivo apenas para <strong>substituir</strong> a chave atual. Deixe em branco para manter.
                    </div>
                @endif

                <input
                    type="file"
                    id="ssh_key"
                    name="ssh_key"
                    class="lp-file-input {{ $errors->has('ssh_key') ? 'is-invalid' : '' }}"
                    accept=".pem,.ppk,.key,application/octet-stream"
                >
                @error('ssh_key')
                    <div class="lp-invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <hr style="border-color:var(--lp-border-muted);margin:1.5rem 0">

        {{-- Startup Script --}}
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
            >{{ old('startup_script', $connection->startup_script) }}</textarea>
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

        <div class="ms-auto d-flex gap-2">
            <a href="{{ route('connections.index') }}" class="btn-lp-secondary">Cancelar</a>
            <button type="submit" class="btn-lp-primary">
                <i class="bi bi-floppy2-fill"></i>
                Salvar Alterações
            </button>
        </div>
    </div>

    {{-- Resultado do teste --}}
    <div id="testResultBox" class="lp-test-result mt-3" style="display:none"></div>

</form>

@endsection

@push('scripts')
<script>
    document.getElementById('btnTest').addEventListener('click', async () => {
        const btn = document.getElementById('btnTest');
        const box = document.getElementById('testResultBox');

        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat lp-spin"></i> Testando…';
        box.style.display = 'none';
        box.className = 'lp-test-result mt-3';
        box.innerHTML = '';

        try {
            const { data } = await axios.post('{{ route('connections.test', $connection) }}');

            box.style.display = 'block';

            if (data.success) {
                box.classList.add('success');
                box.innerHTML = `
                    <div style="color:#3fb950;font-weight:600;margin-bottom:.4rem">
                        <i class="bi bi-check-circle-fill me-1"></i> ${data.message}
                    </div>
                    ${data.directory ? `<div style="color:var(--lp-text-muted)">Diretório atual: <span style="color:var(--lp-blue);font-family:'JetBrains Mono',monospace">${data.directory}</span></div>` : ''}
                `;
            } else {
                box.classList.add('error');
                box.innerHTML = `
                    <div style="color:var(--lp-red);font-weight:600">
                        <i class="bi bi-x-circle-fill me-1"></i> ${data.message}
                    </div>
                `;
            }
        } catch (err) {
            const msg = err.response?.data?.message || 'Erro de comunicação com o servidor. Tente novamente.';
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
