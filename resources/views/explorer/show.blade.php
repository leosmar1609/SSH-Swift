<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $connection->name }} — LeoPanel</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@4.19.0/css/xterm.css">

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

        /* ── Log viewer ── */
        #log-viewer { flex: 1; display: none; flex-direction: column; overflow: hidden; background: #0a0e14; }
        #log-viewer.active { display: flex; }
        #log-toolbar { height: 32px; background: var(--surface); border-bottom: 1px solid var(--border2); display: flex; align-items: center; padding: 0 10px; gap: 8px; flex-shrink: 0; font-size: 12px; }
        .log-live-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--green); flex-shrink: 0; animation: log-pulse 1.4s ease-in-out infinite; }
        @keyframes log-pulse { 0%,100%{opacity:1;box-shadow:0 0 4px var(--green)} 50%{opacity:.5;box-shadow:none} }
        #log-file-name { color: var(--muted); font-family: 'JetBrains Mono',monospace; font-size: 11px; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        #log-updated { font-size: 11px; color: var(--subtle); white-space: nowrap; }
        .log-tb-btn { background: transparent; border: none; color: var(--subtle); cursor: pointer; padding: 3px 8px; border-radius: 4px; font-size: 11.5px; display: flex; align-items: center; gap: 4px; }
        .log-tb-btn:hover { background: rgba(255,255,255,.07); color: var(--text); }
        .log-tb-btn.active { color: var(--green); }
        #log-content-wrap { flex: 1; overflow-y: auto; overflow-x: auto; padding: 8px 12px; }
        #log-content { font-family: 'JetBrains Mono', Consolas, monospace; font-size: 12.5px; line-height: 1.55; color: #c9d1d9; white-space: pre; margin: 0; }
        #log-content .log-err  { color: #f85149; }
        #log-content .log-warn { color: #e3b341; }
        #log-content .log-info { color: #58a6ff; }
        #log-content .log-ok   { color: #3fb950; }

        /* ── Terminal panel ── */
        #term-resize-bar { height: 4px; background: var(--border2); cursor: row-resize; flex-shrink: 0; display: none; }
        #term-resize-bar:hover, #term-resize-bar.dragging { background: var(--blue-dim); }
        #term-panel { display: none; flex-direction: column; flex-shrink: 0; height: 260px; min-height: 80px; background: #0d1117; }
        #term-panel.open { display: flex; }
        #term-header { height: 30px; background: var(--surface); border-bottom: 1px solid var(--border2); border-top: 1px solid var(--border); display: flex; align-items: center; padding: 0 10px; gap: 8px; flex-shrink: 0; font-size: 12px; color: var(--muted); user-select: none; }
        #term-header i.bi-terminal-fill { color: var(--green); }
        #term-cwd { font-family: 'JetBrains Mono', monospace; font-size: 11px; color: var(--blue); flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        #term-close { background: transparent; border: none; color: var(--subtle); cursor: pointer; padding: 2px 5px; border-radius: 3px; font-size: 13px; margin-left: auto; }
        #term-close:hover { color: var(--red); background: rgba(248,81,73,.12); }
        #term-container { flex: 1; padding: 4px 0 0 6px; overflow: hidden; }
        #term-container .xterm { height: 100%; }
        #term-container .xterm-viewport { overflow-y: auto !important; }

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
    </style>
</head>
<body>
<div id="lp-ide">

