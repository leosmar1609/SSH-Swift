<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $connection->name }} — TechIA Panel</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg:          #0d1117;
            --surface:     #161b22;
            --surface2:    #1c2128;
            --border:      #30363d;
            --border2:     #21262d;
            --text:        #e6edf3;
            --muted:       #8b949e;
            --subtle:      #6e7681;
            --blue:        #58a6ff;
            --blue-dim:    #1f6feb;
            --green:       #3fb950;
            --red:         #f85149;
            --orange:      #d29922;
            --yellow:      #e3b341;
            --purple:      #bc8cff;
            --sidebar-w:   260px;
            --tab-h:       36px;
            --toolbar-h:   34px;
            --statusbar-h: 24px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; overflow: hidden; background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; font-size: 13px; }

        /* ── IDE shell ── */
        #lp-ide { display: flex; flex-direction: column; height: 100vh; }

        /* ── Topbar ── */
        .lp-topbar { height: 44px; background: var(--surface); border-bottom: 1px solid var(--border); display: flex; align-items: center; padding: 0 14px; gap: 12px; flex-shrink: 0; z-index: 50; }
        .lp-topbar-brand { display: flex; align-items: center; gap: 6px; color: var(--text); text-decoration: none; font-weight: 700; font-size: 14px; }
        .lp-topbar-brand span { color: var(--blue); }
        .lp-topbar-sep { width: 1px; height: 18px; background: var(--border); }
        .lp-topbar-conn { display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--muted); }
        .lp-topbar-conn strong { color: var(--text); font-weight: 600; }
        .lp-topbar-right { margin-left: auto; display: flex; align-items: center; gap: 8px; }
        .lp-icon-btn { background: transparent; border: 1px solid var(--border); color: var(--muted); border-radius: 6px; padding: 4px 10px; font-size: 12.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: background .15s, color .15s; text-decoration: none; }
        .lp-icon-btn:hover { background: rgba(255,255,255,.07); color: var(--text); }

        /* ── Body ── */
        .lp-body { display: flex; flex: 1; overflow: hidden; }

        /* ── Sidebar ── */
        #sidebar { width: var(--sidebar-w); background: var(--surface); border-right: 1px solid var(--border); display: flex; flex-direction: column; overflow: hidden; flex-shrink: 0; }
        .lp-server-header { padding: 10px 12px 8px; border-bottom: 1px solid var(--border2); }
        .lp-server-name { font-weight: 600; font-size: 13px; color: var(--text); display: flex; align-items: center; gap: 6px; }
        .lp-conn-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--subtle); flex-shrink: 0; transition: background .3s; }
        .lp-conn-dot.online  { background: var(--green); box-shadow: 0 0 6px var(--green); }
        .lp-conn-dot.offline { background: var(--red); }
        .lp-server-meta { font-size: 11px; color: var(--muted); font-family: 'JetBrains Mono', monospace; margin-top: 2px; padding-left: 14px; }

        .lp-sidebar-actions { display: flex; gap: 4px; padding: 6px 10px; border-bottom: 1px solid var(--border2); }
        .lp-sidebar-search { padding: 6px 10px; border-bottom: 1px solid var(--border2); }
        .lp-sidebar-search input { width: 100%; background: var(--bg); border: 1px solid var(--border); color: var(--text); border-radius: 5px; padding: 5px 8px; font-size: 12px; outline: none; font-family: inherit; }
        .lp-sidebar-search input:focus { border-color: var(--blue); box-shadow: 0 0 0 2px rgba(88,166,255,.15); }
        .lp-sidebar-search input::placeholder { color: var(--subtle); }

        .lp-act-btn { flex: 1; background: var(--bg); border: 1px solid var(--border); color: var(--muted); border-radius: 5px; padding: 4px 6px; font-size: 11.5px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px; transition: background .12s, color .12s; }
        .lp-act-btn:hover { background: var(--surface2); color: var(--text); }

        .lp-sidebar-scroll { flex: 1; overflow-y: auto; overflow-x: hidden; }
        .lp-section { border-bottom: 1px solid var(--border2); }
        .lp-section-hdr { display: flex; align-items: center; gap: 5px; padding: 7px 10px 6px; font-size: 10.5px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .7px; cursor: pointer; user-select: none; }
        .lp-section-hdr:hover { color: var(--text); }
        .lp-section-hdr .lp-arrow { transition: transform .15s; font-size: 9px; }
        .lp-section-hdr.collapsed .lp-arrow { transform: rotate(-90deg); }
        .lp-section-body { padding-bottom: 4px; }
        .lp-section-body.hidden { display: none; }

        /* Tree */
        .lp-node { display: flex; align-items: center; cursor: pointer; user-select: none; position: relative; min-height: 22px; }
        .lp-node:hover > .lp-node-inner { background: rgba(255,255,255,.05); }
        .lp-node.active > .lp-node-inner { background: rgba(88,166,255,.12); }
        .lp-node-inner { display: flex; align-items: center; flex: 1; gap: 4px; padding: 1px 6px 1px 0; border-radius: 4px; overflow: hidden; }
        .lp-node-arrow { width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-size: 9px; color: var(--subtle); flex-shrink: 0; transition: transform .12s; }
        .lp-node-arrow.open { transform: rotate(90deg); }
        .lp-node-arrow.spacer { visibility: hidden; }
        .lp-node-icon { font-size: 13px; flex-shrink: 0; width: 16px; text-align: center; }
        .lp-node-name { font-size: 12.5px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }
        .lp-node-actions { display: none; gap: 2px; margin-left: auto; padding-right: 6px; }
        .lp-node:hover .lp-node-actions { display: flex; }
        .lp-node-action-btn { background: transparent; border: none; color: var(--subtle); cursor: pointer; padding: 1px 3px; border-radius: 3px; font-size: 11px; }
        .lp-node-action-btn:hover { color: var(--text); background: rgba(255,255,255,.1); }
        .lp-tree-msg { padding: 8px 16px; font-size: 12px; color: var(--subtle); display: flex; align-items: center; gap: 6px; }

        /* Main */
        #main { flex: 1; display: flex; flex-direction: column; overflow: hidden; background: var(--bg); }

        /* Tab bar */
        #tab-bar { height: var(--tab-h); background: var(--surface); border-bottom: 1px solid var(--border); display: flex; align-items: stretch; overflow-x: auto; overflow-y: hidden; flex-shrink: 0; }
        #tab-bar::-webkit-scrollbar { height: 0; }
        .lp-tab { display: flex; align-items: center; gap: 5px; padding: 0 14px 0 10px; border-right: 1px solid var(--border2); cursor: pointer; font-size: 12.5px; color: var(--muted); white-space: nowrap; min-width: 120px; max-width: 200px; position: relative; flex-shrink: 0; transition: background .1s; }
        .lp-tab:hover { background: rgba(255,255,255,.04); color: var(--text); }
        .lp-tab.active { background: var(--bg); color: var(--text); border-bottom: 2px solid var(--blue); }
        .lp-tab.modified .lp-tab-name::after { content: '●'; margin-left: 5px; color: var(--orange); font-size: 10px; }
        .lp-tab-icon { font-size: 12px; flex-shrink: 0; }
        .lp-tab-name { flex: 1; overflow: hidden; text-overflow: ellipsis; }
        .lp-tab-close { background: transparent; border: none; color: var(--subtle); cursor: pointer; padding: 0 2px; border-radius: 3px; font-size: 12px; line-height: 1; opacity: 0; transition: opacity .1s; }
        .lp-tab:hover .lp-tab-close, .lp-tab.active .lp-tab-close { opacity: 1; }
        .lp-tab-close:hover { color: var(--red); background: rgba(248,81,73,.15); }

        /* Toolbar / Breadcrumb */
        #toolbar { height: var(--toolbar-h); background: var(--surface2); border-bottom: 1px solid var(--border2); display: flex; align-items: center; padding: 0 10px; gap: 8px; flex-shrink: 0; }
        #breadcrumb { display: flex; align-items: center; flex: 1; gap: 2px; overflow: hidden; font-size: 12.5px; }
        .lp-bc { color: var(--muted); cursor: pointer; padding: 2px 5px; border-radius: 4px; white-space: nowrap; }
        .lp-bc:hover { color: var(--text); background: rgba(255,255,255,.06); }
        .lp-bc.last { color: var(--text); cursor: default; }
        .lp-bc-sep { color: var(--subtle); font-size: 11px; }
        .lp-toolbar-actions { display: flex; align-items: center; gap: 4px; flex-shrink: 0; }
        .lp-tb-btn { background: transparent; border: none; color: var(--muted); cursor: pointer; padding: 4px 7px; border-radius: 4px; font-size: 13px; display: flex; align-items: center; gap: 4px; transition: background .1s, color .1s; }
        .lp-tb-btn:hover { background: rgba(255,255,255,.07); color: var(--text); }
        .lp-tb-btn:disabled { opacity: .4; cursor: default; }
        .lp-tb-btn.save-active { color: var(--blue); }

        /* Editor */
        #editor-wrap { flex: 1; overflow: hidden; display: flex; flex-direction: column; min-height: 0; }
        #editor-container { flex: 1; }
        #welcome { flex: 1; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 1rem; color: var(--muted); text-align: center; padding: 2rem; }
        #welcome .lp-welcome-icon { font-size: 3.5rem; color: var(--subtle); margin-bottom: .5rem; }
        #welcome h2 { font-size: 18px; font-weight: 600; color: var(--text); letter-spacing: -.3px; }
        #welcome p { font-size: 13px; color: var(--muted); }
        .lp-shortcut-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .4rem .8rem; font-size: 12px; margin-top: .5rem; }
        .lp-shortcut-grid kbd { background: var(--surface); border: 1px solid var(--border); border-radius: 4px; padding: 1px 6px; font-size: 11px; font-family: 'JetBrains Mono', monospace; color: var(--text); }

        /* ── Status bar ── */
        #status-bar { height: var(--statusbar-h); background: var(--blue-dim); color: rgba(255,255,255,.85); display: flex; align-items: center; padding: 0 10px; font-size: 11.5px; flex-shrink: 0; gap: 12px; }
        .lp-sb-right { margin-left: auto; display: flex; gap: 8px; align-items: center; }
        #status-bar span { display: flex; align-items: center; gap: 4px; }
        .lp-sb-term-btn { background: rgba(255,255,255,.12); border: none; color: rgba(255,255,255,.85); cursor: pointer; padding: 1px 8px; border-radius: 3px; font-size: 11px; display: flex; align-items: center; gap: 4px; }
        .lp-sb-term-btn:hover { background: rgba(255,255,255,.22); }
        .lp-sb-term-btn.active { background: rgba(255,255,255,.28); }

        /* ── Context menu ── */
        #ctx-menu { position: fixed; z-index: 9999; background: var(--surface); border: 1px solid var(--border); border-radius: 7px; padding: 4px; min-width: 170px; box-shadow: 0 8px 24px rgba(0,0,0,.55); display: none; }
        .ctx-item { display: flex; align-items: center; gap: 8px; padding: 6px 10px; border-radius: 5px; cursor: pointer; font-size: 12.5px; color: var(--muted); }
        .ctx-item:hover { background: rgba(255,255,255,.06); color: var(--text); }
        .ctx-item.danger { color: var(--red); }
        .ctx-item.danger:hover { background: rgba(248,81,73,.1); }
        .ctx-sep { height: 1px; background: var(--border2); margin: 3px 0; }

        /* ── Toast ── */
        #toast-wrap { position: fixed; bottom: 36px; right: 16px; z-index: 9999; display: flex; flex-direction: column; gap: 6px; pointer-events: none; }
        .lp-toast { padding: 8px 14px; border-radius: 6px; font-size: 12.5px; display: flex; align-items: center; gap: 7px; animation: lp-toast-in .2s ease; max-width: 340px; }
        .lp-toast.ok   { background: #1a3a1f; border: 1px solid rgba(63,185,80,.4);  color: var(--green); }
        .lp-toast.err  { background: #3a1a1a; border: 1px solid rgba(248,81,73,.4);  color: var(--red); }
        .lp-toast.info { background: var(--surface); border: 1px solid var(--border); color: var(--text); }
        @keyframes lp-toast-in { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:none; } }

        /* ── Modals ── */
        .lp-modal .modal-content { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text); }
        .lp-modal .modal-header { border-bottom: 1px solid var(--border); padding: 1rem 1.25rem; }
        .lp-modal .modal-body   { padding: 1.25rem; }
        .lp-modal .modal-footer { border-top: 1px solid var(--border); padding: .75rem 1.25rem; }
        .lp-modal .btn-close    { filter: invert(1) grayscale(1) brightness(1.5); }
        .lp-input { width: 100%; background: var(--bg); border: 1px solid var(--border); color: var(--text); border-radius: 6px; padding: 8px 12px; font-size: 13px; outline: none; font-family: 'JetBrains Mono', monospace; }
        .lp-input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(88,166,255,.15); }
        .lp-btn-primary { background: var(--blue-dim); border: none; color: #fff; border-radius: 6px; padding: 7px 18px; font-size: 13px; cursor: pointer; font-weight: 500; }
        .lp-btn-primary:hover { background: #1a7ee0; }
        .lp-btn-secondary { background: transparent; border: 1px solid var(--border); color: var(--text); border-radius: 6px; padding: 7px 16px; font-size: 13px; cursor: pointer; }
        .lp-btn-secondary:hover { background: rgba(255,255,255,.05); }
        .lp-btn-danger { background: rgba(248,81,73,.15); border: 1px solid rgba(248,81,73,.4); color: var(--red); border-radius: 6px; padding: 7px 16px; font-size: 13px; cursor: pointer; }
        .lp-btn-danger:hover { background: rgba(248,81,73,.25); }

        /* Search */
        .lp-search-results { max-height: 320px; overflow-y: auto; margin-top: 12px; }
        .lp-search-item { padding: 7px 10px; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 8px; }
        .lp-search-item:hover { background: rgba(255,255,255,.05); }
        .lp-si-name { font-size: 13px; color: var(--text); }
        .lp-si-path { font-size: 11.5px; color: var(--muted); font-family: 'JetBrains Mono', monospace; }

        /* ── Shortcuts ── */
        .lp-shortcuts-body { padding: 6px 8px 8px; display: flex; flex-direction: column; gap: 4px; }
        .lp-shortcut-btn { display: flex; align-items: center; gap: 8px; padding: 7px 10px; border-radius: 6px; background: var(--bg); border: 1px solid var(--border2); color: var(--muted); cursor: pointer; font-size: 12.5px; font-family: inherit; text-align: left; width: 100%; transition: background .12s, border-color .12s, color .12s; position: relative; }
        .lp-shortcut-btn:hover { background: rgba(88,166,255,.08); border-color: rgba(88,166,255,.3); color: var(--text); }
        .lp-shortcut-btn.active { background: rgba(88,166,255,.14); border-color: var(--blue); color: var(--text); }
        .lp-shortcut-btn i.sc-icon { font-size: 14px; flex-shrink: 0; }
        .lp-shortcut-btn .sc-name { flex: 1; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .lp-sc-btns { position: absolute; right: 4px; top: 50%; transform: translateY(-50%); display: none; gap: 2px; align-items: center; }
        .lp-shortcut-btn:hover .lp-sc-btns { display: flex; }
        .lp-shortcut-del, .lp-shortcut-edit { background: transparent; border: none; color: var(--subtle); cursor: pointer; padding: 2px 4px; border-radius: 3px; font-size: 11px; line-height: 1; }
        .lp-shortcut-edit:hover { color: var(--blue); background: rgba(88,166,255,.15); }
        .lp-shortcut-del:hover  { color: var(--red);  background: rgba(248,81,73,.15); }
        /* Reordenar atalhos: arrastar e soltar + ordenar A→Z / Z→A */
        .lp-shortcut-btn .sc-grip { position: absolute; left: -1px; top: 50%; transform: translateY(-50%); font-size: 11px; color: var(--subtle); opacity: 0; cursor: grab; transition: opacity .12s; }
        .lp-shortcut-btn:hover .sc-grip { opacity: 1; }
        .lp-shortcut-btn.sc-dragging { opacity: .4; }
        .lp-shortcut-btn.sc-drop-before { box-shadow: 0 -2px 0 0 var(--blue); }
        .lp-shortcut-btn.sc-drop-after  { box-shadow: 0 2px 0 0 var(--blue); }
        .lp-shortcuts-body.sc-sorting .lp-shortcut-btn:hover .lp-sc-btns { display: none; }
        .lp-sec-actions { margin-left: auto; display: flex; gap: 2px; opacity: 0; transition: opacity .12s; }
        .lp-section-hdr:hover .lp-sec-actions, .lp-sec-actions:focus-within { opacity: 1; }
        .lp-sec-actions button { background: transparent; border: none; color: var(--subtle); padding: 1px 4px; border-radius: 3px; font-size: 12px; line-height: 1; cursor: pointer; }
        .lp-sec-actions button:hover { color: var(--text); background: rgba(255,255,255,.08); }
        .lp-shortcuts-empty { font-size: 12px; color: var(--subtle); text-align: center; padding: 8px 4px; }
        .lp-sc-add { display: flex; align-items: center; justify-content: center; gap: 5px; padding: 5px 10px; border-radius: 5px; background: transparent; border: 1px dashed var(--border); color: var(--subtle); cursor: pointer; font-size: 12px; font-family: inherit; width: 100%; transition: background .12s, color .12s, border-color .12s; }
        .lp-sc-add:hover { border-color: var(--blue-dim); color: var(--blue); background: rgba(88,166,255,.05); }
        .lp-icon-pick { width: 36px; height: 36px; background: var(--bg); border: 1px solid var(--border); color: var(--muted); border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 15px; transition: background .1s, color .1s, border-color .1s; }
        .lp-icon-pick:hover  { background: var(--surface2); color: var(--text); }
        .lp-icon-pick.selected { background: rgba(88,166,255,.15); border-color: var(--blue); color: var(--blue); }

        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #444c56; }
        @keyframes lp-spin { to { transform: rotate(360deg); } }
        .lp-spin { animation: lp-spin .7s linear infinite; display: inline-block; }
        .bi-star-fill.fav { color: var(--yellow); }

        /* Non-admin = read-only Explorer. Elements stay in the DOM (the JS
           references them by id) but are hidden; !important beats the inline
           style.display the context menu sets. The routes are blocked
           server-side too — this is only so the buttons aren't offered. */
        body.lp-readonly .lp-admin-only { display: none !important; }
        .lp-ro-badge { display: none; align-items: center; gap: 5px; font-size: 11.5px; color: var(--yellow); padding: 2px 8px; border: 1px solid rgba(210,153,34,.35); border-radius: 4px; white-space: nowrap; }
        body.lp-readonly .lp-ro-badge { display: inline-flex; }
    </style>

    {{-- ══ Tema TechIA (vidro + aurora) — camada por cima do CSS acima ══ --}}
    <style>
        @property --lp-ang { syntax: '<angle>'; initial-value: 0deg; inherits: false; }

        :root {
            --bg:       #070a13;
            --surface:  rgba(14, 18, 31, .72);
            --surface2: rgba(22, 28, 46, .7);
            --border:   rgba(255,255,255,.09);
            --border2:  rgba(255,255,255,.06);
            --text:     #eef2ff;
            --muted:    #94a3b8;
            --subtle:   #64748b;
            --blue:     #818cf8;
            --blue-dim: #6366f1;
            --cyan:     #22d3ee;
            --green:    #34d399;
            --red:      #f87171;
            --orange:   #fbbf24;
            --yellow:   #fcd34d;
            --purple:   #c084fc;
            --grad:     linear-gradient(135deg, #22d3ee 0%, #6366f1 50%, #a855f7 100%);
            --grad-soft:linear-gradient(135deg, rgba(34,211,238,.16), rgba(99,102,241,.16) 50%, rgba(168,85,247,.16));
            --ease:     cubic-bezier(.2, .8, .2, 1);
            --sidebar-w: 272px;
            --tab-h:     38px;
            --toolbar-h: 38px;
            --statusbar-h: 26px;
        }
        html, body { background: var(--bg); -webkit-font-smoothing: antialiased; }
        ::selection { background: rgba(99,102,241,.45); color: #fff; }

        /* Fundo animado (fica atrás do IDE de vidro) */
        .lp-aurora { position: fixed; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; }
        .lp-aurora span { position: absolute; border-radius: 50%; filter: blur(90px); opacity: .3; will-change: transform; animation: lp-drift 30s ease-in-out infinite alternate; }
        .lp-aurora span:nth-child(1) { width: 50vmax; height: 50vmax; background: #4f46e5; top: -24vmax; left: -16vmax; }
        .lp-aurora span:nth-child(2) { width: 46vmax; height: 46vmax; background: #0891b2; bottom: -26vmax; right: -18vmax; animation-duration: 36s; animation-delay: -10s; }
        .lp-aurora span:nth-child(3) { width: 30vmax; height: 30vmax; background: #9333ea; top: 35%; left: 40%; opacity: .16; animation-duration: 42s; animation-delay: -20s; }
        @keyframes lp-drift { 0% { transform: translate(0,0) scale(1); } 50% { transform: translate(6vmax,4vmax) scale(1.1); } 100% { transform: translate(-4vmax,7vmax) scale(.95); } }
        #lp-ide { position: relative; z-index: 1; }

        /* ── Topbar de vidro ── */
        .lp-topbar {
            height: 52px; padding: 0 16px; gap: 14px;
            background: rgba(7,10,19,.6);
            -webkit-backdrop-filter: blur(18px) saturate(160%); backdrop-filter: blur(18px) saturate(160%);
            border-bottom: 1px solid var(--border2);
            position: relative;
        }
        .lp-topbar::after { content: ''; position: absolute; left: 0; right: 0; bottom: -1px; height: 1px; background: linear-gradient(90deg, transparent, rgba(99,102,241,.7) 30%, rgba(34,211,238,.7) 70%, transparent); opacity: .5; }
        .lp-topbar-brand { font-size: 15px; gap: 8px; letter-spacing: -.3px; }
        .lp-topbar-brand > i {
            width: 28px; height: 28px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px;
            background: var(--grad) !important; -webkit-background-clip: border-box !important; background-clip: border-box !important; color: #fff !important;
            box-shadow: 0 6px 18px -6px rgba(99,102,241,.8), inset 0 1px 0 rgba(255,255,255,.3);
            transition: transform .4s var(--ease);
        }
        .lp-topbar-brand:hover > i { transform: rotate(-8deg) scale(1.08); }
        .lp-topbar-conn {
            padding: 5px 12px 5px 8px; border-radius: 999px;
            background: rgba(255,255,255,.04); border: 1px solid var(--border2);
        }
        .lp-topbar-conn > i { width: 22px; height: 22px; border-radius: 7px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; color: #c7d2fe !important; background: var(--grad-soft); border: 1px solid rgba(129,140,248,.35); }
        .lp-topbar-conn span { font-family: 'JetBrains Mono', monospace; font-size: 11.5px; }
        .lp-icon-btn {
            border-radius: 10px; padding: 6px 12px; background: rgba(255,255,255,.04); border-color: var(--border);
            transition: transform .2s var(--ease), background .2s, border-color .2s, color .2s, box-shadow .25s;
        }
        .lp-icon-btn:hover { color: #fff; background: rgba(255,255,255,.08); border-color: rgba(165,180,252,.45); transform: translateY(-1px); box-shadow: 0 8px 20px -12px rgba(99,102,241,.9); }

        /* ── Sidebar de vidro ── */
        #sidebar {
            margin: 10px 0 10px 10px; border-radius: 16px;
            background: linear-gradient(170deg, rgba(255,255,255,.06), rgba(255,255,255,.015) 40%, rgba(255,255,255,.025));
            -webkit-backdrop-filter: blur(18px) saturate(150%); backdrop-filter: blur(18px) saturate(150%);
            border: 1px solid var(--border);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.08), 0 18px 40px -24px rgba(0,0,0,.9);
        }
        .lp-server-header { padding: 14px 14px 12px; border-bottom-color: var(--border2); background: radial-gradient(120% 120% at 0% 0%, rgba(99,102,241,.14), transparent 60%); }
        .lp-server-name { font-size: 13.5px; font-weight: 700; }
        .lp-conn-dot { width: 9px; height: 9px; }
        .lp-conn-dot.online { background: var(--green); box-shadow: 0 0 0 0 rgba(52,211,153,.6); animation: lp-ping 2.2s infinite; }
        .lp-conn-dot.offline { background: var(--red); box-shadow: 0 0 10px rgba(248,113,113,.7); }
        @keyframes lp-ping { 0% { box-shadow: 0 0 0 0 rgba(52,211,153,.55); } 80%, 100% { box-shadow: 0 0 0 8px rgba(52,211,153,0); } }
        .lp-server-meta { padding-left: 15px; }

        .lp-sidebar-actions, .lp-sidebar-search { padding: 8px 12px; border-bottom-color: var(--border2); }
        .lp-act-btn {
            border-radius: 9px; padding: 6px 6px; background: rgba(255,255,255,.04); border-color: var(--border);
            transition: transform .2s var(--ease), background .2s, border-color .2s, color .2s;
        }
        .lp-act-btn:hover { color: #fff; background: var(--grad-soft); border-color: rgba(129,140,248,.45); transform: translateY(-1px); }
        .lp-sidebar-search input { border-radius: 999px; padding: 6px 12px; background: rgba(5,8,16,.55); }
        .lp-sidebar-search input:focus { border-color: rgba(129,140,248,.8); box-shadow: 0 0 0 3px rgba(99,102,241,.18); }

        .lp-section { border-bottom-color: var(--border2); }
        .lp-section-hdr { padding: 10px 12px 7px; font-size: 10.5px; letter-spacing: .9px; }
        .lp-section-hdr:hover { color: #fff; }

        /* Árvore de arquivos */
        .lp-node { min-height: 25px; padding: 0 6px; }
        .lp-node-inner { border-radius: 7px; transition: background .15s; }
        .lp-node:hover > .lp-node-inner { background: rgba(255,255,255,.055); }
        .lp-node.active > .lp-node-inner { background: var(--grad-soft); box-shadow: inset 2px 0 0 #818cf8; }
        .lp-node.active .lp-node-name { color: #fff; }
        .lp-node:hover .lp-node-name { color: var(--text); }
        .lp-node-action-btn { border-radius: 5px; }

        /* ── Atalhos ── */
        .lp-shortcuts-body { padding: 4px 10px 10px; gap: 6px; }
        .lp-shortcut-btn {
            padding: 9px 12px 9px 14px; border-radius: 11px; overflow: hidden;
            background: linear-gradient(160deg, rgba(255,255,255,.06), rgba(255,255,255,.015));
            border: 1px solid var(--border);
            transition: transform .25s var(--ease), background .25s, border-color .25s, color .2s, box-shadow .25s;
        }
        .lp-shortcut-btn::before {
            content: ''; position: absolute; top: 0; bottom: 0; left: -60%; width: 45%;
            background: linear-gradient(100deg, transparent, rgba(255,255,255,.12), transparent);
            transform: skewX(-18deg); transition: left .7s var(--ease); pointer-events: none;
        }
        .lp-shortcut-btn:hover {
            color: #fff; transform: translateX(3px);
            border-color: rgba(129,140,248,.45);
            background: linear-gradient(160deg, rgba(99,102,241,.16), rgba(34,211,238,.06));
            box-shadow: 0 10px 22px -14px rgba(99,102,241,.9);
        }
        .lp-shortcut-btn:hover::before { left: 130%; }
        .lp-shortcut-btn.active {
            color: #fff; border-color: rgba(129,140,248,.7);
            background: linear-gradient(160deg, rgba(99,102,241,.24), rgba(168,85,247,.12));
            box-shadow: inset 3px 0 0 #818cf8, 0 10px 24px -14px rgba(99,102,241,1);
        }
        .lp-shortcut-btn i.sc-icon {
            width: 24px; height: 24px; border-radius: 7px; display: inline-flex; align-items: center; justify-content: center; font-size: 12.5px;
            color: #c7d2fe !important; background: var(--grad-soft); border: 1px solid rgba(129,140,248,.3);
            transition: transform .35s var(--ease);
        }
        .lp-shortcut-btn:hover i.sc-icon { transform: rotate(-8deg) scale(1.08); }
        .lp-shortcut-btn .sc-grip { left: 2px; }
        .lp-sc-btns { right: 6px; background: rgba(10,14,25,.85); border-radius: 7px; padding: 2px; }
        .lp-shortcut-edit:hover { color: #c7d2fe; background: rgba(99,102,241,.2); }
        .lp-shortcut-btn.sc-drop-before { box-shadow: 0 -2px 0 0 #818cf8, 0 -6px 14px -6px rgba(99,102,241,.9); }
        .lp-shortcut-btn.sc-drop-after  { box-shadow: 0 2px 0 0 #818cf8, 0 6px 14px -6px rgba(99,102,241,.9); }
        .lp-sec-actions button:hover { color: #fff; background: rgba(99,102,241,.2); }
        .lp-sc-add { border-radius: 10px; padding: 7px 10px; border-color: rgba(255,255,255,.14); transition: background .2s, color .2s, border-color .2s; }
        .lp-sc-add:hover { color: #c7d2fe; border-color: rgba(129,140,248,.6); background: rgba(99,102,241,.08); }
        .lp-icon-pick { border-radius: 10px; background: rgba(5,8,16,.5); transition: transform .2s var(--ease), background .15s, color .15s, border-color .15s; }
        .lp-icon-pick:hover { transform: translateY(-2px); }
        .lp-icon-pick.selected { background: var(--grad-soft); border-color: rgba(129,140,248,.7); color: #c7d2fe; box-shadow: 0 0 0 3px rgba(99,102,241,.15); }

        /* ── Área principal (abas + barra + editor) em vidro ── */
        #main {
            margin: 10px; border-radius: 16px; overflow: hidden;
            background: rgba(10,14,25,.6);
            -webkit-backdrop-filter: blur(16px); backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.06), 0 18px 40px -24px rgba(0,0,0,.9);
        }
        #tab-bar { background: rgba(255,255,255,.025); border-bottom-color: var(--border2); padding: 5px 6px 0; gap: 3px; }
        .lp-tab {
            border-right: 0; border-radius: 10px 10px 0 0; padding: 0 12px 0 12px;
            transition: background .2s, color .2s;
        }
        .lp-tab:hover { background: rgba(255,255,255,.05); }
        .lp-tab.active {
            background: #0b0f1c; color: #fff; border-bottom: 0;
            box-shadow: inset 0 2px 0 0 transparent;
        }
        .lp-tab.active::before { content: ''; position: absolute; left: 10px; right: 10px; top: 0; height: 2px; border-radius: 0 0 2px 2px; background: var(--grad); box-shadow: 0 0 10px rgba(99,102,241,.9); }
        .lp-tab-close { border-radius: 5px; }

        #toolbar { background: #0b0f1c; border-bottom-color: var(--border2); padding: 0 12px; }
        .lp-bc { border-radius: 7px; transition: background .15s, color .15s; }
        .lp-bc.last { color: #fff; font-weight: 600; }
        .lp-tb-btn { border-radius: 8px; padding: 5px 9px; transition: background .15s, color .15s, transform .2s var(--ease); }
        .lp-tb-btn:hover:not(:disabled) { color: #fff; background: rgba(255,255,255,.08); transform: translateY(-1px); }
        .lp-tb-btn.save-active { color: #fff; background: var(--grad); box-shadow: 0 6px 16px -6px rgba(99,102,241,.9); }
        .lp-ro-badge { border-radius: 999px; border-color: rgba(251,191,36,.35); background: rgba(251,191,36,.08); }

        #editor-wrap { background: #0b0f1c; }

        /* Boas-vindas */
        #welcome .lp-welcome-icon {
            width: 84px; height: 84px; border-radius: 24px; display: flex; align-items: center; justify-content: center; font-size: 2.3rem;
            color: #fff; background: var(--grad); background-size: 200% 200%;
            box-shadow: 0 22px 50px -18px rgba(99,102,241,.95), inset 0 1px 0 rgba(255,255,255,.3);
            animation: lp-float 4.5s ease-in-out infinite, lp-grad-shift 8s ease infinite;
        }
        #welcome h2 { font-size: 22px; font-weight: 800; background: linear-gradient(180deg, #fff 30%, #a5b4fc); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .lp-shortcut-grid { gap: .55rem 1.1rem; padding: 1rem 1.25rem; border-radius: 14px; background: rgba(255,255,255,.03); border: 1px solid var(--border2); }
        .lp-shortcut-grid kbd { border-radius: 7px; padding: 2px 8px; background: rgba(255,255,255,.06); border-color: var(--border); box-shadow: inset 0 -1px 0 rgba(255,255,255,.08); }
        @keyframes lp-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        @keyframes lp-grad-shift { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

        /* ── Barra de status ── */
        #status-bar {
            margin: 0 10px 10px; border-radius: 10px; padding: 0 12px;
            background: linear-gradient(90deg, rgba(34,211,238,.85), rgba(99,102,241,.9) 50%, rgba(168,85,247,.85));
            color: #fff; box-shadow: 0 10px 26px -14px rgba(99,102,241,1);
        }
        .lp-sb-term-btn { border-radius: 6px; background: rgba(255,255,255,.16); color: #fff; }
        .lp-sb-term-btn:hover { background: rgba(255,255,255,.28); }

        /* ── Menu de contexto, toasts e modais ── */
        #ctx-menu {
            border-radius: 12px; padding: 5px;
            background: rgba(15,19,32,.92); -webkit-backdrop-filter: blur(16px); backdrop-filter: blur(16px);
            box-shadow: 0 20px 44px -12px rgba(0,0,0,.85);
            animation: lp-pop .16s var(--ease);
        }
        .ctx-item { border-radius: 8px; transition: background .15s, color .15s, padding-left .2s var(--ease); }
        .ctx-item:hover { color: #fff; background: rgba(255,255,255,.07); padding-left: 14px; }
        @keyframes lp-pop { from { opacity: 0; transform: translateY(-4px) scale(.97); } to { opacity: 1; transform: none; } }

        .lp-toast { border-radius: 12px; padding: 10px 16px; -webkit-backdrop-filter: blur(14px); backdrop-filter: blur(14px); box-shadow: 0 14px 30px -12px rgba(0,0,0,.8); }
        .lp-toast.ok   { background: rgba(6,78,59,.75);  border-color: rgba(52,211,153,.45); color: #a7f3d0; }
        .lp-toast.err  { background: rgba(127,29,29,.75); border-color: rgba(248,113,113,.45); color: #fecaca; }
        .lp-toast.info { background: rgba(15,19,32,.88); border-color: var(--border); }

        .modal-backdrop.show { opacity: 1; background: rgba(3,5,12,.6); -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px); }
        .lp-modal .modal-content {
            background: rgba(14,18,31,.92); -webkit-backdrop-filter: blur(20px) saturate(150%); backdrop-filter: blur(20px) saturate(150%);
            border-radius: 18px; box-shadow: 0 30px 80px -20px rgba(0,0,0,.9), inset 0 1px 0 rgba(255,255,255,.07);
        }
        .lp-modal.fade .modal-dialog { transform: translateY(16px) scale(.97); transition: transform .3s var(--ease); }
        .lp-modal.show .modal-dialog { transform: none; }
        .lp-modal .modal-header, .lp-modal .modal-footer { border-color: var(--border2); }
        .lp-input { border-radius: 10px; background: rgba(5,8,16,.55); transition: border-color .2s, box-shadow .2s; }
        .lp-input:focus { border-color: rgba(129,140,248,.8); box-shadow: 0 0 0 4px rgba(99,102,241,.18); }
        .lp-btn-primary, .lp-btn-secondary, .lp-btn-danger { border-radius: 10px; font-weight: 600; transition: transform .2s var(--ease), box-shadow .25s, background .25s, border-color .25s; }
        .lp-btn-primary { background: var(--grad); background-size: 180% 180%; box-shadow: 0 8px 22px -10px rgba(99,102,241,.9), inset 0 1px 0 rgba(255,255,255,.25); }
        .lp-btn-primary:hover { background: var(--grad); background-size: 180% 180%; background-position: 100% 50%; transform: translateY(-1px); box-shadow: 0 14px 28px -10px rgba(99,102,241,1); }
        .lp-btn-secondary { background: rgba(255,255,255,.04); }
        .lp-btn-secondary:hover { background: rgba(255,255,255,.08); border-color: rgba(165,180,252,.45); transform: translateY(-1px); }
        .lp-btn-danger:hover { color: #fff; background: linear-gradient(135deg, #ef4444, #db2777); border-color: transparent; transform: translateY(-1px); }
        .lp-search-item { border-radius: 9px; transition: background .15s, transform .2s var(--ease); }
        .lp-search-item:hover { background: var(--grad-soft); transform: translateX(3px); }

        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 8px; border: 2px solid transparent; background-clip: padding-box; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(129,140,248,.45); background-clip: padding-box; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body class="{{ auth()->user()->is_admin ? '' : 'lp-readonly' }}">
<div class="lp-aurora" aria-hidden="true"><span></span><span></span><span></span></div>
<div id="lp-ide">

{{-- ── Topbar ── --}}
<header class="lp-topbar">
    <a href="{{ route('connections.index') }}" class="lp-topbar-brand" title="Servidores">
        <i class="bi bi-terminal-fill" style="background:linear-gradient(135deg,#22d3ee,#6366f1 50%,#a855f7);-webkit-background-clip:text;background-clip:text;color:transparent"></i>
        TechIA<span style="background:linear-gradient(135deg,#22d3ee,#6366f1 50%,#a855f7);-webkit-background-clip:text;background-clip:text;color:transparent"> Panel</span>
    </a>
    <div class="lp-topbar-sep"></div>
    <div class="lp-topbar-conn">
        <i class="bi bi-hdd-fill" style="color:var(--blue-dim)"></i>
        <strong>{{ $connection->name }}</strong>
        <span style="color:var(--subtle)">{{ $connection->username }}&#64;{{ $connection->host }}:{{ $connection->port }}</span>
    </div>
    <div class="lp-topbar-right">
        <button class="lp-icon-btn" data-bs-toggle="modal" data-bs-target="#searchModal" title="Pesquisar (Ctrl+P)">
            <i class="bi bi-search"></i> Pesquisar
        </button>
        <a href="{{ route('connections.index') }}" class="lp-icon-btn">
            <i class="bi bi-grid"></i>
        </a>
    </div>
</header>

{{-- ── Body ── --}}
<div class="lp-body">

    {{-- ── Sidebar ── --}}
    <aside id="sidebar">
        <div class="lp-server-header">
            <div class="lp-server-name">
                <span class="lp-conn-dot" id="connDot"></span>
                <span id="connLabel">Conectando…</span>
            </div>
            <div class="lp-server-meta" id="connMeta">{{ $connection->host }}</div>
        </div>

        <div class="lp-sidebar-actions lp-admin-only">
            <button class="lp-act-btn" onclick="LP.promptCreateFile()" title="Novo arquivo">
                <i class="bi bi-file-earmark-plus"></i> Arquivo
            </button>
            <button class="lp-act-btn" onclick="LP.promptCreateDir()" title="Nova pasta">
                <i class="bi bi-folder-plus"></i> Pasta
            </button>
            <button class="lp-act-btn" onclick="LP.openTerminalTab()" title="Abrir terminal em nova aba (Ctrl+`)">
                <i class="bi bi-terminal"></i>
            </button>
        </div>

        <div class="lp-sidebar-search">
            <input type="text" id="treeFilter" placeholder="Filtrar arquivos…" autocomplete="off" spellcheck="false">
        </div>

        <div class="lp-sidebar-scroll" id="sidebarScroll">

            {{-- Shortcuts --}}
            <div class="lp-section" id="shortcutsSection">
                <div class="lp-section-hdr" onclick="LP.toggleSection('shortcutsSection')">
                    <i class="bi bi-chevron-right lp-arrow" style="transform:rotate(90deg)"></i>
                    <i class="bi bi-signpost-split-fill" style="color:var(--blue);font-size:10px"></i>
                    Atalhos
                    <span class="lp-sec-actions">
                        <button type="button" onclick="event.stopPropagation();LP.sortShortcuts('asc')" title="Ordenar de A a Z"><i class="bi bi-sort-alpha-down"></i></button>
                        <button type="button" onclick="event.stopPropagation();LP.sortShortcuts('desc')" title="Ordenar de Z a A"><i class="bi bi-sort-alpha-up-alt"></i></button>
                    </span>
                </div>
                <div class="lp-section-body lp-shortcuts-body" id="shortcutsBody">
                    @forelse($shortcuts as $sc)
                    <button class="lp-shortcut-btn"
                            id="sc-btn-{{ $sc->id }}"
                            onclick="LP.navigateTo('{{ $sc->path }}')"
                            data-id="{{ $sc->id }}"
                            data-path="{{ $sc->path }}"
                            draggable="true"
                            title="{{ $sc->path }} (arraste para mudar a ordem)">
                        <i class="bi bi-grip-vertical sc-grip"></i>
                        <i class="bi {{ $sc->icon }} sc-icon" style="color:var(--blue)"></i>
                        <span class="sc-name">{{ $sc->name }}</span>
                        <span class="lp-sc-btns">
                            <span class="lp-shortcut-edit"
                                    onclick="event.stopPropagation();LP.promptEditShortcut({{ $sc->id }},'{{ addslashes($sc->name) }}','{{ addslashes($sc->path) }}','{{ $sc->icon }}')"
                                    title="Editar atalho"><i class="bi bi-pencil"></i></span>
                            <span class="lp-shortcut-del"
                                    onclick="event.stopPropagation();LP.removeShortcut({{ $sc->id }})"
                                    title="Remover atalho"><i class="bi bi-x"></i></span>
                        </span>
                    </button>
                    @empty
                    <div class="lp-shortcuts-empty" id="shortcutsEmpty">
                        Nenhum atalho ainda.<br>Navegue até uma pasta e clique em <strong>+ Atalho</strong>.
                    </div>
                    @endforelse
                    <button class="lp-sc-add" onclick="LP.promptAddShortcut()" title="Adicionar atalho para o diretório atual">
                        <i class="bi bi-plus-circle"></i> Adicionar atalho
                    </button>
                </div>
            </div>

            @if($favorites->isNotEmpty())
            <div class="lp-section" id="favSection">
                <div class="lp-section-hdr" onclick="LP.toggleSection('favSection')">
                    <i class="bi bi-chevron-right lp-arrow"></i>
                    <i class="bi bi-star-fill" style="color:var(--yellow);font-size:10px"></i>
                    Favoritos
                </div>
                <div class="lp-section-body hidden" id="favBody">
                    @foreach($favorites as $fav)
                    <div class="lp-node" data-path="{{ $fav->path }}" data-type="dir"
                         onclick="LP.navigateTo('{{ $fav->path }}')" title="{{ $fav->path }}">
                        <div class="lp-node-inner" style="padding-left:10px">
                            <span class="lp-node-arrow spacer"><i class="bi bi-chevron-right"></i></span>
                            <span class="lp-node-icon"><i class="bi bi-folder-fill" style="color:var(--yellow)"></i></span>
                            <span class="lp-node-name">{{ $fav->display_label }}</span>
                            <div class="lp-node-actions">
                                <button class="lp-node-action-btn"
                                        onclick="event.stopPropagation();LP.removeFavorite('{{ $fav->path }}')"
                                        title="Remover favorito"><i class="bi bi-x"></i></button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="lp-section" id="explorerSection">
                <div class="lp-section-hdr" onclick="LP.toggleSection('explorerSection')">
                    <i class="bi bi-chevron-right lp-arrow"></i>
                    <i class="bi bi-files" style="font-size:10px"></i>
                    Explorador
                </div>
                <div class="lp-section-body" id="explorerBody">
                    <div class="lp-tree-msg" id="treeStatus">
                        <i class="bi bi-arrow-repeat lp-spin"></i> Conectando…
                    </div>
                    <div id="fileTree"></div>
                </div>
            </div>

        </div>
    </aside>

    {{-- ── Main area ── --}}
    <div id="main">
        <div id="tab-bar"><div id="tabs-list" style="display:flex;align-items:stretch;"></div></div>

        <div id="toolbar">
            <div id="breadcrumb"></div>
            <div class="lp-toolbar-actions">
                <span class="lp-ro-badge" title="Seu usuário pode navegar, abrir e copiar arquivos, mas não alterá-los.">
                    <i class="bi bi-eye"></i> Somente leitura
                </span>
                <button class="lp-tb-btn lp-admin-only" id="btnSave" onclick="LP.saveCurrentFile()" disabled title="Salvar (Ctrl+S)">
                    <i class="bi bi-floppy2"></i>
                    <span style="font-size:12px">Salvar</span>
                </button>
                <button class="lp-tb-btn lp-admin-only" id="btnDeleteFile" onclick="LP.promptDeleteCurrentFile()" disabled title="Excluir arquivo" style="color:var(--red)">
                    <i class="bi bi-trash3"></i>
                </button>
                <button class="lp-tb-btn" onclick="LP.navigateUp()" title="Subir um nível (Alt+↑)">
                    <i class="bi bi-arrow-90deg-up"></i>
                </button>
                <button class="lp-tb-btn" onclick="LP.navigateHome()" title="Voltar à raiz do explorador">
                    <i class="bi bi-house-fill"></i>
                </button>
                <button class="lp-tb-btn" onclick="LP.refreshCurrentDir()" title="Atualizar diretório">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <button class="lp-tb-btn" onclick="LP.toggleFavoriteCurrentDir()" title="Favoritar diretório atual">
                    <i class="bi bi-star" id="btnFavIcon"></i>
                </button>
            </div>
        </div>

        <div id="editor-wrap">
            <div id="welcome">
                <i class="bi bi-terminal-fill lp-welcome-icon"></i>
                <h2>TechIA Panel Explorer</h2>
                <p>Dê duplo clique em um arquivo para abrir no editor.</p>
                <div class="lp-shortcut-grid">
                    <div><kbd>Duplo Clique</kbd></div><div style="color:var(--muted)">Abrir arquivo</div>
                    <div><kbd>Ctrl+S</kbd></div><div style="color:var(--muted)">Salvar</div>
                    <div><kbd>Ctrl+P</kbd></div><div style="color:var(--muted)">Pesquisar</div>
                    <div><kbd>Ctrl+`</kbd></div><div style="color:var(--muted)">Terminal</div>
                    <div><kbd>Botão direito</kbd></div><div style="color:var(--muted)">Menu de contexto</div>
                </div>
            </div>
            <div id="editor-container" style="display:none;"></div>
        </div>

    </div>
</div>

{{-- ── Status bar ── --}}
<div id="status-bar">
    <span id="sb-conn">
        <i class="bi bi-circle-fill" id="sbDot" style="font-size:7px;color:var(--subtle)"></i>
        <span id="sbConnLabel">Conectando…</span>
    </span>
    <span id="sb-path" style="color:rgba(255,255,255,.6);font-family:'JetBrains Mono',monospace;font-size:11px"></span>
    <div class="lp-sb-right">
        <button class="lp-sb-term-btn lp-admin-only" id="sbTermBtn" onclick="LP.openTerminalTab()" title="Abrir terminal em nova aba (Ctrl+`)">
            <i class="bi bi-terminal"></i> Terminal
        </button>
        <span id="sb-lang" style="opacity:.7"></span>
        <span id="sb-pos" style="opacity:.7">Ln 1, Col 1</span>
    </div>
</div>

</div>{{-- #lp-ide --}}

{{-- ── Toast container ── --}}
<div id="toast-wrap"></div>

{{-- ── Context menu ── --}}
<div id="ctx-menu">
    <div class="ctx-item" id="ctx-open"     onclick="LP_CTX.openFile()"><i class="bi bi-pencil" style="width:14px"></i> Abrir</div>
    <div class="ctx-item" id="ctx-copy-path" onclick="LP_CTX.copyPath()"><i class="bi bi-clipboard" style="width:14px"></i> Copiar caminho</div>
    <div class="ctx-sep lp-admin-only" id="ctx-sep-file"></div>
    <div class="ctx-item lp-admin-only" id="ctx-new-file" onclick="LP_CTX.promptNewFile()"><i class="bi bi-file-earmark-plus" style="width:14px"></i> Novo Arquivo</div>
    <div class="ctx-item lp-admin-only" id="ctx-new-dir"  onclick="LP_CTX.promptNewDir()"><i class="bi bi-folder-plus" style="width:14px"></i> Nova Pasta</div>
    <div class="ctx-sep lp-admin-only" id="ctx-sep-dir"></div>
    <div class="ctx-item lp-admin-only" onclick="LP_CTX.promptRename()"><i class="bi bi-cursor-text" style="width:14px"></i> Renomear</div>
    <div class="ctx-item danger lp-admin-only" onclick="LP_CTX.promptDelete()"><i class="bi bi-trash3" style="width:14px"></i> Excluir</div>
    <div class="ctx-sep" id="ctx-sep-term"></div>
    <div class="ctx-item lp-admin-only" id="ctx-terminal"  onclick="LP_CTX.openInTerminal()"><i class="bi bi-terminal" style="width:14px"></i> Abrir no Terminal</div>
    <div class="ctx-item" id="ctx-shortcut" onclick="LP_CTX.promptShortcut()"><i class="bi bi-signpost-split" style="width:14px"></i> Adicionar como Atalho</div>
    <div class="ctx-sep lp-admin-only"></div>
    <div class="ctx-item lp-admin-only" id="ctx-duplicate" onclick="LP_CTX.promptDuplicate()"><i class="bi bi-copy" style="width:14px"></i> Duplicar</div>
</div>

{{-- ── Search modal ── --}}
<div class="modal fade lp-modal" id="searchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size:14px;font-weight:600">
                    <i class="bi bi-search me-2" style="color:var(--blue)"></i>Pesquisar Arquivos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="searchInput" class="lp-input"
                       placeholder="Digite o nome do arquivo (mínimo 2 caracteres)…"
                       autocomplete="off" spellcheck="false" style="font-family:inherit">
                <div id="searchResults" class="lp-search-results"></div>
            </div>
        </div>
    </div>
</div>

{{-- ── Create file modal ── --}}
<div class="modal fade lp-modal" id="modalCreateFile" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size:14px;font-weight:600">
                    <i class="bi bi-file-earmark-plus me-2" style="color:var(--blue)"></i>Novo Arquivo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label style="font-size:12px;color:var(--muted);margin-bottom:6px;display:block">Nome do arquivo</label>
                <input type="text" id="inputFileName" class="lp-input" placeholder="ex: index.php" autocomplete="off" spellcheck="false">
                <div id="createFileDir" style="margin-top:8px;font-size:11.5px;color:var(--subtle);font-family:'JetBrains Mono',monospace;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></div>
            </div>
            <div class="modal-footer gap-2">
                <button type="button" class="lp-btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="lp-btn-primary" onclick="LP.doCreateFile()">Criar</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Create dir modal ── --}}
<div class="modal fade lp-modal" id="modalCreateDir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size:14px;font-weight:600">
                    <i class="bi bi-folder-plus me-2" style="color:var(--blue)"></i>Nova Pasta
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label style="font-size:12px;color:var(--muted);margin-bottom:6px;display:block">Nome da pasta</label>
                <input type="text" id="inputDirName" class="lp-input" placeholder="ex: components" autocomplete="off" spellcheck="false">
                <div id="createDirParent" style="margin-top:8px;font-size:11.5px;color:var(--subtle);font-family:'JetBrains Mono',monospace;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></div>
            </div>
            <div class="modal-footer gap-2">
                <button type="button" class="lp-btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="lp-btn-primary" onclick="LP.doCreateDir()">Criar</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Rename modal ── --}}
<div class="modal fade lp-modal" id="modalRename" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size:14px;font-weight:600">
                    <i class="bi bi-cursor-text me-2" style="color:var(--blue)"></i>Renomear
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label style="font-size:12px;color:var(--muted);margin-bottom:6px;display:block">Novo nome</label>
                <input type="text" id="inputRename" class="lp-input" autocomplete="off" spellcheck="false">
            </div>
            <div class="modal-footer gap-2">
                <button type="button" class="lp-btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="lp-btn-primary" onclick="LP.doRename()">Renomear</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Edit shortcut modal ── --}}
<div class="modal fade lp-modal" id="modalEditShortcut" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size:14px;font-weight:600">
                    <i class="bi bi-pencil-fill me-2" style="color:var(--blue)"></i>Editar Atalho
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="display:flex;flex-direction:column;gap:14px">
                <div>
                    <label style="font-size:12px;color:var(--muted);margin-bottom:5px;display:block">Nome</label>
                    <input type="text" id="editShortcutName" class="lp-input" placeholder="Nome exibido no botão" autocomplete="off" spellcheck="false">
                </div>
                <div>
                    <label style="font-size:12px;color:var(--muted);margin-bottom:5px;display:block">Caminho</label>
                    <input type="text" id="editShortcutPath" class="lp-input" placeholder="/var/www/html/..." autocomplete="off" spellcheck="false">
                </div>
                <div>
                    <label style="font-size:12px;color:var(--muted);margin-bottom:8px;display:block">Ícone <span style="color:var(--subtle)">(Bootstrap Icons)</span></label>
                    <div id="iconPicker" style="display:flex;flex-wrap:wrap;gap:6px">
                        @foreach([
                            'bi-folder-symlink-fill' => 'Pasta atalho',
                            'bi-robot'               => 'IA / Robot',
                            'bi-database'            => 'Banco de dados',
                            'bi-gear-fill'           => 'Configurações',
                            'bi-file-earmark-code'   => 'Código',
                            'bi-cloud-fill'          => 'Cloud',
                            'bi-hdd-fill'            => 'Disco',
                            'bi-shield-fill'         => 'Segurança',
                            'bi-bug-fill'            => 'Debug/Logs',
                            'bi-images'              => 'Imagens/Assets',
                            'bi-box-fill'            => 'Pacotes',
                            'bi-terminal-fill'       => 'Scripts',
                            'bi-key-fill'            => 'Chaves/Auth',
                            'bi-globe'               => 'Web',
                            'bi-envelope-fill'       => 'Email',
                            'bi-graph-up'            => 'Analytics',
                        ] as $ico => $label)
                        <button type="button" class="lp-icon-pick" data-icon="{{ $ico }}" data-picker="edit" title="{{ $label }}"
                                onclick="LP.selectIcon('{{ $ico }}', 'edit')">
                            <i class="bi {{ $ico }}"></i>
                        </button>
                        @endforeach
                    </div>
                    <input type="hidden" id="editShortcutIcon" value="bi-folder-symlink-fill">
                </div>
            </div>
            <div class="modal-footer gap-2">
                <button type="button" class="lp-btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="lp-btn-primary" onclick="LP.doEditShortcut()">Salvar</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Add shortcut modal ── --}}
<div class="modal fade lp-modal" id="modalAddShortcut" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size:14px;font-weight:600">
                    <i class="bi bi-signpost-split-fill me-2" style="color:var(--blue)"></i>Adicionar Atalho
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="display:flex;flex-direction:column;gap:14px">
                <div>
                    <label style="font-size:12px;color:var(--muted);margin-bottom:5px;display:block">Nome</label>
                    <input type="text" id="inputShortcutName" class="lp-input" placeholder="ex: Arquivos de config IA" autocomplete="off" spellcheck="false">
                </div>
                <div>
                    <label style="font-size:12px;color:var(--muted);margin-bottom:5px;display:block">Caminho</label>
                    <input type="text" id="inputShortcutPath" class="lp-input" placeholder="/var/www/html/..." autocomplete="off" spellcheck="false">
                </div>
                <div>
                    <label style="font-size:12px;color:var(--muted);margin-bottom:8px;display:block">Ícone <span style="color:var(--subtle)">(Bootstrap Icons)</span></label>
                    <div id="addIconPicker" style="display:flex;flex-wrap:wrap;gap:6px">
                        @foreach([
                            'bi-folder-symlink-fill' => 'Pasta atalho',
                            'bi-robot'               => 'IA / Robot',
                            'bi-database'            => 'Banco de dados',
                            'bi-gear-fill'           => 'Configurações',
                            'bi-file-earmark-code'   => 'Código',
                            'bi-cloud-fill'          => 'Cloud',
                            'bi-hdd-fill'            => 'Disco',
                            'bi-shield-fill'         => 'Segurança',
                            'bi-bug-fill'            => 'Debug/Logs',
                            'bi-images'              => 'Imagens/Assets',
                            'bi-box-fill'            => 'Pacotes',
                            'bi-terminal-fill'       => 'Scripts',
                            'bi-key-fill'            => 'Chaves/Auth',
                            'bi-globe'               => 'Web',
                            'bi-envelope-fill'       => 'Email',
                            'bi-graph-up'            => 'Analytics',
                        ] as $ico => $label)
                        <button type="button" class="lp-icon-pick" data-icon="{{ $ico }}" data-picker="add" title="{{ $label }}"
                                onclick="LP.selectIcon('{{ $ico }}', 'add')">
                            <i class="bi {{ $ico }}"></i>
                        </button>
                        @endforeach
                    </div>
                    <input type="hidden" id="inputShortcutIcon" value="bi-folder-symlink-fill">
                </div>
            </div>
            <div class="modal-footer gap-2">
                <button type="button" class="lp-btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="lp-btn-primary" onclick="LP.doAddShortcut()">Adicionar</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Duplicate modal ── --}}
<div class="modal fade lp-modal" id="modalDuplicate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size:14px;font-weight:600">
                    <i class="bi bi-copy me-2" style="color:var(--blue)"></i>Duplicar
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label style="font-size:12px;color:var(--muted);margin-bottom:6px;display:block">Novo nome</label>
                <input type="text" id="inputDuplicateName" class="lp-input" autocomplete="off" spellcheck="false">
            </div>
            <div class="modal-footer gap-2">
                <button type="button" class="lp-btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="lp-btn-primary" onclick="LP.doConfirmDuplicate()">Duplicar</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Delete confirm modal ── --}}
<div class="modal fade lp-modal" id="modalDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size:14px;font-weight:600">
                    <i class="bi bi-trash3 me-2" style="color:var(--red)"></i>Excluir
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p style="font-size:13px;color:var(--muted)">Excluir <strong id="deleteItemName" style="color:var(--text)"></strong>?</p>
                <p style="font-size:11.5px;color:var(--red);margin-top:6px"><i class="bi bi-exclamation-triangle-fill me-1"></i>Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer gap-2">
                <button type="button" class="lp-btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="lp-btn-danger" onclick="LP.doDelete()">Excluir</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Scripts ── --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>var require = { paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.52.0/min/vs' } };</script>
<script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.52.0/min/vs/loader.js"></script>

<script>
// ─── Config ──────────────────────────────────────────────────────────────────
const LP_CFG = {
    connectionId : {{ $connection->id }},
    apiBase      : '{{ url("explorer/{$connection->id}") }}',
    csrfToken    : '{{ csrf_token() }}',
    username     : '{{ $connection->username }}',
    host         : '{{ $connection->host }}',
    favorites    : {!! $favorites->map(fn($f) => ['path' => $f->path, 'label' => $f->display_label])->toJson() !!},
    shortcuts    : {!! $shortcuts->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'path' => $s->path, 'icon' => $s->icon])->toJson() !!},
    pingUrl      : '{{ route('ping') }}',
    terminalUrl  : '{{ route('connections.terminal', $connection) }}',
    isAdmin      : {{ auth()->user()->is_admin ? 'true' : 'false' }},
};
axios.defaults.headers.common['X-CSRF-TOKEN'] = LP_CFG.csrfToken;

// Keep the session alive while this page stays open (Monaco editing generates no
// requests on its own), so long edit sessions don't hit a 419 on save.
setInterval(() => { axios.get(LP_CFG.pingUrl).catch(() => {}); }, 10 * 60 * 1000);

// ─── File icons ───────────────────────────────────────────────────────────────
const FILE_ICONS = {
    dir    : { icon: 'bi-folder-fill',   color: '#58a6ff' },
    php    : { icon: 'bi-filetype-php',  color: '#8b9cf0' },
    js     : { icon: 'bi-filetype-js',   color: '#f7df1e' },
    ts     : { icon: 'bi-filetype-tsx',  color: '#007acc' },
    jsx    : { icon: 'bi-filetype-jsx',  color: '#61dafb' },
    tsx    : { icon: 'bi-filetype-tsx',  color: '#61dafb' },
    json   : { icon: 'bi-filetype-json', color: '#fab040' },
    html   : { icon: 'bi-filetype-html', color: '#e34c26' },
    css    : { icon: 'bi-filetype-css',  color: '#264de4' },
    scss   : { icon: 'bi-filetype-scss', color: '#bf4080' },
    xml    : { icon: 'bi-filetype-xml',  color: '#e37a27' },
    sql    : { icon: 'bi-filetype-sql',  color: '#00adef' },
    yml    : { icon: 'bi-filetype-yml',  color: '#cb171e' },
    yaml   : { icon: 'bi-filetype-yml',  color: '#cb171e' },
    md     : { icon: 'bi-filetype-md',   color: '#519aba' },
    env    : { icon: 'bi-file-lock-fill',color: '#ecd53f' },
    log    : { icon: 'bi-file-text',     color: '#8b949e' },
    txt    : { icon: 'bi-file-text',     color: '#8b949e' },
    sh     : { icon: 'bi-terminal',      color: '#4caf50' },
    py     : { icon: 'bi-filetype-py',   color: '#3572a5' },
    png    : { icon: 'bi-filetype-png',  color: '#e384c8' },
    jpg    : { icon: 'bi-filetype-jpg',  color: '#e384c8' },
    jpeg   : { icon: 'bi-filetype-jpg',  color: '#e384c8' },
    gif    : { icon: 'bi-filetype-gif',  color: '#e384c8' },
    svg    : { icon: 'bi-filetype-svg',  color: '#e384c8' },
    pdf    : { icon: 'bi-filetype-pdf',  color: '#f40f02' },
    zip    : { icon: 'bi-file-zip-fill', color: '#d4a017' },
    gz     : { icon: 'bi-file-zip-fill', color: '#d4a017' },
    tar    : { icon: 'bi-file-zip-fill', color: '#d4a017' },
    default: { icon: 'bi-file-earmark',  color: '#8b949e' },
};

function getFileIcon(name, type) {
    if (type === 'dir') return FILE_ICONS.dir;
    const nl = name.toLowerCase();
    if (nl.endsWith('.blade.php')) return { icon: 'bi-filetype-html', color: '#ff2d20' };
    if (['.env','.env.example','.env.local','.env.production','.env.testing'].includes(nl)) return FILE_ICONS.env;
    return FILE_ICONS[nl.split('.').pop()] || FILE_ICONS.default;
}

// ─── State ────────────────────────────────────────────────────────────────────
const treeState  = { cache: {}, expanded: new Set(), root: null, originalRoot: null };
const tabState   = { tabs: [], activeIdx: -1 };
let editor = null, monacoReady = false, pendingOpen = null;

// Op state
let _createFileDir = '', _createDirParent = '';
let _renamePath = '', _renameIsDir = false;
let _deletePath = '', _deleteType  = '';
let _dupSrcPath = '', _dupSrcType  = '';

// ─── LP ───────────────────────────────────────────────────────────────────────
const LP = {

    currentPath: '/',

    async init() {
        this.setupKeyboardShortcuts();
        this.setupShortcutReorder();
        this.setupSearchModal();
        this.setupTreeFilter();
        this.openSection('explorerSection');
        await this.connect();
    },

    // ── Connect ───────────────────────────────────────────────────────────────
    async connect() {
        try {
            const { data } = await axios.post(`${LP_CFG.apiBase}/connect`);
            if (data.success) {
                treeState.root         = data.root;
                treeState.originalRoot = data.root;
                this.currentPath       = data.root;
                this.setConnStatus(true, data.root);
                await this.loadTreeRoot(data.root);
            } else {
                this.setConnStatus(false, data.message);
                this.setTreeMsg(`<i class="bi bi-x-circle-fill" style="color:var(--red)"></i> ${this.esc(data.message)}`);
            }
        } catch (e) {
            this.setConnStatus(false, 'Erro de rede');
            this.setTreeMsg('<i class="bi bi-x-circle-fill" style="color:var(--red)"></i> Falha ao conectar.');
        }
    },

    setConnStatus(online, label) {
        document.getElementById('connDot').className = 'lp-conn-dot ' + (online ? 'online' : 'offline');
        document.getElementById('connLabel').textContent = online ? (label || 'Conectado') : (label || 'Erro');
        document.getElementById('sbDot').style.color = online ? 'var(--green)' : 'var(--red)';
        document.getElementById('sbConnLabel').textContent = online
            ? `${LP_CFG.username}@${LP_CFG.host} · ${treeState.root || ''}`
            : (label || 'Desconectado');
    },

    setTreeMsg(html) {
        const el = document.getElementById('treeStatus');
        el.innerHTML = html;
        el.style.display = 'flex';
    },

    // ── Tree ──────────────────────────────────────────────────────────────────
    async loadTreeRoot(path) {
        this.setTreeMsg('<i class="bi bi-arrow-repeat lp-spin"></i> Carregando…');
        document.getElementById('fileTree').innerHTML = '';

        const entries = await this.fetchDirectory(path);
        if (entries === null) return;

        treeState.expanded.add(path);
        document.getElementById('treeStatus').style.display = 'none';

        const el = this.createNode(path, path.split('/').filter(Boolean).pop() || path, 'dir', 0, true);
        document.getElementById('fileTree').appendChild(el);

        const wrap = this.createChildWrap(path);
        this.renderEntries(entries, path, 1, wrap);
        document.getElementById('fileTree').appendChild(wrap);

        this.updateBreadcrumb(path);
        this.updateFavButton(path);
    },

    async toggleDir(path, depth, arrowEl, wrapEl, iconSpanEl, iconInfo) {
        if (treeState.expanded.has(path)) {
            treeState.expanded.delete(path);
            arrowEl.classList.remove('open');
            wrapEl.style.display = 'none';
            iconSpanEl.innerHTML = `<i class="bi ${iconInfo.icon}" style="color:${iconInfo.color}"></i>`;
        } else {
            treeState.expanded.add(path);
            arrowEl.classList.add('open');
            wrapEl.style.display = 'block';
            iconSpanEl.innerHTML = `<i class="bi bi-folder2-open" style="color:${iconInfo.color}"></i>`;

            if (!treeState.cache[path]) {
                wrapEl.innerHTML = '<div class="lp-tree-msg"><i class="bi bi-arrow-repeat lp-spin"></i></div>';
                const entries = await this.fetchDirectory(path);
                if (entries === null) { wrapEl.innerHTML = ''; return; }
                wrapEl.innerHTML = '';
                this.renderEntries(entries, path, depth + 1, wrapEl);
            }
        }
    },

    renderEntries(entries, parentPath, depth, container) {
        for (const entry of entries) {
            try {
                const fullPath = parentPath.replace(/\/$/, '') + '/' + entry.name;
                const expanded = treeState.expanded.has(fullPath);
                const node = this.createNode(fullPath, entry.name, entry.type, depth, expanded);
                container.appendChild(node);

                if (entry.type === 'dir') {
                    const wrap = this.createChildWrap(fullPath);
                    if (expanded && treeState.cache[fullPath]) {
                        this.renderEntries(treeState.cache[fullPath], fullPath, depth + 1, wrap);
                    } else {
                        wrap.style.display = 'none';
                    }
                    container.appendChild(wrap);
                }
            } catch (err) {
                // Entry rendering failed — skip this item but continue with the rest
            }
        }
    },

    createNode(path, name, type, depth, isExpanded) {
        const fi    = getFileIcon(name, type);
        const isDir = type === 'dir';
        const indent = depth * 12;

        const node = document.createElement('div');
        node.className = 'lp-node';
        node.dataset.path = path;
        node.dataset.type = type;
        if (path === this.currentPath && isDir) node.classList.add('active');

        const inner = document.createElement('div');
        inner.className = 'lp-node-inner';
        inner.style.paddingLeft = (indent + 4) + 'px';

        const arrowSpan = document.createElement('span');
        arrowSpan.className = 'lp-node-arrow' + (isDir ? '' : ' spacer');
        arrowSpan.innerHTML = '<i class="bi bi-chevron-right"></i>';
        if (isDir && isExpanded) arrowSpan.classList.add('open');

        const iconSpan = document.createElement('span');
        iconSpan.className = 'lp-node-icon';
        const openIcon = isDir && isExpanded ? 'bi-folder2-open' : fi.icon;
        iconSpan.innerHTML = `<i class="bi ${openIcon}" style="color:${fi.color}"></i>`;

        const nameSpan = document.createElement('span');
        nameSpan.className = 'lp-node-name';
        nameSpan.title = name;
        nameSpan.textContent = name;

        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'lp-node-actions';

        if (isDir) {
            const isFav = LP_CFG.favorites.some(f => f.path === path);
            const favBtn = document.createElement('button');
            favBtn.className = 'lp-node-action-btn';
            favBtn.title = isFav ? 'Remover favorito' : 'Adicionar favorito';
            favBtn.innerHTML = `<i class="bi ${isFav ? 'bi-star-fill fav' : 'bi-star'}"></i>`;
            favBtn.addEventListener('click', e => { e.stopPropagation(); this.toggleFavorite(path, favBtn); });
            actionsDiv.appendChild(favBtn);
        }

        inner.appendChild(arrowSpan);
        inner.appendChild(iconSpan);
        inner.appendChild(nameSpan);
        inner.appendChild(actionsDiv);
        node.appendChild(inner);

        // Context menu
        node.addEventListener('contextmenu', e => LP_CTX.show(e, path, type));

        if (isDir) {
            node.addEventListener('click', async () => {
                this.setAllNodesInactive();
                node.classList.add('active');
                this.currentPath = path;
                this.updateBreadcrumb(path);
                this.updateFavButton(path);
                this.updateStatusPath(path);
                this.updateShortcutActive(path);

                const wrap = document.getElementById('tree-wrap-' + this.pathId(path));
                if (wrap) await this.toggleDir(path, depth, arrowSpan, wrap, iconSpan, fi);
            });
        } else {
            node.addEventListener('click', () => { this.setAllNodesInactive(); node.classList.add('active'); });
            node.addEventListener('dblclick', () => this.openFile(path));
        }

        return node;
    },

    createChildWrap(path) {
        const div = document.createElement('div');
        div.id = 'tree-wrap-' + this.pathId(path);
        div.style.display = treeState.expanded.has(path) ? 'block' : 'none';
        return div;
    },

    setAllNodesInactive() {
        document.querySelectorAll('.lp-node.active').forEach(n => n.classList.remove('active'));
    },

    async fetchDirectory(path) {
        if (treeState.cache[path]) return treeState.cache[path];
        try {
            const { data } = await axios.get(`${LP_CFG.apiBase}/directory`, { params: { path } });
            if (data.success) { treeState.cache[path] = data.entries; return data.entries; }
            this.toast('err', data.message);
            return null;
        } catch {
            this.toast('err', 'Erro ao listar diretório.');
            return null;
        }
    },

    async navigateTo(path) {
        // Re-root the tree at the target path so its contents are immediately visible
        this.currentPath = path;
        treeState.root   = path;
        await this.loadTreeRoot(path);
        this.updateShortcutActive(path);
    },

    async navigateUp() {
        const parts = this.currentPath.split('/').filter(Boolean);
        if (parts.length === 0) return; // already at '/', nothing above it

        parts.pop();
        const parent = '/' + parts.join('/');
        await this.navigateTo(parent);
    },

    async navigateHome() {
        const home = treeState.originalRoot;
        if (!home) return;
        this.currentPath = home;
        treeState.root   = home;
        await this.loadTreeRoot(home);
        this.updateShortcutActive(home);
    },

    async refreshCurrentDir() {
        delete treeState.cache[this.currentPath];
        const wrap = document.getElementById('tree-wrap-' + this.pathId(this.currentPath));
        if (wrap) {
            wrap.innerHTML = '<div class="lp-tree-msg"><i class="bi bi-arrow-repeat lp-spin"></i></div>';
            const entries = await this.fetchDirectory(this.currentPath);
            if (entries === null) return;
            const depth = Math.max(0,
                this.currentPath.split('/').filter(Boolean).length
                - (treeState.root || '').split('/').filter(Boolean).length
            );
            wrap.innerHTML = '';
            this.renderEntries(entries, this.currentPath, depth + 1, wrap);
        }
    },

    pathId(path) { return path.replace(/[^a-zA-Z0-9]/g, '_'); },

    // ── Breadcrumb ────────────────────────────────────────────────────────────
    updateBreadcrumb(path) {
        const bc   = document.getElementById('breadcrumb');
        const root = treeState.root || '/';

        bc.innerHTML = '';

        const rootLabel = root.split('/').filter(Boolean).pop() || '/';
        const rootEl = document.createElement('span');
        rootEl.className = 'lp-bc' + (path === root ? ' last' : '');
        rootEl.textContent = rootLabel;
        if (path !== root) rootEl.onclick = () => this.navigateTo(root);
        bc.appendChild(rootEl);

        const rootDepth  = root.split('/').filter(Boolean).length;
        const fullParts  = path.split('/').filter(Boolean);
        const subParts   = fullParts.slice(rootDepth);
        let cumulative   = root;

        subParts.forEach((part, i) => {
            cumulative = cumulative.replace(/\/$/, '') + '/' + part;
            const sep = document.createElement('span'); sep.className = 'lp-bc-sep'; sep.textContent = '›';
            bc.appendChild(sep);
            const el = document.createElement('span');
            const isLast = i === subParts.length - 1;
            el.className = 'lp-bc' + (isLast ? ' last' : '');
            el.textContent = part;
            if (!isLast) { const cp = cumulative; el.onclick = () => this.navigateTo(cp); }
            bc.appendChild(el);
        });

        document.getElementById('sb-path').textContent = path;
    },

    updateStatusPath(path) { document.getElementById('sb-path').textContent = path; },

    // ── File opening ──────────────────────────────────────────────────────────
    async openFile(path) {
        const existingIdx = tabState.tabs.findIndex(t => t.path === path);
        if (existingIdx >= 0) { this.switchTab(existingIdx); return; }

        this.toast('info', `Abrindo ${path.split('/').pop()}…`);

        try {
            const { data } = await axios.get(`${LP_CFG.apiBase}/file`, { params: { path } });
            if (!data.success) { this.toast('err', data.message); return; }
            if (monacoReady) this._doOpenFile(data);
            else pendingOpen = data;
        } catch { this.toast('err', 'Erro ao abrir arquivo.'); }
    },

    _doOpenFile(data) {
        const model = monaco.editor.createModel(data.content, data.language, monaco.Uri.parse('lp://' + data.path));
        // Read-only when the server says what's shown isn't the exact file
        // (see SFTPService::finishReadFile) or the user isn't an admin.
        const tab   = { path: data.path, name: data.name, language: data.language, model, viewState: null, modified: false, readonly: !!data.readonly || !LP_CFG.isAdmin };

        if (data.notice) this.toast('info', data.notice);

        model.onDidChangeContent(() => {
            if (tab.readonly) return;
            tab.modified = true;
            this.renderTabs();
            const btn = document.getElementById('btnSave');
            btn.disabled = false;
            btn.classList.add('save-active');
        });

        tabState.tabs.push(tab);
        this.switchTab(tabState.tabs.length - 1);

        // Recover content that failed to save earlier due to an expired session
        const backupKey = 'lp_unsaved_backup:' + data.path;
        const backup = localStorage.getItem(backupKey);
        if (backup !== null && backup !== data.content && !tab.readonly) {
            if (confirm(`Foi encontrado um backup local não salvo de "${data.name}" (de uma falha de sessão anterior). Restaurar esse conteúdo?`)) {
                model.setValue(backup);
                tab.modified = true;
                document.getElementById('btnSave').disabled = false;
                document.getElementById('btnSave').classList.add('save-active');
            }
            localStorage.removeItem(backupKey);
        }
    },

    switchTab(idx) {
        // Save editor state of previous tab
        const prev = tabState.activeIdx >= 0 ? tabState.tabs[tabState.activeIdx] : null;
        if (prev) prev.viewState = editor?.saveViewState();

        tabState.activeIdx = idx;
        const tab = tabState.tabs[idx];
        if (!tab) return;

        document.getElementById('welcome').style.display = 'none';
        document.getElementById('sb-lang').textContent   = tab.language;

        document.getElementById('editor-container').style.display = 'block';
        if (editor) {
            editor.setModel(tab.model);
            editor.updateOptions({ readOnly: tab.readonly });
            if (tab.viewState) editor.restoreViewState(tab.viewState);
            if (tab.readonly) editor.revealLine(tab.model.getLineCount());
            editor.focus();
        }
        const btn = document.getElementById('btnSave');
        btn.disabled = !tab.modified;
        btn.classList.toggle('save-active', tab.modified);
        document.getElementById('btnDeleteFile').disabled = false;

        this.renderTabs();
    },

    closeTab(idx) {
        const tab = tabState.tabs[idx];
        if (!tab) return;
        if (tab.modified && !confirm(`"${tab.name}" tem alterações não salvas. Fechar?`)) return;

        tab.model?.dispose();
        tabState.tabs.splice(idx, 1);

        if (tabState.tabs.length === 0) {
            tabState.activeIdx = -1;
            editor?.setModel(null);
            document.getElementById('welcome').style.display = 'flex';
            document.getElementById('editor-container').style.display = 'none';
            document.getElementById('btnSave').disabled       = true;
            document.getElementById('btnDeleteFile').disabled = true;
            document.getElementById('sb-lang').textContent   = '';
        } else {
            this.switchTab(Math.min(idx, tabState.tabs.length - 1));
        }
        this.renderTabs();
    },

    renderTabs() {
        const list = document.getElementById('tabs-list');
        list.innerHTML = '';
        tabState.tabs.forEach((tab, idx) => {
            const icon  = getFileIcon(tab.name, 'file').icon;
            const color = getFileIcon(tab.name, 'file').color;
            const div   = document.createElement('div');
            div.className = 'lp-tab' + (idx === tabState.activeIdx ? ' active' : '') + (tab.modified ? ' modified' : '');
            div.title = tab.path;
            div.innerHTML = `<span class="lp-tab-icon"><i class="bi ${icon}" style="color:${color}"></i></span>
                             <span class="lp-tab-name">${this.esc(tab.name)}</span>
                             <button class="lp-tab-close" title="Fechar"><i class="bi bi-x"></i></button>`;
            div.addEventListener('click', e => { if (!e.target.closest('.lp-tab-close')) this.switchTab(idx); });
            div.querySelector('.lp-tab-close').addEventListener('click', e => { e.stopPropagation(); this.closeTab(idx); });
            list.appendChild(div);
        });
    },

    // ── Save ──────────────────────────────────────────────────────────────────
    async saveCurrentFile() {
        if (tabState.activeIdx < 0) return;
        const tab = tabState.tabs[tabState.activeIdx];
        if (!tab) return;
        if (!LP_CFG.isAdmin) { this.toast('err', 'Seu usuário não tem permissão para alterar arquivos.'); return; }
        if (tab.readonly) { this.toast('err', 'Este arquivo foi aberto somente leitura e não pode ser salvo daqui.'); return; }
        const content = tab.model.getValue();

        try {
            const { data } = await axios.post(`${LP_CFG.apiBase}/file/save`, {
                path: tab.path, content,
            });
            if (data.success) {
                tab.modified = false;
                this.renderTabs();
                const btn = document.getElementById('btnSave');
                btn.disabled = true;
                btn.classList.remove('save-active');
                this.toast('ok', data.message);
            } else {
                this.toast('err', data.message);
            }
        } catch (err) {
            if (err?.response?.status === 419) {
                // Session/CSRF expired — back up the unsaved content so a page reload doesn't lose it.
                try { localStorage.setItem('lp_unsaved_backup:' + tab.path, content); } catch {}
                this.toast('err', 'Sessão expirada. Seu conteúdo foi salvo localmente — recarregue a página (F5) e tente novamente.');
            } else {
                this.toast('err', 'Erro ao salvar arquivo.');
            }
        }
    },

    // ── File operations ───────────────────────────────────────────────────────
    promptCreateFile(dirPath) {
        _createFileDir = dirPath || this.currentPath;
        const input = document.getElementById('inputFileName');
        input.value = '';
        document.getElementById('createFileDir').textContent = _createFileDir;

        const m = new bootstrap.Modal(document.getElementById('modalCreateFile'));
        m.show();
        document.getElementById('modalCreateFile').addEventListener('shown.bs.modal', () => input.focus(), { once: true });
        input.onkeydown = e => { if (e.key === 'Enter') this.doCreateFile(); };
    },

    async doCreateFile() {
        const name = document.getElementById('inputFileName').value.trim();
        if (!name) return;
        const path = _createFileDir.replace(/\/$/, '') + '/' + name;
        bootstrap.Modal.getInstance(document.getElementById('modalCreateFile'))?.hide();

        try {
            const { data } = await axios.post(`${LP_CFG.apiBase}/fs/file`, { path });
            if (data.success) {
                delete treeState.cache[_createFileDir];
                await this.refreshCurrentDir();
                this.toast('ok', `"${name}" criado.`);
                await this.openFile(path);
            } else {
                this.toast('err', data.message);
            }
        } catch { this.toast('err', 'Erro ao criar arquivo.'); }
    },

    promptCreateDir(dirPath) {
        _createDirParent = dirPath || this.currentPath;
        const input = document.getElementById('inputDirName');
        input.value = '';
        document.getElementById('createDirParent').textContent = _createDirParent;

        const m = new bootstrap.Modal(document.getElementById('modalCreateDir'));
        m.show();
        document.getElementById('modalCreateDir').addEventListener('shown.bs.modal', () => input.focus(), { once: true });
        input.onkeydown = e => { if (e.key === 'Enter') this.doCreateDir(); };
    },

    async doCreateDir() {
        const name = document.getElementById('inputDirName').value.trim();
        if (!name) return;
        const path = _createDirParent.replace(/\/$/, '') + '/' + name;
        bootstrap.Modal.getInstance(document.getElementById('modalCreateDir'))?.hide();

        try {
            const { data } = await axios.post(`${LP_CFG.apiBase}/fs/dir`, { path });
            if (data.success) {
                delete treeState.cache[_createDirParent];
                await this.refreshCurrentDir();
                this.toast('ok', `"${name}" criada.`);
            } else {
                this.toast('err', data.message);
            }
        } catch { this.toast('err', 'Erro ao criar pasta.'); }
    },

    promptRename(path, isDir) {
        _renamePath  = path;
        _renameIsDir = isDir;
        const input = document.getElementById('inputRename');
        input.value = path.split('/').pop();

        const m = new bootstrap.Modal(document.getElementById('modalRename'));
        m.show();
        document.getElementById('modalRename').addEventListener('shown.bs.modal', () => { input.focus(); input.select(); }, { once: true });
        input.onkeydown = e => { if (e.key === 'Enter') this.doRename(); };
    },

    async doRename() {
        const newName = document.getElementById('inputRename').value.trim();
        if (!newName) return;
        const dir = _renamePath.split('/').slice(0, -1).join('/');
        const to  = dir + '/' + newName;
        bootstrap.Modal.getInstance(document.getElementById('modalRename'))?.hide();

        try {
            const { data } = await axios.post(`${LP_CFG.apiBase}/fs/rename`, { from: _renamePath, to });
            if (data.success) {
                delete treeState.cache[dir];
                const tabIdx = tabState.tabs.findIndex(t => t.path === _renamePath);
                if (tabIdx >= 0) this.closeTab(tabIdx);
                if (_renameIsDir) this.currentPath = to;
                await this.refreshCurrentDir();
                this.toast('ok', 'Renomeado com sucesso.');
            } else {
                this.toast('err', data.message);
            }
        } catch { this.toast('err', 'Erro ao renomear.'); }
    },

    promptDeleteCurrentFile() {
        if (tabState.activeIdx < 0) return;
        const tab = tabState.tabs[tabState.activeIdx];
        if (!tab) return;
        this.promptDelete(tab.path, 'file');
    },

    promptDelete(path, type) {
        _deletePath = path;
        _deleteType = type;
        document.getElementById('deleteItemName').textContent = path.split('/').pop();
        new bootstrap.Modal(document.getElementById('modalDelete')).show();
    },

    async doDelete() {
        const path = _deletePath;
        bootstrap.Modal.getInstance(document.getElementById('modalDelete'))?.hide();

        try {
            const { data } = await axios.delete(`${LP_CFG.apiBase}/fs/delete`, { params: { path } });
            if (data.success) {
                const dir = path.split('/').slice(0, -1).join('/');
                delete treeState.cache[dir];
                const tabIdx = tabState.tabs.findIndex(t => t.path === path);
                if (tabIdx >= 0) this.closeTab(tabIdx);
                await this.refreshCurrentDir();
                this.toast('info', 'Excluído.');
            } else {
                this.toast('err', data.message);
            }
        } catch { this.toast('err', 'Erro ao excluir.'); }
    },

    // ── Favorites ─────────────────────────────────────────────────────────────
    async toggleFavorite(path, btnEl) {
        const isFav = LP_CFG.favorites.some(f => f.path === path);
        if (isFav) {
            await this.removeFavorite(path);
            if (btnEl) { btnEl.title = 'Adicionar favorito'; btnEl.innerHTML = '<i class="bi bi-star"></i>'; }
        } else {
            await this.addFavorite(path);
            if (btnEl) { btnEl.title = 'Remover favorito'; btnEl.innerHTML = '<i class="bi bi-star-fill fav"></i>'; }
        }
    },

    async addFavorite(path) {
        try {
            const { data } = await axios.post(`${LP_CFG.apiBase}/favorites`, { path, label: path.split('/').pop() });
            if (data.success) {
                LP_CFG.favorites.push(data.favorite);
                this.toast('ok', `"${data.favorite.label}" favoritado.`);
                this.updateFavButton(this.currentPath);
            }
        } catch { this.toast('err', 'Erro ao favoritar.'); }
    },

    async removeFavorite(path) {
        try {
            const { data } = await axios.delete(`${LP_CFG.apiBase}/favorites`, { params: { path } });
            if (data.success) {
                LP_CFG.favorites = LP_CFG.favorites.filter(f => f.path !== path);
                this.toast('info', 'Favorito removido.');
                this.updateFavButton(this.currentPath);
                const favNode = document.querySelector(`#favBody .lp-node[data-path="${CSS.escape(path)}"]`);
                if (favNode) favNode.remove();
            }
        } catch { this.toast('err', 'Erro ao remover favorito.'); }
    },

    toggleFavoriteCurrentDir() { this.toggleFavorite(this.currentPath, null); },

    updateFavButton(path) {
        const isFav = LP_CFG.favorites.some(f => f.path === path);
        const icon  = document.getElementById('btnFavIcon');
        if (!icon) return;
        icon.className = isFav ? 'bi bi-star-fill' : 'bi bi-star';
        icon.style.color = isFav ? 'var(--yellow)' : '';
    },

    // ── Sections ──────────────────────────────────────────────────────────────
    toggleSection(id) {
        const sec  = document.getElementById(id);
        if (!sec) return;
        const hdr  = sec.querySelector('.lp-section-hdr');
        const body = sec.querySelector('.lp-section-body');
        const arr  = hdr?.querySelector('.lp-arrow');
        const col  = hdr.classList.toggle('collapsed');
        body?.classList.toggle('hidden', col);
        if (arr) arr.style.transform = col ? 'rotate(-90deg)' : '';
    },

    openSection(id) {
        const sec = document.getElementById(id);
        if (!sec) return;
        sec.querySelector('.lp-section-hdr')?.classList.remove('collapsed');
        sec.querySelector('.lp-section-body')?.classList.remove('hidden');
        const arr = sec.querySelector('.lp-arrow');
        if (arr) arr.style.transform = '';
    },

    // ── Shortcuts ─────────────────────────────────────────────────────────────
    _scTargetPath: null,

    promptAddShortcut(path) {
        const target = path || this.currentPath;
        document.getElementById('inputShortcutName').value = target.split('/').pop() || target;
        document.getElementById('inputShortcutPath').value = target;
        document.getElementById('inputShortcutIcon').value = 'bi-folder-symlink-fill';
        this.selectIcon('bi-folder-symlink-fill', 'add');

        const m = new bootstrap.Modal(document.getElementById('modalAddShortcut'));
        m.show();
        document.getElementById('modalAddShortcut').addEventListener('shown.bs.modal', () => {
            const n = document.getElementById('inputShortcutName');
            n.focus(); n.select();
        }, { once: true });
    },

    async doAddShortcut() {
        const name = document.getElementById('inputShortcutName').value.trim();
        const path = document.getElementById('inputShortcutPath').value.trim();
        const icon = document.getElementById('inputShortcutIcon').value || 'bi-folder-symlink-fill';
        if (!name || !path) { this.toast('err', 'Nome e caminho são obrigatórios.'); return; }
        bootstrap.Modal.getInstance(document.getElementById('modalAddShortcut'))?.hide();

        try {
            const { data } = await axios.post(`${LP_CFG.apiBase}/shortcuts`, { name, path, icon });
            if (data.success) {
                LP_CFG.shortcuts.push(data.shortcut);
                this._renderShortcutBtn(data.shortcut);
                this.toast('ok', `Atalho "${name}" adicionado.`);
                this.updateShortcutActive(this.currentPath);
            } else {
                this.toast('err', data.message);
            }
        } catch { this.toast('err', 'Erro ao adicionar atalho.'); }
    },

    async removeShortcut(id) {
        try {
            const { data } = await axios.delete(`${LP_CFG.apiBase}/shortcuts/${id}`);
            if (data.success) {
                LP_CFG.shortcuts = LP_CFG.shortcuts.filter(s => s.id !== id);
                document.getElementById('sc-btn-' + id)?.remove();
                this._checkShortcutsEmpty();
                this.toast('info', 'Atalho removido.');
            } else {
                this.toast('err', data.message);
            }
        } catch { this.toast('err', 'Erro ao remover atalho.'); }
    },

    _editScId: null,

    promptEditShortcut(id, name, path, icon) {
        this._editScId = id;
        document.getElementById('editShortcutName').value = name;
        document.getElementById('editShortcutPath').value = path;
        document.getElementById('editShortcutIcon').value = icon;
        this.selectIcon(icon, 'edit');

        const m = new bootstrap.Modal(document.getElementById('modalEditShortcut'));
        m.show();
        document.getElementById('modalEditShortcut').addEventListener('shown.bs.modal', () => {
            document.getElementById('editShortcutName').focus();
        }, { once: true });
    },

    selectIcon(icon, picker) {
        const inputId = picker === 'add' ? 'inputShortcutIcon' : 'editShortcutIcon';
        document.querySelectorAll(`.lp-icon-pick[data-picker="${picker}"]`).forEach(btn => {
            btn.classList.toggle('selected', btn.dataset.icon === icon);
        });
        document.getElementById(inputId).value = icon;
    },

    async doEditShortcut() {
        const id   = this._editScId;
        const name = document.getElementById('editShortcutName').value.trim();
        const path = document.getElementById('editShortcutPath').value.trim();
        const icon = document.getElementById('editShortcutIcon').value || 'bi-folder-symlink-fill';

        if (!name || !path) { this.toast('err', 'Nome e caminho são obrigatórios.'); return; }

        bootstrap.Modal.getInstance(document.getElementById('modalEditShortcut'))?.hide();

        try {
            const { data } = await axios.patch(`${LP_CFG.apiBase}/shortcuts/${id}`, { name, path, icon });
            if (data.success) {
                const sc = data.shortcut;
                // Update in-memory list
                const idx = LP_CFG.shortcuts.findIndex(s => s.id === id);
                if (idx >= 0) LP_CFG.shortcuts[idx] = sc;

                // Update DOM button
                const btn = document.getElementById('sc-btn-' + id);
                if (btn) {
                    btn.dataset.path = sc.path;
                    btn.title        = sc.path + ' (arraste para mudar a ordem)';
                    btn.querySelector('.sc-icon').className = `bi ${sc.icon} sc-icon`;
                    btn.querySelector('.sc-name').textContent = sc.name;
                    // Update onclick for edit button
                    const editBtn = btn.querySelector('.lp-shortcut-edit');
                    if (editBtn) editBtn.setAttribute('onclick',
                        `event.stopPropagation();LP.promptEditShortcut(${sc.id},'${sc.name.replace(/'/g,"\\'")}','${sc.path.replace(/'/g,"\\'")}','${sc.icon}')`);
                }

                this.toast('ok', 'Atalho atualizado.');
            } else {
                this.toast('err', data.message);
            }
        } catch { this.toast('err', 'Erro ao atualizar atalho.'); }
    },

    _renderShortcutBtn(sc) {
        const body    = document.getElementById('shortcutsBody');
        const emptyEl = document.getElementById('shortcutsEmpty');
        if (emptyEl) emptyEl.remove();

        const addBtn = body.querySelector('.lp-sc-add');

        const btn = document.createElement('button');
        btn.className    = 'lp-shortcut-btn';
        btn.id           = 'sc-btn-' + sc.id;
        btn.dataset.id   = sc.id;
        btn.dataset.path = sc.path;
        btn.title        = sc.path + ' (arraste para mudar a ordem)';
        btn.draggable    = true;
        btn.innerHTML = `<i class="bi bi-grip-vertical sc-grip"></i>
                         <i class="bi ${sc.icon} sc-icon" style="color:var(--blue)"></i>
                         <span class="sc-name">${this.esc(sc.name)}</span>
                         <span class="lp-sc-btns">
                             <span class="lp-shortcut-edit" onclick="event.stopPropagation();LP.promptEditShortcut(${sc.id},'${sc.name.replace(/'/g,"\\'")}','${sc.path.replace(/'/g,"\\'")}','${sc.icon}')" title="Editar atalho"><i class="bi bi-pencil"></i></span>
                             <span class="lp-shortcut-del" onclick="event.stopPropagation();LP.removeShortcut(${sc.id})" title="Remover atalho"><i class="bi bi-x"></i></span>
                         </span>`;
        btn.addEventListener('click', () => this.navigateTo(sc.path));
        body.insertBefore(btn, addBtn || null);
    },

    // ── Reordenar atalhos ────────────────────────────────────────────────────
    _scButtons() {
        return [...document.querySelectorAll('#shortcutsBody .lp-shortcut-btn')];
    },

    /** Põe os botões na ordem dos ids (antes do "+ Adicionar atalho"). */
    _placeShortcuts(ids) {
        const body   = document.getElementById('shortcutsBody');
        const addBtn = body.querySelector('.lp-sc-add');
        ids.forEach(id => {
            const btn = document.getElementById('sc-btn-' + id);
            if (btn) body.insertBefore(btn, addBtn || null);
        });
    },

    /** Grava a ordem atual da tela; se falhar, volta para a ordem anterior. */
    async _saveShortcutOrder(previousIds) {
        const ids = this._scButtons().map(b => +b.dataset.id);
        if (ids.join(',') === previousIds.join(',')) return;

        const byId = new Map(LP_CFG.shortcuts.map(s => [s.id, s]));
        LP_CFG.shortcuts = ids.map(id => byId.get(id)).filter(Boolean);

        try {
            const { data } = await axios.post(`${LP_CFG.apiBase}/shortcuts/reorder`, { ids });
            if (!data.success) throw new Error(data.message);
        } catch (e) {
            this._placeShortcuts(previousIds);
            LP_CFG.shortcuts = previousIds.map(id => byId.get(id)).filter(Boolean);
            this.toast('err', 'Não foi possível salvar a ordem dos atalhos.');
        }
    },

    sortShortcuts(dir) {
        const btns = this._scButtons();
        if (btns.length < 2) return;
        const before = btns.map(b => +b.dataset.id);
        const name = b => b.querySelector('.sc-name')?.textContent.trim() || '';
        btns.sort((a, b) => name(a).localeCompare(name(b), 'pt-BR', { sensitivity: 'base', numeric: true }) * (dir === 'desc' ? -1 : 1));
        this._placeShortcuts(btns.map(b => +b.dataset.id));
        this._saveShortcutOrder(before);
        this.openSection('shortcutsSection');
        this.toast('info', dir === 'desc' ? 'Atalhos ordenados de Z a A.' : 'Atalhos ordenados de A a Z.');
    },

    setupShortcutReorder() {
        const body = document.getElementById('shortcutsBody');
        if (!body) return;
        let dragging = null, before = null;

        const clearMarks = () => body.querySelectorAll('.sc-drop-before, .sc-drop-after')
            .forEach(el => el.classList.remove('sc-drop-before', 'sc-drop-after'));

        body.addEventListener('dragstart', e => {
            const btn = e.target.closest?.('.lp-shortcut-btn');
            if (!btn) return;
            dragging = btn;
            before = this._scButtons().map(b => +b.dataset.id);
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', btn.dataset.id);
            body.classList.add('sc-sorting');
            requestAnimationFrame(() => btn.classList.add('sc-dragging'));
        });

        body.addEventListener('dragover', e => {
            if (!dragging) return;          // só reage a atalhos, não a arquivos de fora
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            clearMarks();
            // Mira no atalho sob o cursor; nas sobras (fim da lista), cola após o último.
            let target = e.target.closest?.('.lp-shortcut-btn');
            let after;
            if (target) {
                const r = target.getBoundingClientRect();
                after = e.clientY > r.top + r.height / 2;
            } else {
                const all = this._scButtons();
                target = all[all.length - 1];
                after = true;
            }
            if (target && target !== dragging) target.classList.add(after ? 'sc-drop-after' : 'sc-drop-before');
        });

        body.addEventListener('drop', e => {
            if (!dragging) return;
            e.preventDefault();
            const mark = body.querySelector('.sc-drop-before, .sc-drop-after');
            if (mark) {
                if (mark.classList.contains('sc-drop-after')) mark.after(dragging);
                else mark.before(dragging);
            }
            clearMarks();
            this._saveShortcutOrder(before);
        });

        body.addEventListener('dragend', () => {
            dragging?.classList.remove('sc-dragging');
            body.classList.remove('sc-sorting');
            clearMarks();
            dragging = null;
        });
    },

    _checkShortcutsEmpty() {
        const body = document.getElementById('shortcutsBody');
        const btns = body.querySelectorAll('.lp-shortcut-btn');
        if (btns.length === 0 && !document.getElementById('shortcutsEmpty')) {
            const empty = document.createElement('div');
            empty.id = 'shortcutsEmpty';
            empty.className = 'lp-shortcuts-empty';
            empty.innerHTML = 'Nenhum atalho ainda.<br>Navegue até uma pasta e clique em <strong>+ Atalho</strong>.';
            const addBtn = body.querySelector('.lp-sc-add');
            body.insertBefore(empty, addBtn || null);
        }
    },

    updateShortcutActive(path) {
        document.querySelectorAll('.lp-shortcut-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.path === path);
        });
    },

    // ── Terminal ──────────────────────────────────────────────────────────────
    openTerminalTab(cwd) {
        if (!LP_CFG.isAdmin) { this.toast('err', 'Acesso ao terminal restrito a administradores.'); return; }
        const path = cwd || this.currentPath || treeState.root || '';
        const url = LP_CFG.terminalUrl + (path ? '?path=' + encodeURIComponent(path) : '');
        window.open(url, '_blank', 'noopener');
    },

    // ── Search ────────────────────────────────────────────────────────────────
    setupSearchModal() {
        const input   = document.getElementById('searchInput');
        const results = document.getElementById('searchResults');
        let timer = null;

        input.addEventListener('input', () => {
            clearTimeout(timer);
            const q = input.value.trim();
            if (q.length < 2) { results.innerHTML = ''; return; }
            results.innerHTML = '<div class="lp-tree-msg"><i class="bi bi-arrow-repeat lp-spin"></i> Pesquisando…</div>';

            timer = setTimeout(async () => {
                try {
                    const { data } = await axios.get(`${LP_CFG.apiBase}/search`, { params: { q } });
                    if (!data.success) { results.innerHTML = `<div class="lp-tree-msg" style="color:var(--red)">${this.esc(data.message)}</div>`; return; }
                    if (!data.results.length) { results.innerHTML = '<div class="lp-tree-msg">Nenhum arquivo encontrado.</div>'; return; }

                    results.innerHTML = '';
                    data.results.forEach(r => {
                        const { icon, color } = getFileIcon(r.name, 'file');
                        const item = document.createElement('div');
                        item.className = 'lp-search-item';
                        item.innerHTML = `<i class="bi ${icon}" style="color:${color};font-size:14px;flex-shrink:0"></i>
                            <div><div class="lp-si-name">${this.esc(r.name)}</div><div class="lp-si-path">${this.esc(r.directory)}</div></div>`;
                        item.addEventListener('click', () => {
                            bootstrap.Modal.getInstance(document.getElementById('searchModal'))?.hide();
                            this.openFile(r.path);
                        });
                        results.appendChild(item);
                    });
                } catch { results.innerHTML = '<div class="lp-tree-msg" style="color:var(--red)">Erro na pesquisa.</div>'; }
            }, 400);
        });

        document.getElementById('searchModal').addEventListener('hidden.bs.modal', () => { input.value = ''; results.innerHTML = ''; });
        document.getElementById('searchModal').addEventListener('shown.bs.modal', () => input.focus());
    },

    // ── Tree filter ───────────────────────────────────────────────────────────
    setupTreeFilter() {
        document.getElementById('treeFilter').addEventListener('input', e => {
            const q = e.target.value.toLowerCase();
            document.querySelectorAll('#fileTree .lp-node').forEach(node => {
                const name = node.dataset.path?.split('/').pop()?.toLowerCase() || '';
                node.style.display = (!q || name.includes(q)) ? '' : 'none';
            });
        });
    },

    // ── Keyboard shortcuts ────────────────────────────────────────────────────
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', e => {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); this.saveCurrentFile(); }
            if ((e.ctrlKey || e.metaKey) && e.key === 'w') { e.preventDefault(); if (tabState.activeIdx >= 0) this.closeTab(tabState.activeIdx); }
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') { e.preventDefault(); new bootstrap.Modal(document.getElementById('searchModal')).show(); }
            if ((e.ctrlKey || e.metaKey) && e.key === '`') { e.preventDefault(); this.openTerminalTab(); }
            if (e.altKey && e.key === 'ArrowUp') { e.preventDefault(); this.navigateUp(); }
        });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') LP_CTX.hide(); });
    },

    // ── Toast ─────────────────────────────────────────────────────────────────
    toast(type, msg) {
        const icons = { ok: 'bi-check-circle-fill', err: 'bi-x-circle-fill', info: 'bi-info-circle-fill' };
        const t = document.createElement('div');
        t.className = `lp-toast ${type}`;
        t.innerHTML = `<i class="bi ${icons[type]}"></i> ${this.esc(String(msg))}`;
        document.getElementById('toast-wrap').appendChild(t);
        setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; }, 2500);
        setTimeout(() => t.remove(), 2900);
    },

    promptDuplicate(path, type) {
        _dupSrcPath = path;
        _dupSrcType = type;

        const name    = path.split('/').pop();
        const isDir   = type === 'dir';
        const dotIdx  = isDir ? -1 : name.lastIndexOf('.');
        const base    = dotIdx > 0 ? name.slice(0, dotIdx) : name;
        const ext     = dotIdx > 0 ? name.slice(dotIdx) : '';
        const newName = base + '_copy' + ext;

        const input = document.getElementById('inputDuplicateName');
        input.value = newName;

        const m = new bootstrap.Modal(document.getElementById('modalDuplicate'));
        m.show();
        document.getElementById('modalDuplicate').addEventListener('shown.bs.modal', () => { input.focus(); input.select(); }, { once: true });
        input.onkeydown = e => { if (e.key === 'Enter') this.doConfirmDuplicate(); };
    },

    async doConfirmDuplicate() {
        const newName = document.getElementById('inputDuplicateName').value.trim();
        if (!newName) return;
        const dir = _dupSrcPath.split('/').slice(0, -1).join('/');
        const to  = dir + '/' + newName;
        bootstrap.Modal.getInstance(document.getElementById('modalDuplicate'))?.hide();

        try {
            const { data } = await axios.post(`${LP_CFG.apiBase}/fs/duplicate`, { from: _dupSrcPath, to });
            if (data.success) {
                delete treeState.cache[dir];
                const wrap = document.getElementById('tree-wrap-' + this.pathId(dir));
                if (wrap) {
                    wrap.innerHTML = '<div class="lp-tree-msg"><i class="bi bi-arrow-repeat lp-spin"></i></div>';
                    const entries = await this.fetchDirectory(dir);
                    if (entries !== null) {
                        const depth = Math.max(0, dir.split('/').filter(Boolean).length - (treeState.root || '').split('/').filter(Boolean).length);
                        wrap.innerHTML = '';
                        this.renderEntries(entries, dir, depth + 1, wrap);
                    }
                }
                this.toast('ok', `"${newName}" criado.`);
            } else {
                this.toast('err', data.message);
            }
        } catch { this.toast('err', 'Erro ao duplicar.'); }
    },

    esc(str) {
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    },
};

// ─── Context menu ─────────────────────────────────────────────────────────────
const LP_CTX = {
    path: null,
    type: null,

    show(e, path, type) {
        e.preventDefault();
        e.stopPropagation();
        this.path = path;
        this.type = type;

        const isDir  = type === 'dir';
        const isFile = !isDir;
        const menu   = document.getElementById('ctx-menu');

        document.getElementById('ctx-open').style.display      = isFile ? '' : 'none';
        document.getElementById('ctx-new-file').style.display  = isDir  ? '' : 'none';
        document.getElementById('ctx-new-dir').style.display   = isDir  ? '' : 'none';
        document.getElementById('ctx-sep-dir').style.display   = isDir  ? '' : 'none';
        document.getElementById('ctx-terminal').style.display  = isDir  ? '' : 'none';
        document.getElementById('ctx-shortcut').style.display  = isDir  ? '' : 'none';
        document.getElementById('ctx-sep-term').style.display  = isDir  ? '' : 'none';

        menu.style.display = 'block';
        menu.style.left    = e.clientX + 'px';
        menu.style.top     = e.clientY + 'px';

        requestAnimationFrame(() => {
            const r = menu.getBoundingClientRect();
            if (r.right  > window.innerWidth)  menu.style.left = (e.clientX - r.width)  + 'px';
            if (r.bottom > window.innerHeight) menu.style.top  = (e.clientY - r.height) + 'px';
        });

        setTimeout(() => document.addEventListener('click', () => this.hide(), { once: true }), 0);
    },

    hide() { document.getElementById('ctx-menu').style.display = 'none'; },

    openFile()         { this.hide(); LP.openFile(this.path); },
    promptNewFile()    { this.hide(); LP.promptCreateFile(this.path); },
    promptNewDir()     { this.hide(); LP.promptCreateDir(this.path); },
    promptRename()     { this.hide(); LP.promptRename(this.path, this.type === 'dir'); },
    promptDelete()     { this.hide(); LP.promptDelete(this.path, this.type); },
    openInTerminal()   { this.hide(); LP.openTerminalTab(this.path); },
    promptShortcut()   { this.hide(); LP.promptAddShortcut(this.path); },
    promptDuplicate()  { this.hide(); LP.promptDuplicate(this.path, this.type); },

    copyPath() {
        this.hide();
        const text = this.path;
        const done = () => LP.toast('ok', 'Caminho copiado.');

        // navigator.clipboard only exists on HTTPS/localhost — the panel is
        // usually reached over plain HTTP by IP, so fall back to execCommand.
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done, () => LP.toast('err', 'Não foi possível copiar.'));
            return;
        }
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity  = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy') ? done() : LP.toast('err', 'Não foi possível copiar.'); }
        catch { LP.toast('err', 'Não foi possível copiar.'); }
        ta.remove();
    },
};

// ─── Monaco editor ────────────────────────────────────────────────────────────
require(['vs/editor/editor.main'], function () {
    monacoReady = true;

    monaco.editor.defineTheme('techiapanel-dark', {
        base   : 'vs-dark',
        inherit: true,
        rules  : [],
        colors : {
            'editor.background'               : '#0b0f1c',
            'editor.lineHighlightBackground'  : '#ffffff08',
            'editor.lineHighlightBorder'      : '#00000000',
            'editorLineNumber.foreground'     : '#475569',
            'editorLineNumber.activeForeground': '#c7d2fe',
            'editor.selectionBackground'      : '#6366f155',
            'editor.inactiveSelectionBackground': '#6366f12a',
            'editorCursor.foreground'         : '#a5b4fc',
            'editorIndentGuide.background1'   : '#1e2436',
            'editorIndentGuide.activeBackground1': '#4f46e5aa',
            'editorGutter.background'         : '#0b0f1c',
            'minimap.background'              : '#0b0f1c',
            'editorWidget.background'         : '#11162a',
            'editorWidget.border'             : '#2a3150',
            'scrollbarSlider.background'      : '#ffffff14',
            'scrollbarSlider.hoverBackground' : '#818cf855',
        },
    });

    editor = monaco.editor.create(document.getElementById('editor-container'), {
        theme               : 'techiapanel-dark',
        fontSize            : 14,
        fontFamily          : "'JetBrains Mono', 'Fira Code', Consolas, monospace",
        fontLigatures       : true,
        lineNumbers         : 'on',
        minimap             : { enabled: true },
        wordWrap            : 'off',
        scrollBeyondLastLine: false,
        automaticLayout     : true,
        tabSize             : 4,
        insertSpaces        : true,
        smoothScrolling     : true,
        cursorBlinking      : 'smooth',
        cursorSmoothCaretAnimation: 'on',
        padding             : { top: 10 },
    });

    editor.onDidChangeCursorPosition(e => {
        document.getElementById('sb-pos').textContent = `Ln ${e.position.lineNumber}, Col ${e.position.column}`;
    });

    editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, () => LP.saveCurrentFile());

    if (pendingOpen) { LP._doOpenFile(pendingOpen); pendingOpen = null; }
});

// ─── Boot ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => LP.init());
</script>
</body>
</html>
