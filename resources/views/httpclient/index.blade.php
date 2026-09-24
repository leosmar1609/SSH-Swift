<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>HTTP Client — TechIA Panel</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg:       #0d1117;
            --surface:  #161b22;
            --surface2: #1c2128;
            --border:   #30363d;
            --border2:  #21262d;
            --text:     #e6edf3;
            --muted:    #8b949e;
            --subtle:   #6e7681;
            --blue:     #58a6ff;
            --blue-dim: #1f6feb;
            --green:    #3fb950;
            --red:      #f85149;
            --orange:   #d29922;
            --yellow:   #e3b341;
            --purple:   #bc8cff;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; overflow: hidden; background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; font-size: 13px; }

        /* ── Topbar ── */
        .lp-topbar {
            height: 46px; background: var(--surface); border-bottom: 1px solid var(--border);
            display: flex; align-items: center; padding: 0 14px; gap: 12px; flex-shrink: 0; z-index: 100;
            position: relative;
        }
        .lp-brand { display: flex; align-items: center; gap: 6px; color: var(--text); text-decoration: none; font-weight: 700; font-size: 15px; }
        .lp-brand-icon { width: 26px; height: 26px; background: linear-gradient(135deg, var(--blue-dim), var(--blue)); border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 12px; }
        .lp-brand span { color: var(--blue); }
        .lp-topbar-sep { width: 1px; height: 18px; background: var(--border); }
        .lp-topbar-title { font-size: 13px; font-weight: 600; color: var(--muted); display: flex; align-items: center; gap: 6px; }
        .lp-topbar-title i { color: var(--blue); }
        .lp-icon-btn { background: transparent; border: 1px solid var(--border); color: var(--muted); border-radius: 6px; padding: 4px 10px; font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: background .15s, color .15s; text-decoration: none; font-family: inherit; }
        .lp-icon-btn:hover { background: rgba(255,255,255,.07); color: var(--text); }
        .lp-icon-btn.primary { background: var(--blue-dim); border-color: var(--blue-dim); color: #fff; }
        .lp-icon-btn.primary:hover { background: var(--blue); border-color: var(--blue); }
        .lp-icon-btn.danger { color: var(--red); border-color: var(--border); }
        .lp-icon-btn.danger:hover { background: rgba(248,81,73,.1); border-color: var(--red); color: var(--red); }
        .ms-auto { margin-left: auto !important; }

        /* ── App Shell ── */
        #app { display: flex; height: calc(100vh - 46px); overflow: hidden; }

        /* ── Sidebar ── */
        #sidebar {
            width: 280px; flex-shrink: 0; background: var(--surface); border-right: 1px solid var(--border);
            display: flex; flex-direction: column; overflow: hidden;
        }
        .sidebar-tabs { display: flex; border-bottom: 1px solid var(--border); flex-shrink: 0; }
        .sidebar-tab { flex: 1; padding: 8px 6px; font-size: 11.5px; font-weight: 600; text-align: center; cursor: pointer; color: var(--muted); border-bottom: 2px solid transparent; transition: color .15s, border-color .15s; user-select: none; }
        .sidebar-tab:hover { color: var(--text); }
        .sidebar-tab.active { color: var(--blue); border-bottom-color: var(--blue); }

        .sidebar-pane { flex: 1; overflow-y: auto; overflow-x: hidden; display: none; flex-direction: column; }
        .sidebar-pane.active { display: flex; }

        /* Collections */
        .coll-toolbar { display: flex; align-items: center; gap: 6px; padding: 8px 10px; border-bottom: 1px solid var(--border2); flex-shrink: 0; }
        .coll-toolbar input { flex: 1; background: var(--bg); border: 1px solid var(--border); color: var(--text); border-radius: 5px; padding: 4px 8px; font-size: 12px; outline: none; font-family: inherit; }
        .coll-toolbar input:focus { border-color: var(--blue); }
        .coll-toolbar input::placeholder { color: var(--subtle); }

        .coll-list { flex: 1; overflow-y: auto; }
        .coll-group { border-bottom: 1px solid var(--border2); }
        .coll-group-hdr {
            display: flex; align-items: center; gap: 6px; padding: 7px 10px;
            cursor: pointer; user-select: none; font-size: 12px; font-weight: 600; color: var(--text);
        }
        .coll-group-hdr:hover { background: rgba(255,255,255,.04); }
        .coll-group-hdr .arr { font-size: 9px; color: var(--muted); transition: transform .15s; margin-right: 2px; }
        .coll-group-hdr.collapsed .arr { transform: rotate(-90deg); }
        .coll-group-hdr .coll-name-input { background: transparent; border: 1px solid var(--blue); color: var(--text); border-radius: 3px; padding: 1px 4px; font-size: 12px; font-weight: 600; font-family: inherit; outline: none; display: none; flex: 1; }
        .coll-group-hdr.editing .coll-name-input { display: block; }
        .coll-group-hdr.editing .coll-name-label { display: none; }
        .coll-group-actions { margin-left: auto; display: flex; gap: 2px; opacity: 0; transition: opacity .15s; }
        .coll-group-hdr:hover .coll-group-actions { opacity: 1; }
        .coll-group-actions button { background: transparent; border: none; color: var(--muted); cursor: pointer; padding: 2px 4px; border-radius: 3px; font-size: 11px; line-height: 1; }
        .coll-group-actions button:hover { color: var(--text); background: rgba(255,255,255,.08); }

        .coll-items { padding: 2px 0; display: block; }
        .coll-group.collapsed .coll-items { display: none; }
        .coll-item {
            display: flex; align-items: center; gap: 8px; padding: 5px 10px 5px 24px;
            cursor: pointer; font-size: 12px; color: var(--muted); transition: background .12s, color .12s;
        }
        .coll-item:hover { background: rgba(255,255,255,.05); color: var(--text); }
        .coll-item.active { background: rgba(88,166,255,.1); color: var(--blue); }
        .coll-item .method-badge { font-size: 10px; font-weight: 700; min-width: 38px; text-align: center; font-family: 'JetBrains Mono', monospace; }
        .coll-item .item-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .coll-item-del { opacity: 0; background: transparent; border: none; color: var(--muted); cursor: pointer; padding: 2px 4px; border-radius: 3px; font-size: 11px; }
        .coll-item:hover .coll-item-del { opacity: 1; }
        .coll-item-del:hover { color: var(--red); }

        .coll-empty { padding: 24px 12px; text-align: center; color: var(--muted); font-size: 12px; }
        .coll-empty i { display: block; font-size: 28px; color: var(--subtle); margin-bottom: 8px; }

        /* History */
        .hist-list { flex: 1; overflow-y: auto; }
        .hist-item {
            display: flex; align-items: center; gap: 8px; padding: 7px 10px;
            cursor: pointer; border-bottom: 1px solid var(--border2); transition: background .12s;
        }
        .hist-item:hover { background: rgba(255,255,255,.04); }
        .hist-item .method-badge { font-size: 10px; font-weight: 700; min-width: 38px; font-family: 'JetBrains Mono', monospace; }
        .hist-item .hist-url { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 11.5px; color: var(--muted); }
        .hist-item .hist-time { font-size: 10px; color: var(--subtle); flex-shrink: 0; }
        .hist-empty { padding: 24px 12px; text-align: center; color: var(--muted); font-size: 12px; }
        .hist-empty i { display: block; font-size: 28px; color: var(--subtle); margin-bottom: 8px; }
        .hist-toolbar { padding: 8px 10px; border-bottom: 1px solid var(--border2); flex-shrink: 0; display: flex; justify-content: flex-end; }

        /* ── Main Panel ── */
        #main-panel { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-width: 0; }

        /* ── Request Editor ── */
        #req-editor { display: flex; flex-direction: column; overflow: hidden; }

        /* URL Bar */
        .url-bar { display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-bottom: 1px solid var(--border); background: var(--surface); flex-shrink: 0; }
        .method-select {
            background: var(--bg); border: 1px solid var(--border); border-radius: 6px;
            padding: 5px 8px; font-size: 12.5px; font-weight: 700; font-family: 'JetBrains Mono', monospace;
            cursor: pointer; outline: none; min-width: 100px; transition: border-color .15s;
            appearance: none; -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%238b949e'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 8px center; padding-right: 24px;
        }
        .method-select:focus { border-color: var(--blue); box-shadow: 0 0 0 2px rgba(88,166,255,.15); }
        .method-select option { background: var(--surface2); color: var(--text); font-weight: 700; }

        .url-input {
            flex: 1; background: var(--bg); border: 1px solid var(--border); color: var(--text);
            border-radius: 6px; padding: 6px 10px; font-size: 13px; font-family: 'JetBrains Mono', monospace;
            outline: none; transition: border-color .15s; min-width: 0;
        }
        .url-input:focus { border-color: var(--blue); box-shadow: 0 0 0 2px rgba(88,166,255,.15); }
        .url-input::placeholder { color: var(--subtle); font-family: 'Inter', sans-serif; }

        .btn-send {
            background: linear-gradient(135deg, #1f6feb, #58a6ff); color: #fff; border: none;
            border-radius: 6px; padding: 6px 18px; font-size: 13px; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; gap: 6px; transition: opacity .15s, transform .1s; flex-shrink: 0;
            font-family: inherit;
        }
        .btn-send:hover { opacity: .9; }
        .btn-send:active { transform: scale(.97); }
        .btn-send:disabled { opacity: .5; cursor: not-allowed; transform: none; }

        /* Request Tabs */
        .req-tabs { display: flex; align-items: center; gap: 2px; padding: 6px 14px; border-bottom: 1px solid var(--border); background: var(--surface); flex-shrink: 0; }
        .req-tab { padding: 4px 12px; font-size: 12px; font-weight: 500; cursor: pointer; color: var(--muted); border: 1px solid transparent; border-radius: 20px; transition: all .15s; user-select: none; display: flex; align-items: center; gap: 5px; }
        .req-tab:hover { color: var(--text); background: rgba(255,255,255,.05); }
        .req-tab.active { color: var(--blue); background: rgba(88,166,255,.1); border-color: rgba(88,166,255,.25); }
        .req-tab .tab-badge { background: rgba(88,166,255,.2); color: var(--blue); border-radius: 10px; padding: 1px 6px; font-size: 10px; font-weight: 700; }

        .request-tab-strip { display: flex; align-items: center; gap: 8px; padding: 8px 14px 6px; border-bottom: 1px solid var(--border); background: var(--surface); flex-shrink: 0; }
        .request-tabs { display: flex; align-items: center; gap: 6px; flex: 1; overflow-x: auto; }
        .request-tab { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; border: 1px solid var(--border); background: var(--surface2); color: var(--muted); font-size: 12px; cursor: pointer; white-space: nowrap; }
        .request-tab.active { color: var(--blue); background: rgba(88,166,255,.12); border-color: rgba(88,166,255,.28); }
        .request-tab-close { display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; border: none; background: transparent; color: inherit; border-radius: 50%; cursor: pointer; }
        .request-tab-close:hover { background: rgba(255,255,255,.08); color: var(--red); }

        .req-tab-save { margin-left: auto; }

        /* Tab Panels */
        .req-tab-panels { flex: 1; overflow: hidden; position: relative; }
        .req-tab-panel { display: none; height: 100%; overflow-y: auto; padding: 10px 14px; }
        .req-tab-panel.active { display: block; }

        /* KV Table */
        .kv-table { width: 100%; border-collapse: collapse; }
        .kv-table th { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; padding: 4px 8px; border-bottom: 1px solid var(--border); text-align: left; }
        .kv-table td { padding: 3px 4px; border-bottom: 1px solid var(--border2); vertical-align: middle; }
        .kv-table tr:last-child td { border-bottom: none; }
        .kv-row td input[type="text"] {
            width: 100%; background: transparent; border: 1px solid transparent; color: var(--text);
            border-radius: 4px; padding: 4px 6px; font-size: 12.5px; font-family: 'JetBrains Mono', monospace;
            outline: none; transition: border-color .15s, background .15s;
        }
        .kv-row td input[type="text"]:hover { border-color: var(--border); background: rgba(255,255,255,.03); }
        .kv-row td input[type="text"]:focus { border-color: var(--blue); background: var(--bg); }
        .kv-row td input[type="text"]::placeholder { color: var(--subtle); font-family: 'Inter', sans-serif; }
        .kv-row td input[type="checkbox"] { cursor: pointer; accent-color: var(--blue); }
        .kv-row.disabled td input[type="text"] { opacity: .4; }
        .kv-del-btn { background: transparent; border: none; color: var(--subtle); cursor: pointer; padding: 2px 5px; border-radius: 3px; font-size: 13px; line-height: 1; transition: color .12s, background .12s; }
        .kv-del-btn:hover { color: var(--red); background: rgba(248,81,73,.1); }
        .kv-add-btn { background: transparent; border: 1px dashed var(--border); color: var(--muted); border-radius: 5px; padding: 5px 12px; font-size: 12px; cursor: pointer; margin-top: 8px; display: inline-flex; align-items: center; gap: 5px; transition: border-color .15s, color .15s; font-family: inherit; }
        .kv-add-btn:hover { border-color: var(--blue); color: var(--blue); }

        /* Body tabs */
        .body-type-tabs { display: flex; gap: 4px; margin-bottom: 10px; }
        .body-type-tab { padding: 3px 10px; font-size: 12px; cursor: pointer; color: var(--muted); border: 1px solid transparent; border-radius: 4px; transition: all .12s; user-select: none; }
        .body-type-tab:hover { color: var(--text); }
        .body-type-tab.active { color: var(--text); background: var(--surface2); border-color: var(--border); }

        .body-type-panel { display: none; }
        .body-type-panel.active { display: block; }

        /* Code textarea */
        .code-textarea {
            width: 100%; min-height: 180px; background: var(--bg); border: 1px solid var(--border);
            color: var(--text); border-radius: 6px; padding: 10px 12px; font-family: 'JetBrains Mono', monospace;
            font-size: 12.5px; line-height: 1.7; outline: none; resize: vertical; transition: border-color .15s;
        }
        .code-textarea:focus { border-color: var(--blue); box-shadow: 0 0 0 2px rgba(88,166,255,.15); }
        .code-textarea::placeholder { color: var(--subtle); font-family: 'Inter', sans-serif; }

        /* Variables panel */
        .vars-hint { font-size: 12px; color: var(--muted); margin-bottom: 10px; }
        .vars-hint code { background: var(--surface2); border: 1px solid var(--border); border-radius: 3px; padding: 1px 5px; font-family: 'JetBrains Mono', monospace; color: var(--yellow); font-size: 11.5px; }

        /* ── Resize Handle ── */
        #resize-handle {
            height: 5px; background: var(--border); cursor: row-resize; flex-shrink: 0;
            transition: background .15s; position: relative;
        }
        #resize-handle:hover, #resize-handle.dragging { background: var(--blue-dim); }
        #resize-handle::after { content: ''; position: absolute; left: 50%; top: 50%; transform: translate(-50%,-50%); width: 36px; height: 3px; border-radius: 2px; background: var(--border); }
        #resize-handle:hover::after { background: var(--blue); }

        /* ── Response Panel ── */
        #res-panel { display: flex; flex-direction: column; overflow: hidden; background: var(--bg); }

        .res-toolbar { display: flex; align-items: center; gap: 10px; padding: 8px 14px; border-bottom: 1px solid var(--border); background: var(--surface); flex-shrink: 0; }
        .res-toolbar .title { font-size: 12px; font-weight: 600; color: var(--muted); }

        .status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; font-family: 'JetBrains Mono', monospace; }
        .status-badge.s2xx { background: rgba(63,185,80,.15); color: var(--green); border: 1px solid rgba(63,185,80,.3); }
        .status-badge.s3xx { background: rgba(88,166,255,.12); color: var(--blue); border: 1px solid rgba(88,166,255,.25); }
        .status-badge.s4xx { background: rgba(248,81,73,.12); color: var(--red); border: 1px solid rgba(248,81,73,.3); }
        .status-badge.s5xx { background: rgba(248,81,73,.12); color: var(--red); border: 1px solid rgba(248,81,73,.3); }
        .status-badge.serr { background: rgba(248,81,73,.1); color: var(--orange); border: 1px solid rgba(210,153,34,.3); }

        .res-meta { display: flex; align-items: center; gap: 14px; font-size: 11.5px; color: var(--muted); }
        .res-meta span { display: flex; align-items: center; gap: 4px; }
        .res-meta i { color: var(--subtle); }

        .res-body { flex: 1; overflow: hidden; display: flex; flex-direction: column; }
        .res-body-tabs { display: flex; align-items: center; gap: 2px; padding: 6px 14px; border-bottom: 1px solid var(--border); flex-shrink: 0; }
        .res-body-tab { padding: 3px 10px; font-size: 12px; cursor: pointer; color: var(--muted); border: 1px solid transparent; border-radius: 4px; transition: all .12s; user-select: none; }
        .res-body-tab:hover { color: var(--text); }
        .res-body-tab.active { color: var(--text); background: var(--surface2); border-color: var(--border); }
        .res-body-copy { margin-left: auto; }

        .res-body-panels { flex: 1; overflow: hidden; }
        .res-body-panel { display: none; height: 100%; overflow: auto; padding: 10px 14px; }
        .res-body-panel.active { display: block; }

        /* Empty/loading response */
        #res-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; text-align: center; color: var(--muted); flex: 1; }
        #res-empty i { font-size: 38px; color: var(--subtle); margin-bottom: 12px; }
        #res-empty p { font-size: 13px; }

        #res-loading { display: none; flex-direction: column; align-items: center; justify-content: center; padding: 40px; text-align: center; color: var(--muted); flex: 1; }
        #res-loading .spin { animation: spin .8s linear infinite; font-size: 28px; color: var(--blue); margin-bottom: 12px; }

        #res-error { display: none; padding: 16px; flex-shrink: 0; }
        .res-error-box { background: rgba(248,81,73,.08); border: 1px solid rgba(248,81,73,.25); border-radius: 6px; padding: 12px 16px; }
        .res-error-box .err-title { color: var(--red); font-weight: 600; font-size: 13px; margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
        .res-error-box .err-msg { color: var(--muted); font-size: 12.5px; line-height: 1.6; }

        #res-content { display: none; flex-direction: column; height: 100%; overflow: hidden; }

        .res-headers-section { border-bottom: 1px solid var(--border2); flex-shrink: 0; }
        .res-headers-toggle { display: flex; align-items: center; gap: 6px; padding: 7px 14px; cursor: pointer; font-size: 12px; color: var(--muted); user-select: none; transition: color .12s; }
        .res-headers-toggle:hover { color: var(--text); }
        .res-headers-toggle .arr { font-size: 9px; transition: transform .15s; }
        .res-headers-toggle.open .arr { transform: rotate(90deg); }
        .res-headers-body { display: none; padding: 4px 14px 10px; max-height: 160px; overflow-y: auto; }
        .res-headers-toggle.open + .res-headers-body { display: block; }
        .res-header-row { display: flex; gap: 8px; padding: 3px 0; font-size: 11.5px; border-bottom: 1px solid var(--border2); }
        .res-header-row:last-child { border-bottom: none; }
        .res-header-key { color: var(--blue); font-family: 'JetBrains Mono', monospace; min-width: 180px; flex-shrink: 0; }
        .res-header-val { color: var(--muted); word-break: break-all; }

        /* JSON syntax highlight */
        pre.json-out { font-family: 'JetBrains Mono', monospace; font-size: 12px; line-height: 1.7; white-space: pre-wrap; word-break: break-word; margin: 0; }
        .json-str { color: #79c0ff; }
        .json-key { color: var(--blue); font-weight: 500; }
        .json-num { color: #d2a8ff; }
        .json-bool { color: var(--orange); }
        .json-null { color: var(--red); }
        .json-punct { color: var(--subtle); }

        pre.raw-out { font-family: 'JetBrains Mono', monospace; font-size: 12px; line-height: 1.7; white-space: pre-wrap; word-break: break-word; margin: 0; color: var(--muted); }

        /* ── Save Modal ── */
        .lp-modal-overlay {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,.65); z-index: 9999;
            align-items: center; justify-content: center;
        }
        .lp-modal-overlay.open { display: flex; }
        .lp-modal-box { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 24px; width: 380px; max-width: 95vw; box-shadow: 0 16px 40px rgba(0,0,0,.6); }
        .lp-modal-box h3 { font-size: 15px; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .lp-modal-box label { display: block; font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 5px; text-transform: uppercase; letter-spacing: .4px; }
        .lp-modal-box input, .lp-modal-box select {
            width: 100%; background: var(--bg); border: 1px solid var(--border); color: var(--text);
            border-radius: 6px; padding: 6px 10px; font-size: 13px; outline: none; font-family: inherit; margin-bottom: 14px;
        }
        .lp-modal-box input:focus, .lp-modal-box select:focus { border-color: var(--blue); box-shadow: 0 0 0 2px rgba(88,166,255,.15); }
        .lp-modal-box select option { background: var(--surface2); }
        .lp-modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 4px; }

        /* ── Scrollbars ── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #444c56; }

        /* ── Animations ── */
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }
        .fade-in { animation: fadeIn .2s ease; }

        /* ── Method colors ── */
        .m-get    { color: var(--blue); }
        .m-post   { color: var(--green); }
        .m-put    { color: var(--orange); }
        .m-patch  { color: var(--yellow); }
        .m-delete { color: var(--red); }
        .m-head   { color: var(--purple); }
        .m-options { color: var(--muted); }

        /* Misc */
        .flex-row { display: flex; align-items: center; gap: 8px; }
        .label-sm { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
    </style>
</head>
<body>

{{-- ── Topbar ── --}}
<div class="lp-topbar">
    <a href="{{ route('connections.index') }}" class="lp-brand">
        <div class="lp-brand-icon"><i class="bi bi-terminal-fill" style="color:#fff"></i></div>
        Leo<span>Panel</span>
    </a>
    <div class="lp-topbar-sep"></div>
    <div class="lp-topbar-title"><i class="bi bi-send"></i> HTTP Client</div>

    <div class="ms-auto" style="display:flex;align-items:center;gap:8px">
        <a href="{{ route('connections.index') }}" class="lp-icon-btn">
            <i class="bi bi-hdd-network"></i> Servidores
        </a>
        <a href="{{ route('docs.index') }}" class="lp-icon-btn">
            <i class="bi bi-file-earmark-pdf"></i> Docs
        </a>
    </div>
</div>

{{-- ── App ── --}}
<div id="app">

    {{-- ── Sidebar ── --}}
    <div id="sidebar">
        <div class="sidebar-tabs">
            <div class="sidebar-tab active" data-tab="collections">Collections</div>
            <div class="sidebar-tab" data-tab="history">Histórico</div>
        </div>

        {{-- Collections Pane --}}
        <div class="sidebar-pane active" id="pane-collections">
            <div class="coll-toolbar">
                <input type="text" id="coll-search" placeholder="Buscar...">
                <button class="lp-icon-btn" id="btn-new-collection" title="Nova coleção">
                    <i class="bi bi-folder-plus"></i>
                </button>
            </div>
            <div class="coll-list" id="coll-list">
                <div class="coll-empty" id="coll-empty">
                    <i class="bi bi-collection"></i>
                    <div style="font-weight:600;color:var(--text);margin-bottom:4px">Sem coleções</div>
                    <div>Clique em <i class="bi bi-folder-plus"></i> para criar</div>
                </div>
            </div>
        </div>

        {{-- History Pane --}}
        <div class="sidebar-pane" id="pane-history">
            <div class="hist-toolbar">
                <button class="lp-icon-btn danger" id="btn-clear-history" title="Limpar histórico" style="font-size:11px;padding:3px 8px">
                    <i class="bi bi-trash"></i> Limpar
                </button>
            </div>
            <div class="hist-list" id="hist-list">
                <div class="hist-empty" id="hist-empty">
                    <i class="bi bi-clock-history"></i>
                    <div>Nenhuma requisição ainda</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Main Panel ── --}}
    <div id="main-panel">

        {{-- ── Request Editor ── --}}
        <div id="req-editor" style="flex-shrink:0;">

            <div class="request-tab-strip">
                <div class="request-tabs" id="request-tabs"></div>
                <button class="lp-icon-btn" id="btn-new-request-tab" title="Nova aba">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>

            {{-- URL Bar --}}
            <div class="url-bar">
                <select class="method-select" id="method-select">
                    <option value="GET"     style="color:#58a6ff">GET</option>
                    <option value="POST"    style="color:#3fb950">POST</option>
                    <option value="PUT"     style="color:#d29922">PUT</option>
                    <option value="PATCH"   style="color:#e3b341">PATCH</option>
                    <option value="DELETE"  style="color:#f85149">DELETE</option>
                    <option value="HEAD"    style="color:#bc8cff">HEAD</option>
                    <option value="OPTIONS" style="color:#8b949e">OPTIONS</option>
                </select>
                <input type="text" class="url-input" id="url-input" placeholder="https://api.exemplo.com/endpoint">
                <button class="btn-send" id="btn-send">
                    <i class="bi bi-send-fill" id="send-icon"></i>
                    <span id="send-label">Enviar</span>
                </button>
            </div>

            {{-- Request Tabs --}}
            <div class="req-tabs">
                <div class="req-tab active" data-req-tab="params">
                    Params <span class="tab-badge" id="badge-params" style="display:none"></span>
                </div>
                <div class="req-tab" data-req-tab="headers">
                    Headers <span class="tab-badge" id="badge-headers" style="display:none"></span>
                </div>
                <div class="req-tab" data-req-tab="body">Body</div>
                <div class="req-tab" data-req-tab="variables">
                    Variáveis <span class="tab-badge" id="badge-vars" style="display:none"></span>
                </div>
                <button class="lp-icon-btn req-tab-save" id="btn-save-request" title="Salvar requisição">
                    <i class="bi bi-bookmark-plus"></i> Salvar
                </button>
            </div>

            {{-- Tab Panels (fixed height area) --}}
            <div class="req-tab-panels" style="max-height:240px;overflow-y:auto;">

                {{-- Params --}}
                <div class="req-tab-panel active" id="panel-params">
                    <table class="kv-table" id="params-table">
                        <thead><tr>
                            <th style="width:24px"></th>
                            <th>Chave</th>
                            <th>Valor</th>
                            <th style="width:28px"></th>
                        </tr></thead>
                        <tbody id="params-body"></tbody>
                    </table>
                    <button class="kv-add-btn" id="add-param-btn"><i class="bi bi-plus"></i> Adicionar</button>
                </div>

                {{-- Headers --}}
                <div class="req-tab-panel" id="panel-headers">
                    <table class="kv-table" id="headers-table">
                        <thead><tr>
                            <th style="width:24px"></th>
                            <th>Chave</th>
                            <th>Valor</th>
                            <th style="width:28px"></th>
                        </tr></thead>
                        <tbody id="headers-body"></tbody>
                    </table>
                    <button class="kv-add-btn" id="add-header-btn"><i class="bi bi-plus"></i> Adicionar</button>
                </div>

                {{-- Body --}}
                <div class="req-tab-panel" id="panel-body">
                    <div class="body-type-tabs">
                        <div class="body-type-tab active" data-body-type="none">Nenhum</div>
                        <div class="body-type-tab" data-body-type="json">JSON</div>
                        <div class="body-type-tab" data-body-type="form">Form-Data</div>
                        <div class="body-type-tab" data-body-type="urlencoded">URL-Encoded</div>
                        <div class="body-type-tab" data-body-type="raw">Raw</div>
                    </div>
                    <div class="body-type-panel active" id="body-none">
                        <div style="color:var(--muted);font-size:12px;padding:8px 0">Nenhum corpo será enviado com esta requisição.</div>
                    </div>
                    <div class="body-type-panel" id="body-json">
                        <textarea class="code-textarea" id="json-body" placeholder='{"key": "value"}'></textarea>
                        <div style="margin-top:6px;display:flex;gap:6px">
                            <button class="lp-icon-btn" id="btn-format-json" style="font-size:11px;padding:3px 8px"><i class="bi bi-magic"></i> Formatar</button>
                            <button class="lp-icon-btn" id="btn-validate-json" style="font-size:11px;padding:3px 8px"><i class="bi bi-check2"></i> Validar</button>
                        </div>
                    </div>
                    <div class="body-type-panel" id="body-form">
                        <table class="kv-table">
                            <thead><tr>
                                <th style="width:24px"></th>
                                <th>Chave</th>
                                <th>Valor</th>
                                <th style="width:28px"></th>
                            </tr></thead>
                            <tbody id="form-body"></tbody>
                        </table>
                        <button class="kv-add-btn" id="add-form-btn"><i class="bi bi-plus"></i> Adicionar</button>
                    </div>
                    <div class="body-type-panel" id="body-urlencoded">
                        <table class="kv-table">
                            <thead><tr>
                                <th style="width:24px"></th>
                                <th>Chave</th>
                                <th>Valor</th>
                                <th style="width:28px"></th>
                            </tr></thead>
                            <tbody id="urlenc-body"></tbody>
                        </table>
                        <button class="kv-add-btn" id="add-urlenc-btn"><i class="bi bi-plus"></i> Adicionar</button>
                    </div>
                    <div class="body-type-panel" id="body-raw">
                        <textarea class="code-textarea" id="raw-body" placeholder="Texto bruto..."></textarea>
                    </div>
                </div>

                {{-- Variables --}}
                <div class="req-tab-panel" id="panel-variables">
                    <div class="vars-hint">
                        Use <code>@{{nome}}</code> na URL, headers e body. As variáveis serão substituídas antes de enviar.
                    </div>
                    <table class="kv-table">
                        <thead><tr>
                            <th>Variável</th>
                            <th>Valor</th>
                            <th style="width:28px"></th>
                        </tr></thead>
                        <tbody id="vars-body"></tbody>
                    </table>
                    <button class="kv-add-btn" id="add-var-btn"><i class="bi bi-plus"></i> Adicionar</button>
                </div>

            </div>{{-- /req-tab-panels --}}
        </div>{{-- /req-editor --}}

        {{-- ── Resize Handle ── --}}
        <div id="resize-handle"></div>

        {{-- ── Response Panel ── --}}
        <div id="res-panel" style="flex:1;overflow:hidden;">

            {{-- Empty state --}}
            <div id="res-empty" style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;text-align:center;color:var(--muted);padding:40px;">
                <i class="bi bi-arrow-up-circle" style="font-size:38px;color:var(--subtle);margin-bottom:12px"></i>
                <p style="font-size:13px">Envie uma requisição para ver a resposta aqui</p>
            </div>

            {{-- Loading --}}
            <div id="res-loading" style="display:none;flex-direction:column;align-items:center;justify-content:center;height:100%;text-align:center;color:var(--muted);padding:40px;">
                <i class="bi bi-arrow-repeat spin" style="font-size:28px;color:var(--blue);margin-bottom:12px"></i>
                <p id="loading-label" style="font-size:13px">Enviando...</p>
            </div>

            {{-- Error --}}
            <div id="res-error" style="display:none;padding:16px;">
                <div class="res-error-box">
                    <div class="err-title"><i class="bi bi-exclamation-triangle-fill"></i> <span id="err-title-txt">Erro</span></div>
                    <div class="err-msg" id="err-msg-txt"></div>
                </div>
            </div>

            {{-- Content --}}
            <div id="res-content" style="display:none;flex-direction:column;height:100%;overflow:hidden;">
                <div class="res-toolbar">
                    <span class="title">Resposta</span>
                    <span id="status-badge" class="status-badge"></span>
                    <div class="res-meta" id="res-meta">
                        <span><i class="bi bi-clock"></i> <span id="res-time">—</span></span>
                        <span><i class="bi bi-hdd"></i> <span id="res-size">—</span></span>
                    </div>
                </div>

                <div class="res-headers-section">
                    <div class="res-headers-toggle" id="res-headers-toggle">
                        <i class="bi bi-chevron-right arr"></i>
                        <span>Headers de Resposta</span>
                        <span id="res-headers-count" style="font-size:11px;color:var(--subtle);margin-left:4px"></span>
                    </div>
                    <div class="res-headers-body" id="res-headers-body"></div>
                </div>

                <div class="res-body" style="flex:1;overflow:hidden;display:flex;flex-direction:column;">
                    <div class="res-body-tabs">
                        <div class="res-body-tab active" data-res-tab="pretty">Pretty</div>
                        <div class="res-body-tab" data-res-tab="raw">Raw</div>
                        <a class="lp-icon-btn res-body-copy" id="btn-download-body" style="font-size:11px;padding:3px 8px;display:none" download>
                            <i class="bi bi-download"></i> Baixar
                        </a>
                        <button class="lp-icon-btn res-body-copy" id="btn-copy-body" style="font-size:11px;padding:3px 8px">
                            <i class="bi bi-clipboard"></i> Copiar
                        </button>
                    </div>
                    <div class="res-body-panels" style="flex:1;overflow:auto;">
                        <div class="res-body-panel active" id="panel-pretty"></div>
                        <div class="res-body-panel" id="panel-raw"></div>
                    </div>
                </div>
            </div>

        </div>{{-- /res-panel --}}
    </div>{{-- /main-panel --}}