{{-- ── Topbar ── --}}
<header class="lp-topbar">
    <a href="{{ route('connections.index') }}" class="lp-topbar-brand" title="Servidores">
        <i class="bi bi-terminal-fill" style="color:var(--blue)"></i>
        Leo<span>Panel</span>
    </a>
    <div class="lp-topbar-sep"></div>
    <div class="lp-topbar-conn">
        <i class="bi bi-hdd-fill" style="color:var(--blue-dim)"></i>
        <strong>{{ $connection->name }}</strong>
        <span style="color:var(--subtle)">{{ $connection->username }}@{{ $connection->host }}:{{ $connection->port }}</span>
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

        <div class="lp-sidebar-actions">
            <button class="lp-act-btn" onclick="LP.promptCreateFile()" title="Novo arquivo">
                <i class="bi bi-file-earmark-plus"></i> Arquivo
            </button>
            <button class="lp-act-btn" onclick="LP.promptCreateDir()" title="Nova pasta">
                <i class="bi bi-folder-plus"></i> Pasta
            </button>
            <button class="lp-act-btn" onclick="LP.terminalToggle()" title="Terminal (Ctrl+`)">
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
                </div>
                <div class="lp-section-body lp-shortcuts-body" id="shortcutsBody">
                    @forelse($shortcuts as $sc)
                    <button class="lp-shortcut-btn"
                            id="sc-btn-{{ $sc->id }}"
                            onclick="LP.navigateTo('{{ $sc->path }}')"
                            data-id="{{ $sc->id }}"
                            data-path="{{ $sc->path }}"
                            title="{{ $sc->path }}">
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
                <button class="lp-tb-btn" id="btnSave" onclick="LP.saveCurrentFile()" disabled title="Salvar (Ctrl+S)">
                    <i class="bi bi-floppy2"></i>
                    <span style="font-size:12px">Salvar</span>
                </button>
                <button class="lp-tb-btn" id="btnDeleteFile" onclick="LP.promptDeleteCurrentFile()" disabled title="Excluir arquivo" style="color:var(--red)">
                    <i class="bi bi-trash3"></i>
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
                <h2>LeoPanel Explorer</h2>
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

            <div id="log-viewer">
                <div id="log-toolbar">
                    <span class="log-live-dot"></span>
                    <span style="color:var(--green);font-size:11px;font-weight:600;letter-spacing:.3px">LIVE</span>
                    <span id="log-file-name"></span>
                    <span id="log-updated"></span>
                    <button class="log-tb-btn" id="btnAutoScroll" onclick="LP_LOG.toggleAutoScroll()" title="Auto-scroll">
                        <i class="bi bi-arrow-down-circle"></i> Auto-scroll
                    </button>
                    <button class="log-tb-btn" onclick="LP_LOG.scrollBottom()" title="Ir para o fim">
                        <i class="bi bi-skip-end-fill"></i>
                    </button>
                    <button class="log-tb-btn" onclick="LP_LOG.clear()" title="Limpar tela">
                        <i class="bi bi-eraser"></i> Limpar
                    </button>
                </div>
                <div id="log-content-wrap">
                    <pre id="log-content"></pre>
                </div>
            </div>
        </div>

        <div id="term-resize-bar"></div>

        <div id="term-panel">
            <div id="term-header">
                <i class="bi bi-terminal-fill"></i>
                <span>Terminal</span>
                <span id="term-cwd"></span>
                <button id="term-close" onclick="LP.terminalClose()" title="Fechar terminal"><i class="bi bi-x-lg"></i></button>
            </div>
            <div id="term-container"></div>
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
        <button class="lp-sb-term-btn" id="sbTermBtn" onclick="LP.terminalToggle()" title="Terminal (Ctrl+`)">
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
    <div class="ctx-sep" id="ctx-sep-file"></div>
    <div class="ctx-item" id="ctx-new-file" onclick="LP_CTX.promptNewFile()"><i class="bi bi-file-earmark-plus" style="width:14px"></i> Novo Arquivo</div>
    <div class="ctx-item" id="ctx-new-dir"  onclick="LP_CTX.promptNewDir()"><i class="bi bi-folder-plus" style="width:14px"></i> Nova Pasta</div>
    <div class="ctx-sep" id="ctx-sep-dir"></div>
    <div class="ctx-item" onclick="LP_CTX.promptRename()"><i class="bi bi-cursor-text" style="width:14px"></i> Renomear</div>
    <div class="ctx-item danger" onclick="LP_CTX.promptDelete()"><i class="bi bi-trash3" style="width:14px"></i> Excluir</div>
    <div class="ctx-sep" id="ctx-sep-term"></div>
    <div class="ctx-item" id="ctx-terminal"  onclick="LP_CTX.openInTerminal()"><i class="bi bi-terminal" style="width:14px"></i> Abrir no Terminal</div>
    <div class="ctx-item" id="ctx-shortcut" onclick="LP_CTX.promptShortcut()"><i class="bi bi-signpost-split" style="width:14px"></i> Adicionar como Atalho</div>
    <div class="ctx-sep"></div>
    <div class="ctx-item" id="ctx-duplicate" onclick="LP_CTX.promptDuplicate()"><i class="bi bi-copy" style="width:14px"></i> Duplicar</div>
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
<script src="https://cdn.jsdelivr.net/npm/xterm@4.19.0/lib/xterm.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.7.0/lib/xterm-addon-fit.js"></script>
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
};
axios.defaults.headers.common['X-CSRF-TOKEN'] = LP_CFG.csrfToken;

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
        this.setupSearchModal();
        this.setupTreeFilter();
        this.setupTermResizeBar();
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
        const ext = path.split('.').pop().toLowerCase();
        if (ext === 'log') { return this._openLogTab(path); }

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

    _openLogTab(path) {
        const existingIdx = tabState.tabs.findIndex(t => t.path === path);
        if (existingIdx >= 0) { this.switchTab(existingIdx); return; }

        const tab = {
            path,
            name      : path.split('/').pop(),
            language  : 'log',
            type      : 'log',
            model     : null,
            viewState : null,
            modified  : false,
            logContent: '',
            logOffset : 0,
            logSize   : 0,
            autoScroll: true,
        };

        tabState.tabs.push(tab);
        this.switchTab(tabState.tabs.length - 1);
    },

    _doOpenFile(data) {
        const model = monaco.editor.createModel(data.content, data.language, monaco.Uri.parse('lp://' + data.path));
        const tab   = { path: data.path, name: data.name, language: data.language, model, viewState: null, modified: false };

        model.onDidChangeContent(() => {
            tab.modified = true;
            this.renderTabs();
            const btn = document.getElementById('btnSave');
            btn.disabled = false;
            btn.classList.add('save-active');
        });

        tabState.tabs.push(tab);
        this.switchTab(tabState.tabs.length - 1);
    },

    switchTab(idx) {
        // Save editor state of previous tab
        const prev = tabState.activeIdx >= 0 ? tabState.tabs[tabState.activeIdx] : null;
        if (prev && prev.type !== 'log') prev.viewState = editor?.saveViewState();

        // Stop any running log poll
        LP_LOG.stopPolling();

        tabState.activeIdx = idx;
        const tab = tabState.tabs[idx];
        if (!tab) return;

        document.getElementById('welcome').style.display = 'none';
        document.getElementById('sb-lang').textContent   = tab.language;

        if (tab.type === 'log') {
            document.getElementById('editor-container').style.display = 'none';
            document.getElementById('log-viewer').classList.add('active');
            document.getElementById('log-file-name').textContent = tab.name;
            document.getElementById('log-content').textContent   = tab.logContent;
            document.getElementById('btnSave').disabled       = true;
            document.getElementById('btnSave').classList.remove('save-active');
            document.getElementById('btnDeleteFile').disabled = true;
            if (tab.autoScroll) LP_LOG.scrollBottom();
            LP_LOG.startPolling(tab);
        } else {
            document.getElementById('log-viewer').classList.remove('active');
            document.getElementById('editor-container').style.display = 'block';
            if (editor) {
                editor.setModel(tab.model);
                if (tab.viewState) editor.restoreViewState(tab.viewState);
                editor.focus();
            }
            const btn = document.getElementById('btnSave');
            btn.disabled = !tab.modified;
            btn.classList.toggle('save-active', tab.modified);
            document.getElementById('btnDeleteFile').disabled = false;
        }

        this.renderTabs();
    },

    closeTab(idx) {
        const tab = tabState.tabs[idx];
        if (!tab) return;
        if (tab.modified && !confirm(`"${tab.name}" tem alterações não salvas. Fechar?`)) return;

        if (tab.type !== 'log') tab.model?.dispose();
        tabState.tabs.splice(idx, 1);

        if (tabState.tabs.length === 0) {
            tabState.activeIdx = -1;
            LP_LOG.stopPolling();
            editor?.setModel(null);
            document.getElementById('welcome').style.display = 'flex';
            document.getElementById('editor-container').style.display = 'none';
            document.getElementById('log-viewer').classList.remove('active');
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
            const isLog = tab.type === 'log';
            const icon  = isLog ? 'bi-broadcast' : getFileIcon(tab.name, 'file').icon;
            const color = isLog ? 'var(--green)'  : getFileIcon(tab.name, 'file').color;
            const div   = document.createElement('div');
            div.className = 'lp-tab' + (idx === tabState.activeIdx ? ' active' : '') + (tab.modified ? ' modified' : '');
            div.title = tab.path;
            div.innerHTML = `<span class="lp-tab-icon"><i class="bi ${icon}" style="color:${color}${isLog ? ';animation:lp-spin 2s linear infinite' : ''}"></i></span>
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

        try {
            const { data } = await axios.post(`${LP_CFG.apiBase}/file/save`, {
                path: tab.path, content: tab.model.getValue(),
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
        } catch { this.toast('err', 'Erro ao salvar arquivo.'); }
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
        if (!tab || tab.type === 'log') return;
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
                    btn.title        = sc.path;
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
        btn.title        = sc.path;
        btn.innerHTML = `<i class="bi ${sc.icon} sc-icon" style="color:var(--blue)"></i>
                         <span class="sc-name">${this.esc(sc.name)}</span>
                         <span class="lp-sc-btns">
                             <span class="lp-shortcut-edit" onclick="event.stopPropagation();LP.promptEditShortcut(${sc.id},'${sc.name.replace(/'/g,"\\'")}','${sc.path.replace(/'/g,"\\'")}','${sc.icon}')" title="Editar atalho"><i class="bi bi-pencil"></i></span>
                             <span class="lp-shortcut-del" onclick="event.stopPropagation();LP.removeShortcut(${sc.id})" title="Remover atalho"><i class="bi bi-x"></i></span>
                         </span>`;
        btn.addEventListener('click', () => this.navigateTo(sc.path));
        body.insertBefore(btn, addBtn || null);
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
    terminalOpen(cwd) {
        document.getElementById('term-panel').classList.add('open');
        document.getElementById('term-resize-bar').style.display = 'block';
        document.getElementById('sbTermBtn').classList.add('active');
        LP_TERM.open(cwd || this.currentPath || treeState.root || '/');
    },

    terminalClose() {
        document.getElementById('term-panel').classList.remove('open');
        document.getElementById('term-resize-bar').style.display = 'none';
        document.getElementById('sbTermBtn').classList.remove('active');
    },

    terminalToggle() {
        document.getElementById('term-panel').classList.contains('open')
            ? this.terminalClose()
            : this.terminalOpen();
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

    // ── Terminal resize bar ───────────────────────────────────────────────────
    setupTermResizeBar() {
        const bar = document.getElementById('term-resize-bar');
        let dragging = false, startY = 0, startH = 0;

        bar.addEventListener('mousedown', e => {
            dragging = true; startY = e.clientY; startH = document.getElementById('term-panel').offsetHeight;
            bar.classList.add('dragging');
            document.body.style.cursor = 'row-resize';
            document.body.style.userSelect = 'none';
        });
        document.addEventListener('mousemove', e => {
            if (!dragging) return;
            const newH = Math.max(80, Math.min(600, startH + (startY - e.clientY)));
            document.getElementById('term-panel').style.height = newH + 'px';
            LP_TERM.fitAddon?.fit();
        });
        document.addEventListener('mouseup', () => {
            if (!dragging) return;
            dragging = false;
            bar.classList.remove('dragging');
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
        });
    },

    // ── Keyboard shortcuts ────────────────────────────────────────────────────
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', e => {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); this.saveCurrentFile(); }
            if ((e.ctrlKey || e.metaKey) && e.key === 'w') { e.preventDefault(); if (tabState.activeIdx >= 0) this.closeTab(tabState.activeIdx); }
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') { e.preventDefault(); new bootstrap.Modal(document.getElementById('searchModal')).show(); }
            if ((e.ctrlKey || e.metaKey) && e.key === '`') { e.preventDefault(); this.terminalToggle(); }
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
        document.getElementById('ctx-sep-file').style.display  = isFile ? '' : 'none';
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
    openInTerminal()   { this.hide(); LP.terminalOpen(this.path); },
    promptShortcut()   { this.hide(); LP.promptAddShortcut(this.path); },
    promptDuplicate()  { this.hide(); LP.promptDuplicate(this.path, this.type); },
};

