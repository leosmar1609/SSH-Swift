@php
    // Cor própria e estável para cada servidor (derivada do nome).
    $hue = crc32((string) $connection->name) % 360;
    $search = mb_strtolower($connection->name . ' ' . $connection->host . ' ' . $connection->username);
@endphp
<div class="col-md-6 col-xl-4 lp-srv-col" data-search="{{ $search }}" style="--i: {{ $i ?? 0 }}">
    <article class="lp-srv" style="--hue: {{ $hue }}">

        {{-- Camadas de efeito: reflexo espelhado + brilho que segue o mouse --}}
        <div class="lp-srv-fx" aria-hidden="true">
            <span class="lp-srv-glare"></span>
            <span class="lp-srv-sweep"></span>
        </div>

        <div class="lp-srv-body">
            <header class="lp-srv-head">
                <div class="lp-srv-avatar">
                    <i class="bi bi-hdd-rack-fill"></i>
                </div>
                <div class="lp-srv-title">
                    <h3 title="{{ $connection->name }}">{{ $connection->name }}</h3>
                    <div class="lp-srv-host" title="{{ $connection->host }}">
                        <i class="bi bi-globe2"></i>{{ $connection->host }}
                    </div>
                </div>
            </header>

            <div class="lp-srv-meta">
                <div class="lp-srv-chip">
                    <span>Usuário</span>
                    <strong title="{{ $connection->username }}">{{ $connection->username }}</strong>
                </div>
                <div class="lp-srv-chip">
                    <span>Porta</span>
                    <strong>{{ $connection->port }}</strong>
                </div>
            </div>

            <div class="lp-srv-status">
                @if($connection->auth_type === 'password')
                    <span class="lp-srv-tag is-ok"><i class="bi bi-shield-lock-fill"></i> Senha configurada</span>
                @elseif($connection->ssh_key_path)
                    <span class="lp-srv-tag is-ok"><i class="bi bi-key-fill"></i> Chave SSH</span>
                @else
                    <span class="lp-srv-tag is-warn"><i class="bi bi-exclamation-triangle-fill"></i> Sem credencial</span>
                @endif

                @if($connection->startup_script)
                    <span class="lp-srv-tag" title="Comandos executados ao conectar">
                        <i class="bi bi-terminal"></i>
                        {{ count($connection->startup_script_lines) }} {{ count($connection->startup_script_lines) === 1 ? 'cmd' : 'cmds' }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Ações --}}
        <footer class="lp-srv-actions">
            <a href="{{ route('explorer.show', $connection) }}" class="btn-lp-primary" style="flex:1;justify-content:center">
                <i class="bi bi-folder2-open"></i>
                Explorer
            </a>

            {{-- Terminal (abre em nova aba, PTY interativo) — só ADM --}}
            @if(auth()->user()->is_admin)
            <a
                href="{{ route('connections.terminal', $connection) }}"
                target="_blank"
                rel="noopener"
                class="btn-lp-secondary lp-icon-btn-sm"
                title="Abrir terminal em nova aba"
            >
                <i class="bi bi-terminal-fill"></i>
            </a>
            @endif

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
        </footer>

    </article>
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