</div>{{-- /app --}}

{{-- ── Save Modal ── --}}
<div class="lp-modal-overlay" id="save-modal">
    <div class="lp-modal-box">
        <h3><i class="bi bi-bookmark-plus" style="color:var(--blue)"></i> Salvar Requisição</h3>
        <label for="save-req-name">Nome</label>
        <input type="text" id="save-req-name" placeholder="Ex: Listar usuários">
        <label for="save-coll-select">Coleção</label>
        <select id="save-coll-select">
            <option value="__new__">+ Nova coleção...</option>
        </select>
        <div id="new-coll-wrap" style="display:none;margin-bottom:14px;">
            <label for="new-coll-name">Nome da nova coleção</label>
            <input type="text" id="new-coll-name" placeholder="Minha API" style="margin-bottom:0">
        </div>
        <div style="margin:10px 0;">
            <label style="font-size:12px;color:var(--text);display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="save-response-checkbox" style="accent-color:var(--blue);"> Salvar também a resposta
            </label>
        </div>
        <div class="lp-modal-actions">
            <button class="lp-icon-btn" id="btn-cancel-save">Cancelar</button>
            <button class="lp-icon-btn primary" id="btn-confirm-save"><i class="bi bi-check2"></i> Salvar</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ═══════════════════════════════════════════════════════════════
