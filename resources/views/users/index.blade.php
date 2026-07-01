@extends('layouts.app')

@section('title', 'Usuários')

@section('content')

<div class="lp-page-header">
    <div>
        <h1 class="lp-page-title">Usuários</h1>
        <p class="lp-page-subtitle">
            {{ $users->count() }} {{ $users->count() === 1 ? 'usuário cadastrado' : 'usuários cadastrados' }}
        </p>
    </div>
    <a href="{{ route('users.create') }}" class="btn-lp-primary">
        <i class="bi bi-plus-lg"></i>
        Novo Usuário
    </a>
</div>

@if($users->isEmpty())
    <div class="lp-empty">
        <i class="bi bi-people lp-empty-icon"></i>
        <h3>Nenhum usuário cadastrado</h3>
        <p class="mb-1_5">Adicione o primeiro usuário para começar.</p>
        <a href="{{ route('users.create') }}" class="btn-lp-primary mt-3">
            <i class="bi bi-plus-lg"></i>
            Novo Usuário
        </a>
    </div>
@else
    <div class="lp-form-section" style="padding:0;overflow:hidden">
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="border-bottom:1px solid var(--lp-border)">
                    <th style="padding:.85rem 1.5rem;font-size:12px;font-weight:600;color:var(--lp-text-muted);text-align:left;text-transform:uppercase;letter-spacing:.5px">Nome</th>
                    <th style="padding:.85rem 1.5rem;font-size:12px;font-weight:600;color:var(--lp-text-muted);text-align:left;text-transform:uppercase;letter-spacing:.5px">E-mail</th>
                    <th style="padding:.85rem 1.5rem;font-size:12px;font-weight:600;color:var(--lp-text-muted);text-align:left;text-transform:uppercase;letter-spacing:.5px">Perfil</th>
                    <th style="padding:.85rem 1.5rem;font-size:12px;font-weight:600;color:var(--lp-text-muted);text-align:left;text-transform:uppercase;letter-spacing:.5px">Servidores</th>
                    <th style="padding:.85rem 1.5rem;font-size:12px;font-weight:600;color:var(--lp-text-muted);text-align:center;text-transform:uppercase;letter-spacing:.5px">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr style="border-bottom:1px solid var(--lp-border-muted);{{ $loop->last ? 'border-bottom:none' : '' }}">
                        <td style="padding:.9rem 1.5rem;font-weight:500;color:var(--lp-text)">
                            <div style="display:flex;align-items:center;gap:.6rem">
                                <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--lp-blue-dim),var(--lp-blue));display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                {{ $user->name }}
                                @if($user->id === auth()->id())
                                    <span style="background:rgba(35,134,54,.15);color:#3fb950;font-size:10px;font-weight:600;padding:.1rem .4rem;border-radius:4px;border:1px solid rgba(35,134,54,.25)">VOCÊ</span>
                                @endif
                            </div>
                        </td>
                        <td style="padding:.9rem 1.5rem;color:var(--lp-text-muted);font-family:'JetBrains Mono',monospace;font-size:12.5px">{{ $user->email }}</td>
                        <td style="padding:.9rem 1.5rem">
                            @if($user->is_admin)
                                <span style="background:rgba(88,166,255,.1);color:var(--lp-blue);font-size:11px;font-weight:600;padding:.2rem .55rem;border-radius:20px;border:1px solid rgba(88,166,255,.2)">
                                    <i class="bi bi-shield-check me-1"></i>Admin
                                </span>
                            @else
                                <span style="background:rgba(255,255,255,.05);color:var(--lp-text-muted);font-size:11px;font-weight:500;padding:.2rem .55rem;border-radius:20px;border:1px solid var(--lp-border-muted)">
                                    <i class="bi bi-person me-1"></i>Usuário
                                </span>
                            @endif
                        </td>
                        <td style="padding:.9rem 1.5rem;color:var(--lp-text-muted);font-size:13px">
                            {{ $user->connections()->withoutGlobalScopes()->count() }}
                        </td>
                        <td style="padding:.9rem 1.5rem;text-align:center">
                            <div style="display:flex;align-items:center;justify-content:center;gap:.4rem">
                                <a href="{{ route('users.edit', $user) }}" class="btn-lp-secondary" style="padding:.3rem .65rem;font-size:12px">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if($user->id !== auth()->id())
                                    <button
                                        class="btn-lp-danger"
                                        style="padding:.3rem .65rem;font-size:12px"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalDelete"
                                        data-name="{{ $user->name }}"
                                        data-action="{{ route('users.destroy', $user) }}"
                                    ><i class="bi bi-trash3"></i></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- Modal exclusão --}}
<div class="modal fade lp-modal" id="modalDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-weight:600;font-size:15px">
                    <i class="bi bi-trash3 text-danger me-2"></i>Remover Usuário
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1" style="color:var(--lp-text-muted);font-size:13.5px">
                    Tem certeza que deseja remover o usuário
                    <strong class="text-white" id="deleteUserName">—</strong>?
                </p>
                <p class="mb-0" style="color:var(--lp-text-subtle);font-size:12px">
                    Todos os servidores e chaves SSH associadas serão excluídos permanentemente.
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
    document.getElementById('modalDelete').addEventListener('show.bs.modal', (e) => {
        const btn = e.relatedTarget;
        document.getElementById('deleteUserName').textContent = btn.dataset.name;
        document.getElementById('deleteForm').action = btn.dataset.action;
    });
</script>
@endpush