// ─── Terminal ─────────────────────────────────────────────────────────────────
const LP_TERM = {
    term    : null,
    fitAddon: null,
    cwd     : '/',
    line    : '',
    history : [],
    histIdx : -1,
    busy    : false,
    ready   : false,

    open(initialCwd) {
        this.cwd = initialCwd || treeState.root || '/';
        document.getElementById('term-cwd').textContent = this.cwd;

        if (this.ready) {
            this.term.focus();
            this.fitAddon.fit();
            return;
        }

        this.ready = true;

        this.term = new Terminal({
            theme: {
                background   : '#0d1117',
                foreground   : '#e6edf3',
                cursor       : '#58a6ff',
                selection    : '#1f6feb55',
                black        : '#0d1117',
                red          : '#f85149',
                green        : '#3fb950',
                yellow       : '#e3b341',
                blue         : '#58a6ff',
                magenta      : '#bc8cff',
                cyan         : '#39c5cf',
                white        : '#b1bac4',
                brightBlack  : '#6e7681',
                brightBlue   : '#79c0ff',
                brightGreen  : '#56d364',
                brightWhite  : '#e6edf3',
            },
            fontFamily  : "'JetBrains Mono', 'Fira Code', Consolas, monospace",
            fontSize    : 13,
            lineHeight  : 1.4,
            cursorBlink : true,
            cursorStyle : 'block',
            scrollback  : 2000,
        });

        this.fitAddon = new FitAddon.FitAddon();
        this.term.loadAddon(this.fitAddon);
        this.term.open(document.getElementById('term-container'));

        requestAnimationFrame(() => {
            this.fitAddon.fit();
            this.printPrompt();
            this.term.focus();
        });

        this.bindKeys();
        window.addEventListener('resize', () => this.fitAddon?.fit());
    },

    printPrompt() {
        document.getElementById('term-cwd').textContent = this.cwd;
        this.term.write(`\x1b[32m${LP_CFG.username}@${LP_CFG.host}\x1b[0m:\x1b[34m${this.cwd}\x1b[0m\$ `);
    },

    bindKeys() {
        this.term.onKey(({ key, domEvent: ev }) => {
            if (this.busy) {
                if (ev.ctrlKey && ev.keyCode === 67) {
                    this.term.write('\x1b[31m^C\x1b[0m\r\n');
                    this.busy = false;
                    this.printPrompt();
                }
                return;
            }

            const printable = !ev.altKey && !ev.ctrlKey && !ev.metaKey;

            if      (ev.keyCode === 13) { this.execute(); }
            else if (ev.keyCode ===  8) { if (this.line.length > 0) { this.line = this.line.slice(0,-1); this.term.write('\b \b'); } }
            else if (ev.keyCode === 38) { this.histUp(); }
            else if (ev.keyCode === 40) { this.histDown(); }
            else if (ev.ctrlKey && ev.keyCode === 67) { this.term.write('\x1b[31m^C\x1b[0m\r\n'); this.line = ''; this.printPrompt(); }
            else if (ev.ctrlKey && ev.keyCode === 76) { this.term.clear(); this.line = ''; this.printPrompt(); }
            else if (ev.ctrlKey && ev.keyCode === 85) { this.term.write('\r\x1b[K'); this.printPrompt(); this.line = ''; }
            else if (printable) { this.line += key; this.term.write(key); }
        });
    },

    histUp() {
        if (!this.history.length) return;
        if (this.histIdx === -1) this.histIdx = this.history.length - 1;
        else if (this.histIdx > 0) this.histIdx--;
        this.setLine(this.history[this.histIdx]);
    },

    histDown() {
        if (this.histIdx === -1) return;
        if (this.histIdx < this.history.length - 1) { this.histIdx++; this.setLine(this.history[this.histIdx]); }
        else { this.histIdx = -1; this.setLine(''); }
    },

    setLine(text) {
        this.term.write('\r\x1b[K');
        this.printPrompt();
        this.line = text;
        this.term.write(text);
    },

    async execute() {
        const cmd = this.line.trim();
        this.term.write('\r\n');

        if (!cmd) { this.printPrompt(); return; }

        if (this.history[this.history.length - 1] !== cmd) this.history.push(cmd);
        this.histIdx = -1;
        this.line    = '';
        this.busy    = true;

        if (cmd === 'clear' || cmd === 'cls') {
            this.term.clear();
            this.busy = false;
            this.printPrompt();
            return;
        }

        try {
            const { data } = await axios.post(`${LP_CFG.apiBase}/terminal`, {
                command: cmd,
                cwd    : this.cwd,
            });

            if (data.output) {
                const out = data.output
                    .replace(/\r\n/g, '\n')
                    .replace(/\r/g, '\n')
                    .replace(/\n/g, '\r\n');
                this.term.write(out);
                if (!data.output.endsWith('\n')) this.term.write('\r\n');
            }

            if (data.cwd) {
                this.cwd = data.cwd;
            }
        } catch (e) {
            this.term.write(`\x1b[31mErro: ${e.message}\x1b[0m\r\n`);
        }

        this.busy = false;
        this.printPrompt();
    },
};