// TechIA Panel HTTP Client — State & Storage
// ═══════════════════════════════════════════════════════════════

const LS_COLLECTIONS = 'lp_httpclient_collections';
const LS_HISTORY     = 'lp_httpclient_history';
const LS_VARIABLES   = 'lp_httpclient_variables';
const MAX_HISTORY    = 50;

let state = {
    method: 'GET',
    url: '',
    params: [],    // {enabled, key, value}
    headers: [],   // {enabled, key, value}
    bodyType: 'none',
    bodyJson: '',
    bodyRaw: '',
    bodyForm: [],      // {enabled, key, value}
    bodyUrlEncoded: [], // {enabled, key, value}
    variables: [],     // {key, value}
    responseState: { type: 'empty' }
};

let collections = JSON.parse(localStorage.getItem(LS_COLLECTIONS) || '[]');
let requests = [];
let activeRequestId = null;
// [{id, name, items: [{id, method, name, url, headers, params, bodyType, bodyJson, bodyRaw, bodyForm, bodyUrlEncoded}]}]

let history = JSON.parse(localStorage.getItem(LS_HISTORY) || '[]');
// [{id, method, url, timestamp, status, time}]

let savedVars = JSON.parse(localStorage.getItem(LS_VARIABLES) || '[]');
// [{key, value}]

