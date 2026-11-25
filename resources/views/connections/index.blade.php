@extends('layouts.app')

@section('title', 'Servidores')

@push('styles')
<style>
    /* ── Cabeçalho ── */
    .lp-srv-hero { display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 1rem 1.5rem; margin-bottom: 1.5rem; }
    .lp-srv-eyebrow { display: inline-flex; align-items: center; gap: .45rem; font-size: 11.5px; font-weight: 600; letter-spacing: .8px; text-transform: uppercase; color: #a5b4fc; margin-bottom: .45rem; }
    .lp-srv-eyebrow .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--lp-cyan); box-shadow: 0 0 0 0 rgba(34,211,238,.6); animation: lp-ping 2.2s infinite; }
    @keyframes lp-ping { 0% { box-shadow: 0 0 0 0 rgba(34,211,238,.55); } 80%, 100% { box-shadow: 0 0 0 9px rgba(34,211,238,0); } }
    .lp-srv-count { display: inline-flex; align-items: center; justify-content: center; min-width: 26px; height: 22px; padding: 0 .5rem; margin-left: .5rem; border-radius: 999px; font-size: 12px; font-weight: 700; color: #fff; background: var(--lp-grad); vertical-align: middle; -webkit-text-fill-color: #fff; }
    .lp-srv-tools { display: flex; gap: .6rem; flex-wrap: wrap; align-items: center; }
    .lp-search { position: relative; margin-bottom: 1.5rem; max-width: 420px; }
    .lp-search i { z-index: 2; position: absolute; left: .9rem; top: 50%; transform: translateY(-50%); color: var(--lp-text-subtle); pointer-events: none; transition: color .2s; }
    .lp-search .lp-input { padding-left: 2.4rem; border-radius: 999px; background: rgba(255,255,255,.04); -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px); }
    .lp-search:focus-within i { color: #a5b4fc; }
    .lp-search kbd { position: absolute; right: .7rem; top: 50%; transform: translateY(-50%); font-size: 10.5px; color: var(--lp-text-subtle); background: rgba(255,255,255,.06); border: 1px solid var(--lp-border); border-radius: 6px; padding: .05rem .4rem; font-family: 'JetBrains Mono', monospace; }
    .lp-no-results { display: none; text-align: center; padding: 3rem 1rem; color: var(--lp-text-muted); }

    /* ── Cartão do servidor: vidro espelhado ── */
    .lp-srv-col { position: relative; animation: lp-rise .6s var(--lp-ease) backwards; animation-delay: calc(var(--i, 0) * 60ms + 120ms); }
    .lp-srv-col:hover, .lp-srv-col:focus-within, .lp-srv-col:has(.dropdown-menu.show) { z-index: 5; }

    .lp-srv {
        --rx: 0deg; --ry: 0deg; --lift: 0px;
        --c1: hsl(var(--hue) 90% 65%);
        --c2: hsl(calc(var(--hue) + 60) 85% 62%);
        position: relative;
        height: 100%;
        display: flex;
        flex-direction: column;
        border-radius: 20px;
        background:
            linear-gradient(155deg, rgba(255,255,255,.11) 0%, rgba(255,255,255,.035) 38%, rgba(255,255,255,.015) 62%, rgba(255,255,255,.05) 100%),
            radial-gradient(120% 80% at 0% 0%, hsl(var(--hue) 80% 55% / .16), transparent 60%);
        -webkit-backdrop-filter: blur(18px) saturate(160%);
                backdrop-filter: blur(18px) saturate(160%);
        border: 1px solid rgba(255,255,255,.1);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.16), inset 0 -1px 0 rgba(255,255,255,.03), 0 12px 32px -16px rgba(0,0,0,.8);
        transform: perspective(1000px) rotateX(var(--rx)) rotateY(var(--ry)) translateY(var(--lift));
        transition: transform .35s var(--lp-ease), box-shadow .35s, border-color .35s;
    }
    .lp-srv.is-tilting { transition: transform .08s linear, box-shadow .35s, border-color .35s; }
    .lp-srv:hover {
        --lift: -6px;
        border-color: transparent;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.2), 0 26px 50px -20px hsl(var(--hue) 80% 40% / .75), 0 0 0 1px hsl(var(--hue) 80% 65% / .15);
    }

    /* Borda em gradiente girando (aparece no hover) */
    .lp-srv::after {
        content: '';
        position: absolute;
        inset: -1px;
        border-radius: inherit;
        padding: 1px;
        background: conic-gradient(from var(--lp-ang), var(--c1), #6366f1, var(--c2), #22d3ee, var(--c1));
        -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
        -webkit-mask-composite: xor;
                mask-composite: exclude;
        opacity: 0;
        transition: opacity .35s;
        animation: lp-ang-spin 5s linear infinite;
        pointer-events: none;
    }
    .lp-srv:hover::after { opacity: 1; }
    @keyframes lp-ang-spin { to { --lp-ang: 360deg; } }

    /* Reflexo espelhado fixo + brilho que segue o mouse + faixa de luz */
    .lp-srv-fx { position: absolute; inset: 0; border-radius: inherit; overflow: hidden; pointer-events: none; }
    .lp-srv-fx::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(118deg, rgba(255,255,255,.16) 0%, rgba(255,255,255,.04) 26%, transparent 27%, transparent 100%);
        opacity: .7;
    }
    .lp-srv-glare {
        position: absolute;
        inset: 0;
        background: radial-gradient(380px circle at var(--mx, 50%) var(--my, -20%), rgba(255,255,255,.16), transparent 45%);
        opacity: 0;
        transition: opacity .35s;
    }
    .lp-srv:hover .lp-srv-glare { opacity: 1; }
    .lp-srv-sweep {
        position: absolute;
        top: 0; bottom: 0;
        left: -60%;
        width: 50%;
        background: linear-gradient(100deg, transparent, rgba(255,255,255,.14), transparent);
        transform: skewX(-18deg);
        transition: left .9s var(--lp-ease);
    }
    .lp-srv:hover .lp-srv-sweep { left: 130%; }

    .lp-srv-body { position: relative; flex: 1; padding: 1.35rem 1.4rem 1.1rem; }
    .lp-srv-head { display: flex; align-items: center; gap: .8rem; margin-bottom: 1.1rem; min-width: 0; }
    .lp-srv-avatar {
        position: relative;
        width: 44px; height: 44px;
        flex-shrink: 0;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 19px;
        color: hsl(var(--hue) 95% 80%);
        background: linear-gradient(145deg, hsl(var(--hue) 85% 55% / .35), hsl(calc(var(--hue) + 60) 85% 50% / .18));
        border: 1px solid hsl(var(--hue) 90% 70% / .35);
        box-shadow: 0 10px 24px -10px hsl(var(--hue) 85% 50% / .9), inset 0 1px 0 rgba(255,255,255,.25);
        transition: transform .45s var(--lp-ease);
    }
    .lp-srv:hover .lp-srv-avatar { transform: translateZ(30px) rotate(-6deg) scale(1.06); }
    .lp-srv-title { min-width: 0; }
    .lp-srv-title h3 { font-size: 15.5px; font-weight: 700; margin: 0; letter-spacing: -.2px; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lp-srv-host { display: flex; align-items: center; gap: .35rem; font-size: 12px; color: var(--lp-text-muted); font-family: 'JetBrains Mono', monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: .1rem; }
    .lp-srv-host i { font-size: 11px; color: hsl(var(--hue) 80% 70%); }

    .lp-srv-meta { display: grid; grid-template-columns: 1fr 1fr; gap: .55rem; }
    .lp-srv-chip { min-width: 0; padding: .55rem .75rem; border-radius: 12px; background: rgba(5,8,16,.38); border: 1px solid rgba(255,255,255,.06); transition: border-color .3s, background .3s; }
    .lp-srv:hover .lp-srv-chip { border-color: rgba(255,255,255,.1); background: rgba(5,8,16,.5); }
    .lp-srv-chip span { display: block; font-size: 10px; font-weight: 600; letter-spacing: .7px; text-transform: uppercase; color: var(--lp-text-subtle); }
    .lp-srv-chip strong { display: block; font-size: 13px; font-weight: 500; font-family: 'JetBrains Mono', monospace; color: var(--lp-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .lp-srv-status { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .85rem; }
    .lp-srv-tag { display: inline-flex; align-items: center; gap: .35rem; font-size: 11.5px; font-weight: 500; padding: .18rem .6rem; border-radius: 999px; color: var(--lp-text-muted); background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.07); }
    .lp-srv-tag.is-ok   { color: #6ee7b7; background: rgba(52,211,153,.09); border-color: rgba(52,211,153,.22); }
    .lp-srv-tag.is-warn { color: #fcd34d; background: rgba(251,191,36,.09); border-color: rgba(251,191,36,.25); }

    .lp-srv-actions { position: relative; display: flex; align-items: center; gap: .5rem; padding: .9rem 1.4rem 1.2rem; border-top: 1px solid rgba(255,255,255,.06); }
</style>
@endpush

@section('content')

<div class="lp-srv-hero">
    <div>
        <div class="lp-srv-eyebrow"><span class="dot"></span> Workspace SSH</div>
        <h1 class="lp-page-title">Servidores<span class="lp-srv-count">{{ $connections->count() }}</span></h1>
        <p class="lp-page-subtitle">
            {{ $connections->count() }} {{ $connections->count() === 1 ? 'conexão cadastrada' : 'conexões cadastradas' }}
        </p>
    </div>
    <div class="lp-srv-tools">
        <button type="button" class="btn-lp-secondary" data-bs-toggle="modal" data-bs-target="#quickConnectModal">
            <i class="bi bi-lightning-charge-fill"></i>
            Conectar por IP
        </button>
        <a href="{{ route('connections.create') }}" class="btn-lp-primary">
            <i class="bi bi-plus-lg"></i>
            Nova Conexão
        </a>
    </div>
</div>

@if($connections->count() > 3)
    <div class="lp-search">
        <i class="bi bi-search"></i>
        <input type="search" id="srvSearch" class="lp-input" placeholder="Buscar servidor por nome, IP ou usuário…" autocomplete="off" spellcheck="false">
        <kbd>/</kbd>
    </div>
@endif

@include('components.quick-connect-modal')

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
    <div class="row g-4" id="srvGrid">
        @foreach($connections as $connection)
            @include('components.connection-card', ['connection' => $connection, 'i' => $loop->index])
        @endforeach
    </div>
    <div class="lp-no-results" id="srvNoResults">
        <i class="bi bi-search" style="font-size:1.6rem;display:block;margin-bottom:.5rem;color:var(--lp-text-subtle)"></i>
        Nenhum servidor encontrado com esse termo.
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
    // ── Efeito 3D + reflexo que segue o mouse nos cartões ──
    (() => {
        const canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        const calm     = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (!canHover) return;

        document.querySelectorAll('.lp-srv').forEach(card => {
            let frame = null;
            card.addEventListener('pointermove', e => {
                if (frame) return;
                frame = requestAnimationFrame(() => {
                    frame = null;
                    const r = card.getBoundingClientRect();
                    const x = (e.clientX - r.left) / r.width;
                    const y = (e.clientY - r.top) / r.height;
                    card.style.setProperty('--mx', `${x * 100}%`);
                    card.style.setProperty('--my', `${y * 100}%`);
                    if (calm) return;
                    card.classList.add('is-tilting');
                    card.style.setProperty('--rx', `${(0.5 - y) * 7}deg`);
                    card.style.setProperty('--ry', `${(x - 0.5) * 9}deg`);
                });
            });
            card.addEventListener('pointerleave', () => {
                card.classList.remove('is-tilting');
                card.style.setProperty('--rx', '0deg');
                card.style.setProperty('--ry', '0deg');
            });
        });
    })();

    // ── Busca instantânea ──
    (() => {
        const input = document.getElementById('srvSearch');
        if (!input) return;
        const cols  = [...document.querySelectorAll('.lp-srv-col')];
        const empty = document.getElementById('srvNoResults');
        const norm  = s => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
        const keys  = cols.map(c => norm(c.dataset.search || ''));

        input.addEventListener('input', () => {
            const q = norm(input.value.trim());
            let shown = 0;
            cols.forEach((c, i) => {
                const ok = !q || keys[i].includes(q);
                c.style.display = ok ? '' : 'none';
                if (ok) shown++;
            });
            empty.style.display = shown ? 'none' : 'block';
        });

        // "/" foca a busca (fora de campos de texto)
        document.addEventListener('keydown', e => {
            if (e.key === '/' && !/^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName)) {
                e.preventDefault();
                input.focus();
            }
            if (e.key === 'Escape' && document.activeElement === input) {
                input.value = '';
                input.dispatchEvent(new Event('input'));
                input.blur();
            }
        });
    })();

    // Modal de exclusão
    const modalDelete = document.getElementById('modalDelete');
    modalDelete.addEventListener('show.bs.modal', (e) => {
        const btn = e.relatedTarget;
        document.getElementById('deleteConnectionName').textContent = btn.dataset.name;
        document.getElementById('deleteForm').action = btn.dataset.action;
    });

    // Botão de duplicar conexão
    document.querySelectorAll('.btn-duplicate-conn').forEach(btn => {
        btn.addEventListener('click', () => {
            const f = document.createElement('form');
            f.method = 'POST';
            f.action = btn.dataset.duplicateUrl;
            f.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}">`;
            document.body.appendChild(f);
            f.submit();
        });
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