// ─── Log tail viewer ─────────────────────────────────────────────────────────
const LP_LOG = {
    pollId    : null,
    MAX_LINES : 6000,

    startPolling(tab) {
        if (this.pollId) clearInterval(this.pollId);
        this._poll(tab);
        this.pollId = setInterval(() => this._poll(tab), 2000);
    },

    stopPolling() {
        if (this.pollId) { clearInterval(this.pollId); this.pollId = null; }
    },

    async _poll(tab) {
        try {
            const { data } = await axios.get(`${LP_CFG.apiBase}/log/tail`, {
                params: { path: tab.path, offset: tab.logOffset },
            });
            if (!data.success) return;

            // File rotated / truncated
            if (data.size < tab.logOffset) {
                tab.logContent = '';
                tab.logOffset  = 0;
            }

            if (data.content) {
                tab.logContent += data.content;

                // Keep last MAX_LINES lines to avoid memory bloat
                const lines = tab.logContent.split('\n');
                if (lines.length > this.MAX_LINES) {
                    tab.logContent = lines.slice(-this.MAX_LINES).join('\n');
                }

                tab.logOffset = data.offset;
                tab.logSize   = data.size;

                // Only update DOM for the active tab
                if (tabState.tabs[tabState.activeIdx] === tab) {
                    this._render(tab, data.content.length > 0);
                }
            }
        } catch { /* network hiccup — silently retry next tick */ }
    },

    _render(tab, hasNew) {
        const el = document.getElementById('log-content');
        el.textContent = tab.logContent;

        if (hasNew) {
            const now = new Date();
            const hms = now.toLocaleTimeString('pt-BR');
            document.getElementById('log-updated').textContent = `Última atualização: ${hms}`;
        }

        if (tab.autoScroll) this.scrollBottom();
    },

    scrollBottom() {
        const wrap = document.getElementById('log-content-wrap');
        wrap.scrollTop = wrap.scrollHeight;
    },

    toggleAutoScroll() {
        const tab = tabState.tabs[tabState.activeIdx];
        if (!tab || tab.type !== 'log') return;
        tab.autoScroll = !tab.autoScroll;
        const btn = document.getElementById('btnAutoScroll');
        btn.classList.toggle('active', tab.autoScroll);
        if (tab.autoScroll) this.scrollBottom();
    },

    clear() {
        const tab = tabState.tabs[tabState.activeIdx];
        if (!tab || tab.type !== 'log') return;
        tab.logContent = '';
        document.getElementById('log-content').textContent = '';
    },
};