function saveCollections() { localStorage.setItem(LS_COLLECTIONS, JSON.stringify(collections)); }
function saveHistory()     { localStorage.setItem(LS_HISTORY, JSON.stringify(history)); }
function saveVars()        { localStorage.setItem(LS_VARIABLES, JSON.stringify(savedVars)); }

function uid() { return Math.random().toString(36).slice(2) + Date.now().toString(36); }

function createRequestEntry(name = 'Nova requisição') {
    return {
        id: uid(),
        name,
        savedName: '',
        method: 'GET',
        url: '',
        params: [],
        headers: [],
        bodyType: 'none',
        bodyJson: '',
        bodyRaw: '',
        bodyForm: [],
        bodyUrlEncoded: [],
        variables: [],
        responseState: { type: 'empty' }
    };
}

function getActiveRequest() {
    return requests.find(r => r.id === activeRequestId) || null;
}

function getRequestTabLabel(req) {
    const saved = (req.savedName || '').trim();
    if (saved) return saved;
    const url = (req.url || req.name || '').trim();
    if (url && url !== 'Nova requisição') return url;
    return 'Nova requisição';
}

function syncActiveRequestLabel() {
    const active = getActiveRequest();
    if (!active) return;
    if (active.savedName) {
        active.name = active.savedName;
    } else if ((state.url || '').trim()) {
        active.name = state.url.trim();
    } else {
        active.name = 'Nova requisição';
    }
}

function persistActiveState() {
    const active = getActiveRequest();
    if (!active) return;
    active.method = state.method;
    active.url = state.url;
    active.params = JSON.parse(JSON.stringify(state.params));
    active.headers = JSON.parse(JSON.stringify(state.headers));
    active.bodyType = state.bodyType;
    active.bodyJson = state.bodyJson;
    active.bodyRaw = state.bodyRaw;
    active.bodyForm = JSON.parse(JSON.stringify(state.bodyForm));
    active.bodyUrlEncoded = JSON.parse(JSON.stringify(state.bodyUrlEncoded));
    active.variables = JSON.parse(JSON.stringify(state.variables));
    active.responseState = state.responseState || { type: 'empty' };
}

function syncStateToActiveRequest() {
    const active = getActiveRequest();
    if (!active) return;
    active.method = state.method;
    active.url = state.url;
    active.params = JSON.parse(JSON.stringify(state.params));
    active.headers = JSON.parse(JSON.stringify(state.headers));
    active.bodyType = state.bodyType;
    active.bodyJson = state.bodyJson;
    active.bodyRaw = state.bodyRaw;
    active.bodyForm = JSON.parse(JSON.stringify(state.bodyForm));
    active.bodyUrlEncoded = JSON.parse(JSON.stringify(state.bodyUrlEncoded));
    active.variables = JSON.parse(JSON.stringify(state.variables));
    active.responseState = state.responseState || { type: 'empty' };
}

function loadRequestIntoState(request) {
    if (!request) return;
    state = {
        method: request.method || 'GET',
        url: request.url || '',
        params: JSON.parse(JSON.stringify(request.params || [])),
        headers: JSON.parse(JSON.stringify(request.headers || [])),
        bodyType: request.bodyType || 'none',
        bodyJson: request.bodyJson || '',
        bodyRaw: request.bodyRaw || '',
        bodyForm: JSON.parse(JSON.stringify(request.bodyForm || [])),
        bodyUrlEncoded: JSON.parse(JSON.stringify(request.bodyUrlEncoded || [])),
        variables: JSON.parse(JSON.stringify(request.variables || [])),
        responseState: request.responseState || { type: 'empty' }
    };
}

function renderRequestTabs() {
    const tabs = document.getElementById('request-tabs');
    if (!tabs) return;
    tabs.innerHTML = '';

    requests.forEach(req => {
        const tab = document.createElement('button');
        tab.type = 'button';
        tab.className = 'request-tab' + (req.id === activeRequestId ? ' active' : '');
        const label = getRequestTabLabel(req);
        tab.innerHTML = `<span>${esc(label.length > 36 ? label.slice(0, 33) + '…' : label)}</span>`;

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'request-tab-close';
        closeBtn.innerHTML = '<i class="bi bi-x"></i>';
        closeBtn.addEventListener('click', e => {
            e.stopPropagation();
            closeRequest(req.id);
        });

        tab.addEventListener('click', () => switchRequest(req.id));
        tab.appendChild(closeBtn);
        tabs.appendChild(tab);
    });
}

function switchRequest(requestId) {
    if (!requestId || requestId === activeRequestId) return;
    persistActiveState();
    activeRequestId = requestId;
    const active = getActiveRequest();
    if (active) {
        loadRequestIntoState(active);
        applyStateToUI();
        renderRequestTabs();
        restoreResponseFromState();
    }
}

function closeRequest(requestId) {
    const index = requests.findIndex(r => r.id === requestId);
    if (index < 0) return;

    const wasActive = requestId === activeRequestId;
    if (wasActive) {
        persistActiveState();
    }

    requests = requests.filter(r => r.id !== requestId);

    if (!requests.length) {
        requests = [createRequestEntry('Nova requisição')];
    }

    activeRequestId = wasActive ? requests[Math.min(index, requests.length - 1)].id : activeRequestId;
    const active = getActiveRequest();
    if (active) {
        loadRequestIntoState(active);
        applyStateToUI();
        renderRequestTabs();
        restoreResponseFromState();
    }
}

function addRequestTab() {
    persistActiveState();
    const newReq = createRequestEntry('Nova requisição');
    requests.push(newReq);
    activeRequestId = newReq.id;
    loadRequestIntoState(newReq);
    applyStateToUI();
    renderRequestTabs();
    restoreResponseFromState();
}

function setActiveResponseState(responseState) {
    const active = getActiveRequest();
    if (!active) return;
    active.responseState = responseState;
    state.responseState = responseState;
}

function restoreResponseFromState() {
    const responseState = state.responseState || { type: 'empty' };
    if (responseState.type === 'loading') {
        showLoading(responseState.label || 'Enviando...');
    } else if (responseState.type === 'error') {
        showError(responseState.title || 'Erro', responseState.message || '');
    } else if (responseState.type === 'response') {
        showResponse(responseState.payload);
    } else {
        showEmpty();
    }
}

// ═══════════════════════════════════════════════════════════════
// Method Select
// ═══════════════════════════════════════════════════════════════

const methodSelect = document.getElementById('method-select');
const methodColors = { GET:'#58a6ff', POST:'#3fb950', PUT:'#d29922', PATCH:'#e3b341', DELETE:'#f85149', HEAD:'#bc8cff', OPTIONS:'#8b949e' };

function updateMethodColor() {
    const m = methodSelect.value;
    methodSelect.style.color = methodColors[m] || 'var(--text)';
}
methodSelect.addEventListener('change', () => {
    state.method = methodSelect.value;
    updateMethodColor();
    persistActiveState();
    renderRequestTabs();
});
updateMethodColor();

// ═══════════════════════════════════════════════════════════════
// URL + Params Sync
// ═══════════════════════════════════════════════════════════════

const urlInput = document.getElementById('url-input');

