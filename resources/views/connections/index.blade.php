@extends('layouts.app')

@section('title', 'Servidores')

@section('content')

<div class="lp-page-header">
    <div>
        <h1 class="lp-page-title">Servidores</h1>
        <p class="lp-page-subtitle">
            {{ $connections->count() }} {{ $connections->count() === 1 ? 'conexão cadastrada' : 'conexões cadastradas' }}
        </p>
    </div>
    <a href="{{ route('connections.create') }}" class="btn-lp-primary">
        <i class="bi bi-plus-lg"></i>
        Nova Conexão
    </a>
</div>

@if($connections->isEmpty())
    <div class="lp-empty">
        <i class="bi bi-hdd-network lp-empty-icon"></i>
        <h3>Nenhum servidor cadastrado</h3>
        <p class="mb-1_5">Adicione sua primeira conexão SSH para começar a gerenciar servidores.</p>
        <a href="{{ route('connections.create') }}" class="btn-lp-primary mt-3">
            <i class="bi bi-plus-lg"></i>
            Nova Conexão
        </a>
    </div>
@else
    <div class="row g-3">
        @foreach($connections as $connection)
            @include('components.connection-card', ['connection' => $connection])
        @endforeach
    </div>
@endif

{{-- Modal: confirmar exclusão --}}
<div class="modal fade lp-modal" id="modalDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-weight:600;font-size:15px">
                    <i class="bi bi-trash3 text-danger me-2"></i>Remover Conexão
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1" style="color:var(--lp-text-muted);font-size:13.5px">
                    Tem certeza que deseja remover a conexão
                    <strong class="text-white" id="deleteConnectionName">—</strong>?
                </p>
                <p class="mb-0" style="color:var(--lp-text-subtle);font-size:12px">
                    A chave SSH associada também será excluída permanentemente.
                </p>
            </div>
            <div class="modal-footer gap-2">
                <button type="button" class="btn-lp-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-lp-danger">
                        <i class="bi bi-trash3"></i> Remover
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Modal de exclusão
    const modalDelete = document.getElementById('modalDelete');
    modalDelete.addEventListener('show.bs.modal', (e) => {
        const btn = e.relatedTarget;
        document.getElementById('deleteConnectionName').textContent = btn.dataset.name;
        document.getElementById('deleteForm').action = btn.dataset.action;
    });

    // Botão de teste rápido de conexão (ícone de plug)
    document.querySelectorAll('.btn-test-conn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id        = btn.dataset.connectionId;
            const testUrl   = btn.dataset.testUrl;
            const modal     = new bootstrap.Modal(document.getElementById(`testModal-${id}`));
            const result    = document.getElementById(`testResult-${id}`);
            const btnExplorer = document.getElementById(`btnOpenExplorer-${id}`);

            result.className  = 'lp-test-result';
            result.innerHTML  = '<span style="color:var(--lp-text-subtle)"><i class="bi bi-arrow-repeat lp-spin me-1"></i> Verificando conexão…</span>';
            if (btnExplorer) btnExplorer.style.display = 'none';

            modal.show();

            try {
                const { data } = await axios.post(testUrl);

                if (data.success) {
                    result.classList.add('success');
                    result.innerHTML = `
                        <div style="color:#3fb950;font-weight:600;margin-bottom:.5rem">
                            <i class="bi bi-check-circle-fill me-1"></i> ${data.message}
                        </div>
                        ${data.directory
                            ? `<div style="color:var(--lp-text-muted)">Diretório: <span style="color:var(--lp-blue);font-family:'JetBrains Mono',monospace">${data.directory}</span></div>`
                            : ''
                        }
                    `;
                    if (btnExplorer) btnExplorer.style.display = 'inline-flex';
                } else {
                    result.classList.add('error');
                    result.innerHTML = `<div style="color:var(--lp-red);font-weight:600"><i class="bi bi-x-circle-fill me-1"></i> ${data.message}</div>`;
                }
            } catch (err) {
                const msg = err.response?.data?.message || 'Erro de comunicação. Tente novamente.';
                result.classList.add('error');
                result.innerHTML = `<div style="color:var(--lp-red);font-weight:600"><i class="bi bi-x-circle-fill me-1"></i> ${msg}</div>`;
            }
        });
    });
</script>
@endpush
