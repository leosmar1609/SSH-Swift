<div class="col-md-6 col-xl-4">
    <div class="lp-card h-100 d-flex flex-column">

        <div class="lp-card-body flex-grow-1">

            {{-- Header do card --}}
            <div class="d-flex align-items-flex-start justify-content-between gap-2 mb-3">
                <div class="d-flex align-items-center gap-2" style="min-width:0">
                    <div style="
                        width:34px;height:34px;border-radius:8px;
                        background:linear-gradient(135deg,#1a2a4a,#1f3566);
                        border:1px solid rgba(88,166,255,.2);
                        display:flex;align-items:center;justify-content:center;
                        flex-shrink:0
                    ">
                        <i class="bi bi-hdd-fill" style="color:var(--lp-blue);font-size:15px"></i>
                    </div>
                    <div style="min-width:0">
                        <div style="font-weight:600;font-size:14.5px;color:var(--lp-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ $connection->name }}
                        </div>
                        <div style="font-size:11.5px;color:var(--lp-text-muted);font-family:'JetBrains Mono',monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ $connection->host }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Detalhes --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.55rem">

                <div style="background:var(--lp-bg);border:1px solid var(--lp-border-muted);border-radius:6px;padding:.5rem .75rem">
                    <div style="font-size:10.5px;color:var(--lp-text-subtle);margin-bottom:.15rem;font-weight:500;text-transform:uppercase;letter-spacing:.5px">
                        Usuário
                    </div>
                    <div style="font-size:13px;color:var(--lp-text);font-family:'JetBrains Mono',monospace;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        {{ $connection->username }}
                    </div>
                </div>

                <div style="background:var(--lp-bg);border:1px solid var(--lp-border-muted);border-radius:6px;padding:.5rem .75rem">
                    <div style="font-size:10.5px;color:var(--lp-text-subtle);margin-bottom:.15rem;font-weight:500;text-transform:uppercase;letter-spacing:.5px">
                        Porta
                    </div>
                    <div style="font-size:13px;color:var(--lp-text);font-family:'JetBrains Mono',monospace;font-weight:500">
                        {{ $connection->port }}
                    </div>
                </div>

            </div>

            {{-- Método de autenticação --}}
            <div class="mt-2" style="display:flex;align-items:center;gap:.4rem">
                @if($connection->auth_type === 'password')
                    <span style="font-size:11.5px;color:#3fb950;display:inline-flex;align-items:center;gap:.3rem">
                        <i class="bi bi-shield-lock-fill"></i> Senha configurada
                    </span>
                @elseif($connection->ssh_key_path)
                    <span style="font-size:11.5px;color:#3fb950;display:inline-flex;align-items:center;gap:.3rem">
                        <i class="bi bi-key-fill"></i> Chave SSH configurada
                    </span>
                @else
                    <span style="font-size:11.5px;color:var(--lp-orange);display:inline-flex;align-items:center;gap:.3rem">
                        <i class="bi bi-exclamation-triangle-fill"></i> Sem credencial configurada
                    </span>
                @endif

                @if($connection->startup_script)
                    <span style="margin-left:auto;font-size:11px;color:var(--lp-text-subtle);display:inline-flex;align-items:center;gap:.3rem">
                        <i class="bi bi-terminal"></i>
                        {{ count($connection->startup_script_lines) }} {{ count($connection->startup_script_lines) === 1 ? 'cmd' : 'cmds' }}
                    </span>
                @endif
            </div>

        </div>

        {{-- Footer com ações --}}
        <div class="lp-card-footer" style="gap:.5rem">
            <a href="{{ route('explorer.show', $connection) }}" class="btn-lp-primary" style="flex:1;justify-content:center">
                <i class="bi bi-folder2-open"></i>
                Explorer
            </a>

            {{-- Terminal (abre em nova aba, PTY interativo) --}}
            <a
                href="{{ route('connections.terminal', $connection) }}"
                target="_blank"
                rel="noopener"
                class="btn-lp-secondary lp-icon-btn-sm"
                title="Abrir terminal em nova aba"
            >
                <i class="bi bi-terminal-fill"></i>
            </a>

            {{-- Testar --}}
            <button
                type="button"
                class="btn-lp-secondary lp-icon-btn-sm btn-test-conn"
                title="Testar conexão"
                data-connection-id="{{ $connection->id }}"
                data-test-url="{{ route('connections.test', $connection) }}"
            >
                <i class="bi bi-plug-fill"></i>
            </button>

            {{-- Dropdown de ações --}}
            <div class="dropdown">
                <button type="button" class="btn-lp-secondary lp-icon-btn-sm" data-bs-toggle="dropdown" aria-expanded="false" title="Mais ações">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end lp-dropdown-menu">
                    <li>
                        <a class="lp-dropdown-item" href="{{ route('connections.edit', $connection) }}">
                            <i class="bi bi-pencil-fill"></i> Editar
                        </a>
                    </li>
                    <li>
                        <button type="button" class="lp-dropdown-item btn-duplicate-conn" data-duplicate-url="{{ route('connections.duplicate', $connection) }}">
                            <i class="bi bi-copy"></i> Duplicar
                        </button>
                    </li>
                    <li><hr class="dropdown-divider" style="border-color:var(--lp-border-muted);margin:.25rem 0"></li>
                    <li>
                        <button
                            type="button"
                            class="lp-dropdown-item lp-dropdown-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#modalDelete"
                            data-name="{{ $connection->name }}"
                            data-action="{{ route('connections.destroy', $connection) }}"
                        >
                            <i class="bi bi-trash3-fill"></i> Excluir
                        </button>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</div>

{{-- Modal de teste de conexão --}}
<div class="modal fade lp-modal" id="testModal-{{ $connection->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size:15px;font-weight:600">
                    <i class="bi bi-plug-fill me-2" style="color:var(--lp-blue)"></i>
                    Testando: {{ $connection->name }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="testResult-{{ $connection->id }}" class="lp-test-result">
                    <span style="color:var(--lp-text-subtle)">
                        <i class="bi bi-arrow-repeat lp-spin me-1"></i> Conectando…
                    </span>
                </div>
            </div>
            <div class="modal-footer gap-2">
                <a href="{{ route('explorer.show', $connection) }}" class="btn-lp-primary" id="btnOpenExplorer-{{ $connection->id }}" style="display:none">
                    <i class="bi bi-terminal-fill"></i> Abrir Explorer
                </a>
                <button type="button" class="btn-lp-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