function paramsToUrl(base, rows) {
    const enabled = rows.filter(r => r.enabled && r.key.trim());
    if (!enabled.length) return base;
    const qs = enabled.map(r => encodeURIComponent(r.key) + '=' + encodeURIComponent(r.value)).join('&');
    const hashIdx = base.indexOf('#');
    const noHash  = hashIdx >= 0 ? base.slice(0, hashIdx) : base;
    const hash    = hashIdx >= 0 ? base.slice(hashIdx) : '';
    const qIdx    = noHash.indexOf('?');
    const urlBase = qIdx >= 0 ? noHash.slice(0, qIdx) : noHash;
    return urlBase + '?' + qs + hash;
}

function urlToParams(url) {
    try {
        const u = new URL(url.includes('://') ? url : 'https://placeholder.x/' + url.replace(/^\//,''));
        const result = [];
        u.searchParams.forEach((v, k) => { result.push({enabled: true, key: k, value: v}); });
        return result;
    } catch { return []; }
}

function getBaseUrl(url) {
    const qIdx = url.indexOf('?');
    return qIdx >= 0 ? url.slice(0, qIdx) : url;
}

let syncingUrl = false;
let syncingParams = false;

urlInput.addEventListener('input', () => {
    if (syncingUrl) return;
    syncingParams = true;
    state.url = urlInput.value;
    syncActiveRequestLabel();
    renderRequestTabs();
    const parsed = urlToParams(urlInput.value);
    if (parsed.length > 0) {
        state.params = parsed;
    } else {
        // keep existing param rows but clear values if url has no QS
        if (urlInput.value.indexOf('?') === -1) {
            state.params = state.params.filter(p => !p.key.trim());
        }
    }
    renderParamRows();
    updateBadges();
    syncingParams = false;
});

function syncUrlFromParams() {
    if (syncingParams) return;
    syncingUrl = true;
    const base = getBaseUrl(state.url || urlInput.value);
    urlInput.value = paramsToUrl(base, state.params);
    state.url = urlInput.value;
    syncingUrl = false;
}

// ═══════════════════════════════════════════════════════════════
// KV Table Helpers
// ═══════════════════════════════════════════════════════════════

function makeKvRow(tbodyEl, rows, idx, onUpdate, hasCheckbox = true) {
    const tr = document.createElement('tr');
    tr.className = 'kv-row' + (!rows[idx].enabled ? ' disabled' : '');
    tr.dataset.idx = idx;

    let html = '<td>';
    if (hasCheckbox) html += `<input type="checkbox" ${rows[idx].enabled ? 'checked' : ''}>`;
    html += `</td>
    <td><input type="text" placeholder="Chave" value="${esc(rows[idx].key)}"></td>
    <td><input type="text" placeholder="Valor" value="${esc(rows[idx].value)}"></td>
    <td><button class="kv-del-btn" title="Remover"><i class="bi bi-x"></i></button></td>`;
    tr.innerHTML = html;

    const inputs = tr.querySelectorAll('input[type="text"]');
    const cb     = tr.querySelector('input[type="checkbox"]');
    const delBtn = tr.querySelector('.kv-del-btn');

    if (cb) cb.addEventListener('change', () => {
        rows[idx].enabled = cb.checked;
        tr.className = 'kv-row' + (!rows[idx].enabled ? ' disabled' : '');
        onUpdate();
    });

    inputs[0].addEventListener('input', () => { rows[idx].key = inputs[0].value; onUpdate(); });
    inputs[1].addEventListener('input', () => { rows[idx].value = inputs[1].value; onUpdate(); });

    delBtn.addEventListener('click', () => {
        rows.splice(idx, 1);
        renderRows(tbodyEl, rows, onUpdate, hasCheckbox);
        onUpdate();
    });

    return tr;
}

function renderRows(tbody, rows, onUpdate, hasCheckbox = true) {
    tbody.innerHTML = '';
    rows.forEach((_, i) => tbody.appendChild(makeKvRow(tbody, rows, i, onUpdate, hasCheckbox)));
}

function addRow(rows, tbody, onUpdate, hasCheckbox = true) {
    rows.push({enabled: true, key: '', value: ''});
    tbody.appendChild(makeKvRow(tbody, rows, rows.length - 1, onUpdate, hasCheckbox));
    onUpdate();
    const last = tbody.lastElementChild;
    if (last) last.querySelector('input[type="text"]').focus();
}

function esc(s) {
    return (s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ═══════════════════════════════════════════════════════════════
// Params
// ═══════════════════════════════════════════════════════════════

const paramsTbody = document.getElementById('params-body');
function renderParamRows() { renderRows(paramsTbody, state.params, onParamsUpdate); }
function onParamsUpdate() { syncUrlFromParams(); updateBadges(); }

document.getElementById('add-param-btn').addEventListener('click', () => {
    addRow(state.params, paramsTbody, onParamsUpdate);
});

// ═══════════════════════════════════════════════════════════════
// Headers
// ═══════════════════════════════════════════════════════════════

const headersTbody = document.getElementById('headers-body');
function renderHeaderRows() { renderRows(headersTbody, state.headers, updateBadges); }

document.getElementById('add-header-btn').addEventListener('click', () => {
    addRow(state.headers, headersTbody, updateBadges);
});

// ═══════════════════════════════════════════════════════════════
// Body
// ═══════════════════════════════════════════════════════════════

const bodyTypeTabs = document.querySelectorAll('.body-type-tab');
const bodyTypePanels = { none: 'body-none', json: 'body-json', form: 'body-form', urlencoded: 'body-urlencoded', raw: 'body-raw' };

bodyTypeTabs.forEach(tab => {
    tab.addEventListener('click', () => {
        bodyTypeTabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        const type = tab.dataset.bodyType;
        state.bodyType = type;
        Object.entries(bodyTypePanels).forEach(([k, panelId]) => {
            document.getElementById(panelId).classList.toggle('active', k === type);
        });
    });
});

const jsonBodyEl = document.getElementById('json-body');
jsonBodyEl.addEventListener('input', () => { state.bodyJson = jsonBodyEl.value; });

document.getElementById('btn-format-json').addEventListener('click', () => {
    try {
        const parsed = JSON.parse(jsonBodyEl.value);
        jsonBodyEl.value = JSON.stringify(parsed, null, 2);
        state.bodyJson = jsonBodyEl.value;
    } catch (e) { showToast('JSON inválido: ' + e.message, 'error'); }
});

document.getElementById('btn-validate-json').addEventListener('click', () => {
    try {
        JSON.parse(jsonBodyEl.value);
        showToast('JSON válido!', 'success');
    } catch (e) { showToast('JSON inválido: ' + e.message, 'error'); }
});

const rawBodyEl = document.getElementById('raw-body');
rawBodyEl.addEventListener('input', () => { state.bodyRaw = rawBodyEl.value; });

const formTbody = document.getElementById('form-body');
function renderFormRows() { renderRows(formTbody, state.bodyForm, () => {}); }
document.getElementById('add-form-btn').addEventListener('click', () => { addRow(state.bodyForm, formTbody, () => {}); });

const urlencTbody = document.getElementById('urlenc-body');
function renderUrlEncRows() { renderRows(urlencTbody, state.bodyUrlEncoded, () => {}); }
document.getElementById('add-urlenc-btn').addEventListener('click', () => { addRow(state.bodyUrlEncoded, urlencTbody, () => {}); });

// ═══════════════════════════════════════════════════════════════
// Variables
// ═══════════════════════════════════════════════════════════════

const varsTbody = document.getElementById('vars-body');

function renderVarRows() {
    varsTbody.innerHTML = '';
    state.variables.forEach((_, i) => {
        const tr = document.createElement('tr');
        tr.className = 'kv-row';
        tr.innerHTML = `
            <td><input type="text" placeholder="nome" value="${esc(state.variables[i].key)}" style="color:var(--yellow)"></td>
            <td><input type="text" placeholder="valor" value="${esc(state.variables[i].value)}"></td>
            <td><button class="kv-del-btn" title="Remover"><i class="bi bi-x"></i></button></td>`;
        const inputs = tr.querySelectorAll('input[type="text"]');
        inputs[0].addEventListener('input', () => { state.variables[i].key = inputs[0].value; onVarsUpdate(); });
        inputs[1].addEventListener('input', () => { state.variables[i].value = inputs[1].value; onVarsUpdate(); });
        tr.querySelector('.kv-del-btn').addEventListener('click', () => {
            state.variables.splice(i, 1);
            renderVarRows();
            onVarsUpdate();
        });
        varsTbody.appendChild(tr);
    });
}

function onVarsUpdate() {
    savedVars = [...state.variables];
    saveVars();
    updateBadges();
}

document.getElementById('add-var-btn').addEventListener('click', () => {
    state.variables.push({key: '', value: ''});
    renderVarRows();
});

function applyVariables(str) {
    if (!str) return str;
    state.variables.forEach(v => {
        if (v.key.trim()) str = str.replaceAll('{{' + v.key + '}}', v.value);
    });
    return str;
}

// ═══════════════════════════════════════════════════════════════
// Badges
// ═══════════════════════════════════════════════════════════════

function updateBadges() {
    const pc = state.params.filter(p => p.enabled && p.key.trim()).length;
    const hc = state.headers.filter(h => h.enabled && h.key.trim()).length;
    const vc = state.variables.filter(v => v.key.trim()).length;

    const bp = document.getElementById('badge-params');
    const bh = document.getElementById('badge-headers');
    const bv = document.getElementById('badge-vars');

    bp.style.display = pc ? '' : 'none'; bp.textContent = pc;
    bh.style.display = hc ? '' : 'none'; bh.textContent = hc;
    bv.style.display = vc ? '' : 'none'; bv.textContent = vc;
}

// ═══════════════════════════════════════════════════════════════
// Request Tabs
// ═══════════════════════════════════════════════════════════════

document.querySelectorAll('[data-req-tab]').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('[data-req-tab]').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.req-tab-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('panel-' + tab.dataset.reqTab).classList.add('active');
    });
});

// ═══════════════════════════════════════════════════════════════
// Response Tabs
// ═══════════════════════════════════════════════════════════════

document.querySelectorAll('[data-res-tab]').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('[data-res-tab]').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.res-body-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('panel-' + tab.dataset.resTab).classList.add('active');
    });
});

// ═══════════════════════════════════════════════════════════════
// Sidebar Tabs
// ═══════════════════════════════════════════════════════════════

document.querySelectorAll('.sidebar-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.sidebar-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.sidebar-pane').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('pane-' + tab.dataset.tab).classList.add('active');
    });
});

// ═══════════════════════════════════════════════════════════════
// Response Headers Toggle
// ═══════════════════════════════════════════════════════════════

document.getElementById('res-headers-toggle').addEventListener('click', function() {
    this.classList.toggle('open');
});

// ═══════════════════════════════════════════════════════════════
// JSON Syntax Highlight
// ═══════════════════════════════════════════════════════════════