// ─── Monaco editor ────────────────────────────────────────────────────────────
require(['vs/editor/editor.main'], function () {
    monacoReady = true;

    monaco.editor.defineTheme('leopanel-dark', {
        base   : 'vs-dark',
        inherit: true,
        rules  : [],
        colors : {
            'editor.background'               : '#0d1117',
            'editor.lineHighlightBackground'  : '#161b22',
            'editorLineNumber.foreground'     : '#6e7681',
            'editorLineNumber.activeForeground': '#e6edf3',
            'editor.selectionBackground'      : '#1f6feb66',
            'editorIndentGuide.background1'   : '#21262d',
        },
    });

    editor = monaco.editor.create(document.getElementById('editor-container'), {
        theme               : 'leopanel-dark',
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

// Pause auto-scroll when user manually scrolls up in the log viewer
document.getElementById('log-content-wrap').addEventListener('scroll', function () {
    const tab = tabState.tabs[tabState.activeIdx];
    if (!tab || tab.type !== 'log') return;
    const atBottom = this.scrollHeight - this.scrollTop - this.clientHeight < 40;
    if (!atBottom && tab.autoScroll) {
        tab.autoScroll = false;
        document.getElementById('btnAutoScroll').classList.remove('active');
    }
    if (atBottom && !tab.autoScroll) {
        tab.autoScroll = true;
        document.getElementById('btnAutoScroll').classList.add('active');
    }
});

// ─── Boot ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => LP.init());
</script>
</body>
</html>