function syntaxHighlightJSON(json) {
    // Escape HTML entities first
    json = json.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    return json.replace(
        /("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+\.?\d*(?:[eE][+-]?\d+)?)/g,
        function(match) {
            let cls = 'json-num';
            if (/^"/.test(match)) {
                if (/:$/.test(match)) {
                    cls = 'json-key';
                    match = match.replace(/:$/, ''); // remove trailing colon
                    return `<span class="${cls}">${match}</span>:`;
                } else {
                    cls = 'json-str';
                }
            } else if (/true|false/.test(match)) {
                cls = 'json-bool';
            } else if (/null/.test(match)) {
                cls = 'json-null';
            }
            return `<span class="${cls}">${match}</span>`;
        }
    );
}

// ═══════════════════════════════════════════════════════════════
// Send Request
// ═══════════════════════════════════════════════════════════════

const btnSend    = document.getElementById('btn-send');
const sendIcon   = document.getElementById('send-icon');
const sendLabel  = document.getElementById('send-label');

let activeAbort = null;
let isSending   = false;

btnSend.addEventListener('click', sendRequest);

async function sendRequest() {
    if (isSending) {
        activeAbort && activeAbort.abort();
        resetSendBtn();
        showEmpty();
        return;
    }

    persistActiveState();
    setActiveResponseState({ type: 'loading', label: 'Enviando...' });

    const rawUrl = applyVariables((urlInput.value || '').trim());
    const method = state.method || methodSelect.value;
    if (!rawUrl) { showToast('Informe a URL', 'error'); return; }

    // Build headers
    const headers = {};
    state.headers.filter(h => h.enabled && h.key.trim()).forEach(h => {
        headers[applyVariables(h.key)] = applyVariables(h.value);
    });

    // Set content-type based on body type
    if (state.bodyType === 'json' && !headers['Content-Type'] && !headers['content-type']) {
        headers['Content-Type'] = 'application/json';
    } else if (state.bodyType === 'urlencoded' && !headers['Content-Type'] && !headers['content-type']) {
        headers['Content-Type'] = 'application/x-www-form-urlencoded';
    }

    // Build body
    let body = undefined;
    const hasBody = !['GET','HEAD'].includes(method);

    if (hasBody) {
        if (state.bodyType === 'json') {
            body = applyVariables(state.bodyJson) || undefined;
        } else if (state.bodyType === 'raw') {
            body = applyVariables(state.bodyRaw) || undefined;
        } else if (state.bodyType === 'urlencoded') {
            const enabled = state.bodyUrlEncoded.filter(r => r.enabled && r.key.trim());
            if (enabled.length) {
                body = enabled.map(r => encodeURIComponent(applyVariables(r.key)) + '=' + encodeURIComponent(applyVariables(r.value))).join('&');
            }
        } else if (state.bodyType === 'form') {
            const enabled = state.bodyForm.filter(r => r.enabled && r.key.trim());
            if (enabled.length) {
                const fd = new FormData();
                enabled.forEach(r => fd.append(applyVariables(r.key), applyVariables(r.value)));
                body = fd;
                // Remove Content-Type so browser sets boundary
                delete headers['Content-Type'];
                delete headers['content-type'];
            }
        }
    }

    setSending(true);
    showLoading('Enviando...');

    activeAbort = new AbortController();
    const t0 = performance.now();

    try {
        const response = await fetch('{{ route('httpclient.proxy') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                url: rawUrl,
                method,
                headers,
                body
            }),
            signal: activeAbort.signal,
            redirect: 'follow',
        });

        const elapsed = Math.round(performance.now() - t0);
        const data = await response.json();
        const status = data.status || response.status;
        const text = data.body || '';
        const bodyEncoding = data.bodyEncoding === 'base64' ? 'base64' : 'text';
        const size = typeof data.bodySize === 'number' ? data.bodySize : new TextEncoder().encode(text).length;
        const resHeaders = data.headers || {};

        // Add to history
        addToHistory({
            id: uid(), method, url: rawUrl,
            timestamp: Date.now(), status, time: elapsed
        });

        setActiveResponseState({
            type: 'response',
            payload: {status, statusText: data.statusText || '', elapsed, size, headers: resHeaders, body: text, bodyEncoding}
        });
        showResponse({status, statusText: data.statusText || '', elapsed, size, headers: resHeaders, body: text, bodyEncoding});

    } catch (err) {
        const elapsed = Math.round(performance.now() - t0);
        if (err.name === 'AbortError') {
            showEmpty();
        } else {
            let msg = err.message || 'Erro desconhecido';
            let hint = '';
            if (msg.toLowerCase().includes('failed to fetch') || msg.toLowerCase().includes('networkerror') || msg.toLowerCase().includes('cors')) {
                hint = `<br><br><strong>Possível erro de CORS:</strong> O navegador bloqueou a requisição por política de segurança de origem cruzada (CORS). O servidor precisa retornar os headers <code>Access-Control-Allow-Origin</code> corretos. Para testar APIs locais, use uma extensão de CORS no navegador ou um proxy.`;
            }
            addToHistory({id: uid(), method, url: rawUrl, timestamp: Date.now(), status: 0, time: elapsed});
            setActiveResponseState({ type: 'error', title: 'Falha na requisição', message: msg + hint });
            showError('Falha na requisição', msg + hint);
        }
    } finally {
        setSending(false);
    }
}

function setSending(sending) {
    isSending = sending;
    btnSend.disabled = false;
    if (sending) {
        sendIcon.className = 'bi bi-stop-fill';
        sendLabel.textContent = 'Cancelar';
        btnSend.style.background = 'linear-gradient(135deg, #6e2020, #f85149)';
    } else {
        resetSendBtn();
    }
}

function resetSendBtn() {
    isSending = false;
    sendIcon.className = 'bi bi-send-fill';
    sendLabel.textContent = 'Enviar';
    btnSend.style.background = 'linear-gradient(135deg, #1f6feb, #58a6ff)';
}

// ═══════════════════════════════════════════════════════════════
// Response Display
// ═══════════════════════════════════════════════════════════════

function showEmpty() {
    document.getElementById('res-empty').style.display   = 'flex';
    document.getElementById('res-loading').style.display = 'none';
    document.getElementById('res-error').style.display   = 'none';
    document.getElementById('res-content').style.display = 'none';
    setActiveResponseState({ type: 'empty' });
}

function showLoading(label) {
    document.getElementById('res-empty').style.display   = 'none';
    document.getElementById('res-loading').style.display = 'flex';
    document.getElementById('res-error').style.display   = 'none';
    document.getElementById('res-content').style.display = 'none';
    document.getElementById('loading-label').textContent  = label;
    setActiveResponseState({ type: 'loading', label });
}

function showError(title, msg) {
    document.getElementById('res-empty').style.display   = 'none';
    document.getElementById('res-loading').style.display = 'none';
    document.getElementById('res-error').style.display   = 'block';
    document.getElementById('res-content').style.display = 'none';
    document.getElementById('err-title-txt').textContent = title;
    document.getElementById('err-msg-txt').innerHTML     = msg;
    setActiveResponseState({ type: 'error', title, message: msg });
}

let currentBlobUrl = null;

function base64ToBlob(base64, contentType) {
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
    return new Blob([bytes], { type: contentType || 'application/octet-stream' });
}

function guessFileName(contentType, headers) {
    const disposition = headers['content-disposition'] || headers['Content-Disposition'] || '';
    const match = disposition.match(/filename="?([^";]+)"?/i);
    if (match) return match[1];
    const extMap = {
        'application/pdf': '.pdf', 'image/png': '.png', 'image/jpeg': '.jpg', 'image/gif': '.gif',
        'image/webp': '.webp', 'image/svg+xml': '.svg', 'application/zip': '.zip',
    };
    const ext = extMap[(contentType || '').split(';')[0].trim()] || '.bin';
    return 'response' + ext;
}

function showResponse({status, statusText, elapsed, size, headers, body, bodyEncoding}) {
    document.getElementById('res-empty').style.display   = 'none';
    document.getElementById('res-loading').style.display = 'none';
    document.getElementById('res-error').style.display   = 'none';
    document.getElementById('res-content').style.display = 'flex';

    // Status badge
    const badge = document.getElementById('status-badge');
    badge.textContent = status + (statusText ? ' ' + statusText : '');
    badge.className = 'status-badge ' + (
        status >= 500 ? 's5xx' :
        status >= 400 ? 's4xx' :
        status >= 300 ? 's3xx' :
        status >= 200 ? 's2xx' : 'serr'
    );

    // Meta
    document.getElementById('res-time').textContent = elapsed + ' ms';
    document.getElementById('res-size').textContent = size < 1024 ? size + ' B' : (size/1024).toFixed(1) + ' KB';

    // Response headers
    const hdrBody = document.getElementById('res-headers-body');
    const hdrCount = document.getElementById('res-headers-count');
    hdrBody.innerHTML = '';
    const hkeys = Object.keys(headers);
    hdrCount.textContent = '(' + hkeys.length + ')';
    hkeys.forEach(k => {
        const row = document.createElement('div');
        row.className = 'res-header-row';
        row.innerHTML = `<span class="res-header-key">${esc(k)}</span><span class="res-header-val">${esc(headers[k])}</span>`;
        hdrBody.appendChild(row);
    });

    const ct = (headers['content-type'] || headers['Content-Type'] || '');
    const downloadBtn = document.getElementById('btn-download-body');
    const copyBtn = document.getElementById('btn-copy-body');

    if (currentBlobUrl) { URL.revokeObjectURL(currentBlobUrl); currentBlobUrl = null; }

    if (bodyEncoding === 'base64') {
        // Binary body (PDF, image, zip, ...) — can't render as text, preview or offer download instead.
        const blob = base64ToBlob(body, ct);
        currentBlobUrl = URL.createObjectURL(blob);
        const fileName = guessFileName(ct, headers);

        let previewHtml;
        if (ct.startsWith('application/pdf')) {
            previewHtml = `<iframe src="${currentBlobUrl}" style="width:100%;height:100%;min-height:400px;border:none;background:#fff"></iframe>`;
        } else if (ct.startsWith('image/')) {
            previewHtml = `<div style="padding:14px"><img src="${currentBlobUrl}" style="max-width:100%;border-radius:6px"></div>`;
        } else {
            previewHtml = `<div style="padding:24px;color:var(--muted);font-size:12.5px">
                <i class="bi bi-file-earmark-binary" style="font-size:28px;color:var(--subtle);display:block;margin-bottom:10px"></i>
                Conteúdo binário (${esc(ct || 'tipo desconhecido')}) — sem pré-visualização. Use o botão Baixar.
            </div>`;
        }

        document.getElementById('panel-pretty').innerHTML = `<div class="fade-in" style="height:100%">${previewHtml}</div>`;
        document.getElementById('panel-raw').innerHTML = `<div class="fade-in" style="padding:24px;color:var(--muted);font-size:12.5px">Conteúdo binário — veja a pré-visualização na aba Pretty ou baixe o arquivo.</div>`;

        downloadBtn.style.display = '';
        downloadBtn.href = currentBlobUrl;
        downloadBtn.download = fileName;
        copyBtn.style.display = 'none';

        setActiveResponseState({ type: 'response', payload: {status, statusText, elapsed, size, headers, body, bodyEncoding} });
        return;
    }

    downloadBtn.style.display = 'none';
    copyBtn.style.display = '';

    // Body
    let prettyHtml = '';
    let rawText = body;
    let parsedJson = null;

    // Try JSON
    if (ct.includes('json') || (body && body.trim().match(/^[\[{]/))) {
        try {
            parsedJson = JSON.parse(body);
            const formatted = JSON.stringify(parsedJson, null, 2);
            prettyHtml = `<pre class="json-out">${syntaxHighlightJSON(formatted)}</pre>`;
            rawText = formatted;
        } catch {
            prettyHtml = `<pre class="raw-out">${esc(body)}</pre>`;
        }
    } else {
        prettyHtml = `<pre class="raw-out">${esc(body)}</pre>`;
    }

    document.getElementById('panel-pretty').innerHTML = `<div class="fade-in">${prettyHtml}</div>`;
    document.getElementById('panel-raw').innerHTML    = `<pre class="raw-out fade-in">${esc(body)}</pre>`;
    setActiveResponseState({ type: 'response', payload: {status, statusText, elapsed, size, headers, body, bodyEncoding} });
}

// Copy body
document.getElementById('btn-copy-body').addEventListener('click', () => {
    const activePanel = document.querySelector('.res-body-panel.active');
    const text = activePanel ? (activePanel.innerText || activePanel.textContent) : '';
    navigator.clipboard.writeText(text).then(() => showToast('Copiado!', 'success')).catch(() => showToast('Falha ao copiar', 'error'));
});

// ═══════════════════════════════════════════════════════════════
// Collections
// ═══════════════════════════════════════════════════════════════

function renderCollections() {
    const list    = document.getElementById('coll-list');
    const empty   = document.getElementById('coll-empty');
    const search  = document.getElementById('coll-search').value.toLowerCase();

    // Remove all groups (keep empty node)
    document.querySelectorAll('.coll-group').forEach(g => g.remove());

    if (!collections.length) { empty.style.display = 'block'; return; }
    empty.style.display = 'none';

    collections.forEach(coll => {
        const filteredItems = search
            ? (coll.items || []).filter(it => it.name.toLowerCase().includes(search) || it.url.toLowerCase().includes(search) || coll.name.toLowerCase().includes(search))
            : (coll.items || []);

        if (search && !filteredItems.length && !coll.name.toLowerCase().includes(search)) return;

        const group = document.createElement('div');
        group.className = 'coll-group';
        group.dataset.collId = coll.id;

        const items = filteredItems.length ? filteredItems : (search ? [] : (coll.items || []));

        group.innerHTML = `
            <div class="coll-group-hdr" data-coll-id="${esc(coll.id)}">
                <i class="bi bi-chevron-right arr"></i>
                <i class="bi bi-folder2" style="color:var(--orange)"></i>
                <span class="coll-name-label">${esc(coll.name)}</span>
                <input type="text" class="coll-name-input" value="${esc(coll.name)}" spellcheck="false">
                <div class="coll-group-actions">
                    <button title="Exportar coleção" class="coll-export-btn" data-coll-id="${esc(coll.id)}"><i class="bi bi-download"></i></button>
                    <button title="Renomear" class="coll-rename-btn" data-coll-id="${esc(coll.id)}"><i class="bi bi-pencil"></i></button>
                    <button title="Excluir coleção" class="coll-delete-btn" data-coll-id="${esc(coll.id)}" style="color:var(--red)"><i class="bi bi-trash"></i></button>
                </div>
            </div>
            <div class="coll-items">
                ${items.length === 0 ? `<div style="padding:6px 24px;font-size:11.5px;color:var(--subtle)">Sem itens</div>` :
                    items.map(it => `
                        <div class="coll-item" data-coll-id="${esc(coll.id)}" data-item-id="${esc(it.id)}">
                            <span class="method-badge m-${(it.method||'GET').toLowerCase()}">${esc(it.method||'GET')}</span>
                            <span class="item-name">${esc(it.name)}</span>
                            <button class="coll-item-del" data-coll-id="${esc(coll.id)}" data-item-id="${esc(it.id)}" title="Remover"><i class="bi bi-x"></i></button>
                        </div>
                    `).join('')}
            </div>`;

        list.appendChild(group);
    });

    // Bind events
    document.querySelectorAll('.coll-group-hdr').forEach(hdr => {
        hdr.addEventListener('click', e => {
            if (e.target.closest('.coll-group-actions') || e.target.closest('.coll-name-input')) return;
            hdr.classList.toggle('collapsed');
            const grp = hdr.closest('.coll-group');
            grp.classList.toggle('collapsed');
        });
        hdr.addEventListener('dblclick', e => {
            if (e.target.closest('.coll-group-actions')) return;
            startRenameCollection(hdr.dataset.collId);
        });
    });

    document.querySelectorAll('.coll-export-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            downloadCollection(btn.dataset.collId);
        });
    });

    document.querySelectorAll('.coll-rename-btn').forEach(btn => {
        btn.addEventListener('click', e => { e.stopPropagation(); startRenameCollection(btn.dataset.collId); });
    });

    document.querySelectorAll('.coll-delete-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            if (confirm('Excluir coleção "' + (collections.find(c=>c.id===btn.dataset.collId)||{}).name + '"?')) {
                collections = collections.filter(c => c.id !== btn.dataset.collId);
                saveCollections();
                renderCollections();
            }
        });
    });

    document.querySelectorAll('.coll-item').forEach(item => {
        item.addEventListener('click', e => {
            if (e.target.closest('.coll-item-del')) return;
            loadCollectionItem(item.dataset.collId, item.dataset.itemId);
        });
    });

    document.querySelectorAll('.coll-item-del').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            const coll = collections.find(c => c.id === btn.dataset.collId);
            if (!coll) return;
            coll.items = (coll.items||[]).filter(it => it.id !== btn.dataset.itemId);
            saveCollections();
            renderCollections();
        });
    });

    // Name input handlers
    document.querySelectorAll('.coll-name-input').forEach(input => {
        const collId = input.closest('.coll-group-hdr').dataset.collId;
        input.addEventListener('blur', () => finishRenameCollection(collId, input.value));
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') finishRenameCollection(collId, input.value);
            if (e.key === 'Escape') { renderCollections(); }
        });
        input.addEventListener('click', e => e.stopPropagation());
    });
}

function startRenameCollection(collId) {
    const hdr = document.querySelector(`.coll-group-hdr[data-coll-id="${collId}"]`);
    if (!hdr) return;
    hdr.classList.add('editing');
    const input = hdr.querySelector('.coll-name-input');
    input.style.display = 'block';
    input.focus();
    input.select();
}

function finishRenameCollection(collId, newName) {
    const coll = collections.find(c => c.id === collId);
    if (coll && newName.trim()) coll.name = newName.trim();
    saveCollections();
    renderCollections();
}

function downloadJsonFile(filename, data) {
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function buildPostmanCollectionItem(item) {
    const request = {
        method: item.method || 'GET',
        header: (item.headers || []).filter(h => h.key).map(h => ({ key: h.key, value: h.value })),
        url: {
            raw: item.url || '',
            query: (item.params || []).filter(p => p.key).map(p => ({ key: p.key, value: p.value })),
        }
    };

    if (item.bodyType === 'json' && item.bodyJson.trim()) {
        request.body = {
            mode: 'raw',
            raw: item.bodyJson,
            options: { raw: { language: 'json' } }
        };
    } else if (item.bodyType === 'raw' && item.bodyRaw.trim()) {
        request.body = { mode: 'raw', raw: item.bodyRaw };
    } else if (item.bodyType === 'form') {
        request.body = {
            mode: 'formdata',
            formdata: (item.bodyForm || []).filter(f => f.key).map(f => ({ key: f.key, value: f.value }))
        };
    } else if (item.bodyType === 'urlencoded') {
        request.body = {
            mode: 'urlencoded',
            urlencoded: (item.bodyUrlEncoded || []).filter(f => f.key).map(f => ({ key: f.key, value: f.value }))
        };
    }

    const result = {
        name: item.name || item.url || item.method,
        request
    };

    const savedResponses = Array.isArray(item.responses) ? item.responses : [];
    if (savedResponses.length) {
        result.response = savedResponses.map(r => ({
            name: r.name || (r.code ? String(r.code) : 'Response'),
            originalRequest: request,
            status: r.status ?? r.code,
            code: r.code ?? r.status,
            body: r.body || '',
            header: Array.isArray(r.header)
                ? r.header.map(h => ({ key: h.key, value: h.value }))
                : Object.entries(r.header || {}).map(([key, value]) => ({ key, value }))
        }));
    } else if (item.responseState?.type === 'response' && item.responseState.payload) {
        const payload = item.responseState.payload;
        result.response = [{
            name: payload.statusText ? String(payload.status) + ' ' + payload.statusText : String(payload.status),
            originalRequest: request,
            status: payload.status,
            code: payload.status,
            body: payload.body || '',
            header: Object.entries(payload.headers || {}).map(([key, value]) => ({ key, value }))
        }];
    }

    return result;
}

function downloadCollection(collId) {
    const coll = collections.find(c => c.id === collId);
    if (!coll) return;

    const payload = {
        info: {
            name: coll.name,
            _postman_id: uid(),
            description: 'Exported from TechIA Panel HTTP Client',
            schema: 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
        },
        item: (coll.items || []).map(buildPostmanCollectionItem)
    };

    const filename = (coll.name || 'collection').replace(/[\\/:*?"<>|]/g, '_') + '.postman_collection.json';
    downloadJsonFile(filename, payload);
    showToast('Coleção exportada', 'success');
}

function loadCollectionItem(collId, itemId) {
    const coll = collections.find(c => c.id === collId);
    if (!coll) return;
    const item = (coll.items||[]).find(it => it.id === itemId);
    if (!item) return;

    // Restore state
    state.method    = item.method || 'GET';
    state.url       = item.url || '';
    state.params    = item.params ? JSON.parse(JSON.stringify(item.params)) : [];
    state.headers   = item.headers ? JSON.parse(JSON.stringify(item.headers)) : [];
    state.bodyType  = item.bodyType || 'none';
    state.bodyJson  = item.bodyJson || '';
    state.bodyRaw   = item.bodyRaw || '';
    state.bodyForm  = item.bodyForm ? JSON.parse(JSON.stringify(item.bodyForm)) : [];
    state.bodyUrlEncoded = item.bodyUrlEncoded ? JSON.parse(JSON.stringify(item.bodyUrlEncoded)) : [];
    state.responseState = item.responseState || { type: 'empty' };

    if (item.responses && item.responses.length && state.responseState.type !== 'response') {
        const resp = item.responses[0];
        state.responseState = {
            type: 'response',
            payload: {
                status: resp.status || resp.code || '',
                statusText: resp.name || '',
                elapsed: '',
                size: resp.body ? resp.body.length : 0,
                headers: (resp.header || []).reduce((acc, h) => {
                    if (h && h.key) acc[h.key] = h.value || '';
                    return acc;
                }, {}),
                body: resp.body || ''
            }
        };
    }

    applyStateToUI();
    syncStateToActiveRequest();
    renderRequestTabs();
    restoreResponseFromState();
    showToast('Requisição carregada', 'success');
}

document.getElementById('btn-new-collection').addEventListener('click', () => {
    const name = prompt('Nome da nova coleção:');
    if (!name || !name.trim()) return;
    collections.push({id: uid(), name: name.trim(), items: []});
    saveCollections();
    renderCollections();
});

document.getElementById('coll-search').addEventListener('input', renderCollections);

// ═══════════════════════════════════════════════════════════════
// Save Request
// ═══════════════════════════════════════════════════════════════

const saveModal  = document.getElementById('save-modal');
const saveSelect = document.getElementById('save-coll-select');

document.getElementById('btn-save-request').addEventListener('click', () => {
    // Populate collection dropdown
    saveSelect.innerHTML = '<option value="__new__">+ Nova coleção...</option>';
    collections.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id; opt.textContent = c.name;
        saveSelect.appendChild(opt);
    });
    document.getElementById('save-req-name').value = '';
    document.getElementById('new-coll-wrap').style.display = 'none';
    document.getElementById('save-response-checkbox').checked = state.responseState?.type === 'response';
    saveModal.classList.add('open');
    document.getElementById('save-req-name').focus();
});

saveSelect.addEventListener('change', () => {
    document.getElementById('new-coll-wrap').style.display = saveSelect.value === '__new__' ? 'block' : 'none';
});

document.getElementById('btn-cancel-save').addEventListener('click', () => saveModal.classList.remove('open'));
saveModal.addEventListener('click', e => { if (e.target === saveModal) saveModal.classList.remove('open'); });

document.getElementById('btn-confirm-save').addEventListener('click', () => {
    const name = document.getElementById('save-req-name').value.trim();
    if (!name) { showToast('Informe um nome', 'error'); return; }

    let collId = saveSelect.value;

    if (collId === '__new__') {
        const collName = document.getElementById('new-coll-name').value.trim();
        if (!collName) { showToast('Informe o nome da coleção', 'error'); return; }
        const newColl = {id: uid(), name: collName, items: []};
        collections.push(newColl);
        collId = newColl.id;
    }

    const coll = collections.find(c => c.id === collId);
    if (!coll) return;

    const saveResponse = document.getElementById('save-response-checkbox').checked || state.responseState?.type === 'response';
    const responseState = saveResponse ? JSON.parse(JSON.stringify(state.responseState || { type: 'empty' })) : { type: 'empty' };
    const responsePayload = responseState.type === 'response' ? responseState.payload : null;
    const savedResponses = responsePayload ? [{
        name: responsePayload.statusText ? String(responsePayload.status) + ' ' + responsePayload.statusText : String(responsePayload.status),
        status: responsePayload.status,
        code: responsePayload.status,
        body: responsePayload.body || '',
        header: Object.entries(responsePayload.headers || {}).map(([key, value]) => ({ key, value }))
    }] : [];

    const active = getActiveRequest();
    if (active) {
        active.savedName = name;
        active.name = name;
        active.method = state.method;
        active.url = urlInput.value;
        active.params = JSON.parse(JSON.stringify(state.params));
        active.headers = JSON.parse(JSON.stringify(state.headers));
        active.bodyType = state.bodyType;
        active.bodyJson = jsonBodyEl.value;
        active.bodyRaw = rawBodyEl.value;
        active.bodyForm = JSON.parse(JSON.stringify(state.bodyForm));
        active.bodyUrlEncoded = JSON.parse(JSON.stringify(state.bodyUrlEncoded));
        active.variables = JSON.parse(JSON.stringify(state.variables));
        active.responseState = responseState;
        active.responses = savedResponses;
    }

    coll.items = coll.items || [];
    coll.items.push({
        id: uid(), name, method: state.method, url: urlInput.value,
        params: JSON.parse(JSON.stringify(state.params)),
        headers: JSON.parse(JSON.stringify(state.headers)),
        bodyType: state.bodyType,
        bodyJson: jsonBodyEl.value,
        bodyRaw: rawBodyEl.value,
        bodyForm: JSON.parse(JSON.stringify(state.bodyForm)),
        bodyUrlEncoded: JSON.parse(JSON.stringify(state.bodyUrlEncoded)),
        responseState,
        responses: savedResponses
    });

    saveCollections();
    renderCollections();
    saveModal.classList.remove('open');
    showToast('Salvo em "' + coll.name + '"', 'success');
});

// ═══════════════════════════════════════════════════════════════
// History
// ═══════════════════════════════════════════════════════════════

function addToHistory(entry) {
    history.unshift(entry);
    if (history.length > MAX_HISTORY) history = history.slice(0, MAX_HISTORY);
    saveHistory();
    renderHistory();
}

function renderHistory() {
    const list  = document.getElementById('hist-list');
    const empty = document.getElementById('hist-empty');

    document.querySelectorAll('.hist-item').forEach(i => i.remove());

    if (!history.length) { empty.style.display = 'block'; return; }
    empty.style.display = 'none';

    history.forEach(h => {
        const item = document.createElement('div');
        item.className = 'hist-item';
        const date = new Date(h.timestamp);
        const timeStr = date.toLocaleTimeString('pt-BR', {hour:'2-digit', minute:'2-digit'});
        item.innerHTML = `
            <span class="method-badge m-${(h.method||'GET').toLowerCase()}">${esc(h.method||'GET')}</span>
            <span class="hist-url" title="${esc(h.url)}">${esc(h.url)}</span>
            <span class="hist-time">${timeStr}</span>`;
        item.addEventListener('click', () => {
            persistActiveState();
            urlInput.value = h.url;
            state.url      = h.url;
            methodSelect.value = h.method || 'GET';
            updateMethodColor();
            // parse query params from url
            state.params = urlToParams(h.url);
            renderParamRows();
            updateBadges();
            syncStateToActiveRequest();
        });
        list.insertBefore(item, empty);
    });
}

document.getElementById('btn-clear-history').addEventListener('click', () => {
    if (confirm('Limpar todo o histórico?')) {
        history = [];
        saveHistory();
        renderHistory();
    }
});

// ═══════════════════════════════════════════════════════════════
// Apply State To UI
// ═══════════════════════════════════════════════════════════════

function applyStateToUI() {
    methodSelect.value = state.method;
    updateMethodColor();
    urlInput.value     = state.url;
    jsonBodyEl.value   = state.bodyJson;
    rawBodyEl.value    = state.bodyRaw;

    // Body type
    bodyTypeTabs.forEach(t => t.classList.toggle('active', t.dataset.bodyType === state.bodyType));
    Object.entries(bodyTypePanels).forEach(([k, panelId]) => {
        document.getElementById(panelId).classList.toggle('active', k === state.bodyType);
    });

    renderParamRows();
    renderHeaderRows();
    renderFormRows();
    renderUrlEncRows();
    renderVarRows();
    updateBadges();
}

// ═══════════════════════════════════════════════════════════════
// Resize Handle
// ═══════════════════════════════════════════════════════════════

const resizeHandle  = document.getElementById('resize-handle');
const reqEditor     = document.getElementById('req-editor');
const resPanel      = document.getElementById('res-panel');
const mainPanel     = document.getElementById('main-panel');

let isResizing = false;
let resizeStart = 0;
let startEditorH = 0;

resizeHandle.addEventListener('mousedown', e => {
    isResizing   = true;
    resizeStart  = e.clientY;
    startEditorH = reqEditor.getBoundingClientRect().height;
    resizeHandle.classList.add('dragging');
    document.body.style.cursor = 'row-resize';
    document.body.style.userSelect = 'none';
});

document.addEventListener('mousemove', e => {
    if (!isResizing) return;
    const delta    = e.clientY - resizeStart;
    const newH     = Math.max(180, Math.min(startEditorH + delta, mainPanel.getBoundingClientRect().height - 120));
    reqEditor.style.flexShrink = '0';
    reqEditor.style.height     = newH + 'px';
    reqEditor.style.overflow   = 'hidden';
});

document.addEventListener('mouseup', () => {
    if (!isResizing) return;
    isResizing = false;
    resizeHandle.classList.remove('dragging');
    document.body.style.cursor = '';
    document.body.style.userSelect = '';
});

// ═══════════════════════════════════════════════════════════════
// Toast Notifications
// ═══════════════════════════════════════════════════════════════

let toastTimer = null;
let toastEl    = null;

function showToast(msg, type = 'info') {
    if (!toastEl) {
        toastEl = document.createElement('div');
        toastEl.style.cssText = `
            position:fixed;bottom:20px;right:20px;z-index:99999;
            background:var(--surface);border:1px solid var(--border);
            border-radius:8px;padding:10px 16px;font-size:13px;
            box-shadow:0 8px 24px rgba(0,0,0,.5);
            display:flex;align-items:center;gap:8px;
            transform:translateY(20px);opacity:0;
            transition:opacity .2s,transform .2s;pointer-events:none;min-width:180px;
        `;
        document.body.appendChild(toastEl);
    }

    const colors = { success: 'var(--green)', error: 'var(--red)', info: 'var(--blue)' };
    const icons  = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', info: 'bi-info-circle-fill' };

    toastEl.innerHTML = `<i class="bi ${icons[type]||icons.info}" style="color:${colors[type]||colors.info}"></i> ${esc(msg)}`;
    toastEl.style.opacity = '1';
    toastEl.style.transform = 'translateY(0)';

    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toastEl.style.opacity = '0';
        toastEl.style.transform = 'translateY(20px)';
    }, 2500);
}

// ═══════════════════════════════════════════════════════════════
// Init
// ═══════════════════════════════════════════════════════════════

function init() {
    // Restore saved variables
    if (savedVars.length) state.variables = JSON.parse(JSON.stringify(savedVars));

    requests = [createRequestEntry('Nova requisição')];
    activeRequestId = requests[0].id;
    loadRequestIntoState(requests[0]);

    // Render everything
    renderParamRows();
    renderHeaderRows();
    renderFormRows();
    renderUrlEncRows();
    renderVarRows();
    renderCollections();
    renderHistory();
    renderRequestTabs();
    updateBadges();

    // Default empty rows
    if (!state.params.length)  addRow(state.params, paramsTbody, onParamsUpdate);
    if (!state.headers.length) addRow(state.headers, headersTbody, updateBadges);

    document.getElementById('btn-new-request-tab').addEventListener('click', addRequestTab);

    applyStateToUI();
    showEmpty();
}

init();
</script>
</body>
</html>
