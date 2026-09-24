<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JSON Formatter — TechIA Panel</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg:      #0d1117;
            --surface: #161b22;
            --surface2:#1c2128;
            --border:  #30363d;
            --border2: #21262d;
            --text:    #e6edf3;
            --muted:   #8b949e;
            --subtle:  #6e7681;
            --blue:    #58a6ff;
            --blue-dim:#1f6feb;
            --green:   #3fb950;
            --red:     #f85149;
            --orange:  #d29922;
            --yellow:  #e3b341;
            --purple:  #bc8cff;
            --cyan:    #39c5cf;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; overflow: hidden; background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; font-size: 13px; }

        /* ── Topbar ── */
        .topbar {
            height: 46px; background: var(--surface); border-bottom: 1px solid var(--border);
            display: flex; align-items: center; padding: 0 16px; gap: 12px; flex-shrink: 0; z-index: 10;
        }
        .brand { display: flex; align-items: center; gap: 7px; text-decoration: none; font-weight: 700; font-size: 15px; color: var(--text); }
        .brand-icon { width: 26px; height: 26px; background: linear-gradient(135deg, var(--blue-dim), var(--blue)); border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 13px; }
        .brand span { color: var(--blue); }
        .topbar-sep { width: 1px; height: 18px; background: var(--border); }
        .topbar-title { font-size: 13px; font-weight: 600; color: var(--muted); display: flex; align-items: center; gap: 6px; }
        .topbar-title i { color: var(--blue); }
        .ms-auto { margin-left: auto; }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 6px; font-size: 12.5px; font-weight: 500; cursor: pointer; border: 1px solid var(--border); transition: background .12s, border-color .12s, color .12s; font-family: inherit; }
        .btn-primary { background: var(--blue-dim); border-color: var(--blue-dim); color: #fff; }
        .btn-primary:hover { background: #2176d2; border-color: #2176d2; }
        .btn-ghost { background: transparent; color: var(--muted); }
        .btn-ghost:hover { background: rgba(255,255,255,.07); color: var(--text); }
        .btn-danger { background: transparent; color: var(--red); border-color: var(--border); }
        .btn-danger:hover { background: rgba(248,81,73,.1); border-color: var(--red); }
        .btn-success { background: rgba(63,185,80,.15); color: var(--green); border-color: rgba(63,185,80,.3); }
        .btn-success:hover { background: rgba(63,185,80,.25); }

        /* ── Layout ── */
        #app { display: flex; flex-direction: column; height: calc(100vh - 46px); }

        /* Toolbar */
        #toolbar {
            height: 44px; background: var(--surface2); border-bottom: 1px solid var(--border2);
            display: flex; align-items: center; padding: 0 14px; gap: 8px; flex-shrink: 0;
        }
        .indent-select { background: var(--bg); border: 1px solid var(--border); color: var(--text); border-radius: 5px; padding: 4px 8px; font-size: 12px; font-family: inherit; outline: none; cursor: pointer; }
        .indent-select:focus { border-color: var(--blue); }
        .toolbar-sep { width: 1px; height: 20px; background: var(--border2); margin: 0 4px; }
        #error-badge {
            display: none; align-items: center; gap: 5px; padding: 3px 10px; background: rgba(248,81,73,.12);
            border: 1px solid rgba(248,81,73,.3); border-radius: 5px; color: var(--red); font-size: 12px; font-family: 'JetBrains Mono', monospace;
        }
        #valid-badge {
            display: none; align-items: center; gap: 5px; padding: 3px 10px; background: rgba(63,185,80,.1);
            border: 1px solid rgba(63,185,80,.25); border-radius: 5px; color: var(--green); font-size: 12px;
        }
        #stats-badge { font-size: 11.5px; color: var(--subtle); }

        /* ── Panels ── */
        #panels { display: flex; flex: 1; overflow: hidden; }

        .panel { display: flex; flex-direction: column; flex: 1; min-width: 0; overflow: hidden; }
        .panel-hdr {
            height: 34px; background: var(--surface); border-bottom: 1px solid var(--border2);
            display: flex; align-items: center; padding: 0 12px; gap: 8px; flex-shrink: 0;
            font-size: 11.5px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .5px;
        }
        .panel-hdr .panel-actions { margin-left: auto; display: flex; gap: 5px; }

        #resize-bar {
            width: 4px; background: var(--border2); cursor: col-resize; flex-shrink: 0; transition: background .12s;
        }
        #resize-bar:hover, #resize-bar.dragging { background: var(--blue-dim); }

        /* Input panel */
        #input-wrap { flex: 1; position: relative; overflow: hidden; }
        #input-area {
            width: 100%; height: 100%; resize: none; background: var(--bg); color: var(--text);
            border: none; outline: none; padding: 14px 16px; font-family: 'JetBrains Mono', monospace;
            font-size: 13px; line-height: 1.6; tab-size: 2;
        }
        #input-area::placeholder { color: var(--subtle); }

        /* Output panel */
        #output-wrap { flex: 1; overflow: auto; padding: 14px 16px; }
        #output-wrap::-webkit-scrollbar { width: 5px; height: 5px; }
        #output-wrap::-webkit-scrollbar-track { background: transparent; }
        #output-wrap::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

        #output-pre {
            font-family: 'JetBrains Mono', monospace; font-size: 13px; line-height: 1.7;
            white-space: pre; margin: 0; word-break: break-all;
        }

        /* Syntax highlighting */
        .jk  { color: var(--blue); }          /* key */
        .js  { color: var(--cyan); }          /* string value */
        .jn  { color: var(--purple); }        /* number */
        .jb  { color: var(--orange); }        /* boolean */
        .jnu { color: var(--red); }           /* null */
        .jp  { color: var(--muted); }         /* punctuation */

        /* Empty state */
        #empty-state {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            height: 100%; color: var(--subtle); text-align: center; gap: 10px; padding: 40px;
        }
        #empty-state i { font-size: 36px; color: var(--border); }
        #empty-state p { font-size: 12.5px; }

        /* Line numbers */
        .line-num { color: var(--subtle); user-select: none; min-width: 2.5em; display: inline-block; text-align: right; margin-right: 1.2em; font-size: 11.5px; }

        /* Tree view */
        .tree-node { cursor: pointer; }
        .tree-toggle { display: inline-block; width: 14px; text-align: center; color: var(--muted); font-size: 10px; user-select: none; }
        .tree-children { margin-left: 20px; }
        .tree-children.collapsed { display: none; }
        .tree-key { color: var(--blue); }
        .tree-string { color: var(--cyan); }
        .tree-number { color: var(--purple); }
        .tree-bool { color: var(--orange); }
        .tree-null { color: var(--red); }
        .tree-collapse-btn { color: var(--subtle); font-size: 11px; cursor: pointer; padding: 0 3px; border-radius: 3px; }
        .tree-collapse-btn:hover { background: rgba(255,255,255,.08); color: var(--text); }

        /* View mode tabs */
        .view-tabs { display: flex; gap: 2px; }
        .view-tab { padding: 2px 9px; border-radius: 4px; font-size: 11.5px; cursor: pointer; color: var(--muted); font-weight: 500; transition: background .1s, color .1s; }
        .view-tab:hover { color: var(--text); }
        .view-tab.active { background: rgba(88,166,255,.15); color: var(--blue); }

        /* Toast */
        #toast { position: fixed; bottom: 20px; right: 20px; padding: 8px 16px; background: var(--surface); border: 1px solid var(--border); border-radius: 6px; font-size: 12.5px; display: flex; align-items: center; gap: 7px; opacity: 0; transition: opacity .2s; pointer-events: none; z-index: 999; }
        #toast.show { opacity: 1; }
        #toast.ok  { border-color: rgba(63,185,80,.4); color: var(--green); }
        #toast.err { border-color: rgba(248,81,73,.4); color: var(--red); }

        /* Diff view */
        #diff-wrap { flex: 1; overflow: auto; padding: 14px 16px; display: none; }
        .diff-line { font-family: 'JetBrains Mono', monospace; font-size: 12.5px; line-height: 1.65; white-space: pre; }
        .diff-add { color: var(--green); background: rgba(63,185,80,.06); }
        .diff-del { color: var(--red);   background: rgba(248,81,73,.06); }
        .diff-ctx { color: var(--muted); }
    </style>
</head>
<body>

<header class="topbar">
    <a href="{{ route('connections.index') }}" class="brand">
        <div class="brand-icon"><i class="bi bi-terminal-fill" style="color:#fff"></i></div>
        Leo<span>Panel</span>
    </a>
    <div class="topbar-sep"></div>
    <div class="topbar-title">
        <i class="bi bi-braces"></i> JSON Formatter
    </div>
    <div class="ms-auto" style="display:flex;align-items:center;gap:6px">
        <button class="btn btn-ghost" onclick="loadSample()"><i class="bi bi-lightbulb"></i> Exemplo</button>
    </div>
</header>

<div id="app">

    <!-- Toolbar -->
    <div id="toolbar">
        <button class="btn btn-primary" onclick="format()" title="Ctrl+Enter">
            <i class="bi bi-braces"></i> Formatar
        </button>
        <button class="btn btn-ghost" onclick="minify()" title="Minificar JSON">
            <i class="bi bi-dash-circle"></i> Minificar
        </button>
        <button class="btn btn-ghost" onclick="sortKeys()" title="Ordenar chaves">
            <i class="bi bi-sort-alpha-down"></i> Ordenar
        </button>
        <div class="toolbar-sep"></div>
        <label style="font-size:12px;color:var(--muted);display:flex;align-items:center;gap:6px">
            Indentação
            <select class="indent-select" id="indentSize">
                <option value="2" selected>2 espaços</option>
                <option value="4">4 espaços</option>
                <option value="tab">Tab</option>
            </select>
        </label>
        <div class="toolbar-sep"></div>
        <span id="error-badge"><i class="bi bi-x-circle-fill"></i> <span id="error-msg">Erro</span></span>
        <span id="valid-badge"><i class="bi bi-check-circle-fill"></i> JSON válido</span>
        <span id="stats-badge" style="display:none"></span>
    </div>

    <!-- Panels -->
    <div id="panels">

        <!-- Input panel -->
        <div class="panel" id="panel-left" style="flex:0 0 50%">
            <div class="panel-hdr">
                <i class="bi bi-pencil" style="color:var(--blue)"></i> Entrada
                <div class="panel-actions">
                    <button class="btn btn-ghost" style="padding:2px 8px;font-size:11.5px" onclick="clearInput()" title="Limpar">
                        <i class="bi bi-trash3"></i>
                    </button>
                    <button class="btn btn-ghost" style="padding:2px 8px;font-size:11.5px" onclick="pasteFromClipboard()" title="Colar da área de transferência">
                        <i class="bi bi-clipboard"></i> Colar
                    </button>
                </div>
            </div>
            <div id="input-wrap">
                <textarea id="input-area" spellcheck="false"
                    placeholder='Cole seu JSON aqui ou clique em "Exemplo"…'></textarea>
            </div>
        </div>

        <!-- Resize bar -->
        <div id="resize-bar"></div>

        <!-- Output panel -->
        <div class="panel" id="panel-right">
            <div class="panel-hdr">
                <i class="bi bi-eye" style="color:var(--green)"></i> Resultado
                <div class="view-tabs" style="margin-left:10px">
                    <div class="view-tab active" data-view="pretty" onclick="switchView('pretty')">Pretty</div>
                    <div class="view-tab" data-view="tree" onclick="switchView('tree')">Árvore</div>
                    <div class="view-tab" data-view="raw" onclick="switchView('raw')">Raw</div>
                </div>
                <div class="panel-actions">
                    <button class="btn btn-ghost" style="padding:2px 8px;font-size:11.5px" onclick="copyOutput()" title="Copiar resultado" id="copy-btn">
                        <i class="bi bi-copy"></i> Copiar
                    </button>
                    <button class="btn btn-ghost" style="padding:2px 8px;font-size:11.5px" onclick="downloadJson()" title="Baixar como arquivo">
                        <i class="bi bi-download"></i>
                    </button>
                </div>
            </div>
            <div id="output-wrap">
                <div id="empty-state">
                    <i class="bi bi-braces"></i>
                    <p>O JSON formatado aparecerá aqui</p>
                </div>
                <pre id="output-pre" style="display:none"></pre>
            </div>
            <div id="diff-wrap"></div>
        </div>

    </div>
</div>

<div id="toast"></div>

<script>
// ── State ────────────────────────────────────────────────────────────────────
let currentView  = 'pretty';
let parsedData   = null;
let formattedStr = '';

// ── Format ───────────────────────────────────────────────────────────────────
function format() {
    const raw = document.getElementById('input-area').value.trim();
    if (!raw) { showError('Nenhum conteúdo para formatar.'); return; }

    try {
        parsedData   = JSON.parse(raw);
        const indent = getIndent();
        formattedStr = JSON.stringify(parsedData, null, indent);
        showValid();
        renderView();
    } catch (e) {
        parsedData   = null;
        formattedStr = '';
        showError(e.message);
        document.getElementById('output-pre').style.display  = 'none';
        document.getElementById('empty-state').style.display = 'flex';
        document.getElementById('diff-wrap').style.display   = 'none';
    }
}

function minify() {
    const raw = document.getElementById('input-area').value.trim();
    if (!raw) return;
    try {
        const obj = JSON.parse(raw);
        const min = JSON.stringify(obj);
        document.getElementById('input-area').value = min;
        document.getElementById('input-area').dispatchEvent(new Event('input'));
        toast('ok', 'Minificado.');
    } catch (e) {
        showError(e.message);
    }
}

function sortKeys() {
    const raw = document.getElementById('input-area').value.trim();
    if (!raw) return;
    try {
        const obj    = JSON.parse(raw);
        const sorted = sortDeep(obj);
        const indent = getIndent();
        document.getElementById('input-area').value = JSON.stringify(sorted, null, indent);
        format();
        toast('ok', 'Chaves ordenadas.');
    } catch (e) {
        showError(e.message);
    }
}

function sortDeep(val) {
    if (Array.isArray(val)) return val.map(sortDeep);
    if (val !== null && typeof val === 'object') {
        return Object.keys(val).sort().reduce((acc, k) => { acc[k] = sortDeep(val[k]); return acc; }, {});
    }
    return val;
}

// ── Views ────────────────────────────────────────────────────────────────────
function switchView(view) {
    currentView = view;
    document.querySelectorAll('.view-tab').forEach(t => t.classList.toggle('active', t.dataset.view === view));
    if (parsedData !== null) renderView();
}

function renderView() {
    const outputWrap = document.getElementById('output-wrap');
    const outputPre  = document.getElementById('output-pre');
    const emptyState = document.getElementById('empty-state');
    const diffWrap   = document.getElementById('diff-wrap');

    emptyState.style.display = 'none';
    diffWrap.style.display   = 'none';
    outputPre.style.display  = 'none';
    outputWrap.style.display = 'block';

    if (currentView === 'pretty') {
        outputPre.innerHTML     = syntaxHighlight(formattedStr);
        outputPre.style.display = 'block';
        updateStats(formattedStr);
    } else if (currentView === 'raw') {
        outputPre.textContent   = formattedStr;
        outputPre.style.display = 'block';
        updateStats(formattedStr);
    } else if (currentView === 'tree') {
        outputPre.style.display = 'none';
        diffWrap.style.display  = 'block';
        diffWrap.innerHTML      = buildTree(parsedData, null, 0, true);
        updateStats(formattedStr);
    }
}

// ── Syntax highlight ─────────────────────────────────────────────────────────
function syntaxHighlight(str) {
    const esc = s => s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

    return str.replace(
        /("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+\.?\d*([eE][+\-]?\d+)?)/g,
        match => {
            if (/^"/.test(match)) {
                if (/:$/.test(match)) {
                    return `<span class="jk">${esc(match)}</span>`;
                }
                return `<span class="js">${esc(match)}</span>`;
            }
            if (/true|false/.test(match)) return `<span class="jb">${match}</span>`;
            if (/null/.test(match))        return `<span class="jnu">${match}</span>`;
            return `<span class="jn">${match}</span>`;
        }
    ).replace(/[{}\[\],]/g, m => `<span class="jp">${m}</span>`);
}

// ── Tree view ─────────────────────────────────────────────────────────────────
let _treeId = 0;
function buildTree(val, key, depth, isLast) {
    _treeId++;
    const id     = 'tn' + _treeId;
    const indent = '  '.repeat(depth);
    const comma  = isLast ? '' : '<span class="jp">,</span>';
    const keyHtml = key !== null ? `<span class="tree-key">"${escHtml(key)}"</span><span class="jp">: </span>` : '';

    if (val === null) return `<div class="diff-line">${indent}${keyHtml}<span class="tree-null">null</span>${comma}</div>`;
    if (typeof val === 'boolean') return `<div class="diff-line">${indent}${keyHtml}<span class="tree-bool">${val}</span>${comma}</div>`;
    if (typeof val === 'number') return `<div class="diff-line">${indent}${keyHtml}<span class="tree-number">${val}</span>${comma}</div>`;
    if (typeof val === 'string') return `<div class="diff-line">${indent}${keyHtml}<span class="tree-string">"${escHtml(val)}"</span>${comma}</div>`;

    const isArr = Array.isArray(val);
    const open  = isArr ? '[' : '{';
    const close = isArr ? ']' : '}';
    const keys  = isArr ? val.map((_, i) => i) : Object.keys(val);

    if (keys.length === 0) {
        return `<div class="diff-line">${indent}${keyHtml}<span class="jp">${open}${close}</span>${comma}</div>`;
    }

    const count   = keys.length;
    const preview = isArr ? `${count} item${count !== 1 ? 's' : ''}` : `${count} chave${count !== 1 ? 's' : ''}`;

    let children = '';
    keys.forEach((k, i) => {
        children += buildTree(isArr ? val[k] : val[k], isArr ? null : k, depth + 1, i === keys.length - 1);
    });

    return `<div class="diff-line tree-node">
        ${indent}<span class="tree-collapse-btn" onclick="toggleTree('${id}')" title="Colapsar/Expandir">▾</span>
        ${keyHtml}<span class="jp">${open}</span>
        <span id="${id}-preview" style="color:var(--subtle);font-size:11.5px;display:none"> ${escHtml(preview)} </span>
        <div id="${id}" class="tree-children">${children}</div>
        <span class="diff-line">${indent}<span class="jp">${close}</span>${comma}</span>
    </div>`;
}

function toggleTree(id) {
    const el      = document.getElementById(id);
    const preview = document.getElementById(id + '-preview');
    const btn     = el ? el.previousElementSibling?.previousElementSibling : null;
    if (!el) return;
    const collapsed = el.classList.toggle('collapsed');
    if (preview) preview.style.display = collapsed ? 'inline' : 'none';
    // update toggle icon
    const toggleBtn = document.querySelector(`[onclick="toggleTree('${id}')"]`);
    if (toggleBtn) toggleBtn.textContent = collapsed ? '▸' : '▾';
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Feedback ─────────────────────────────────────────────────────────────────
function showError(msg) {
    const clean = msg.replace(/^JSON\.parse:\s*/i, '').replace(/^SyntaxError:\s*/i, '');
    document.getElementById('error-msg').textContent  = clean;
    document.getElementById('error-badge').style.display = 'inline-flex';
    document.getElementById('valid-badge').style.display = 'none';
    document.getElementById('stats-badge').style.display = 'none';
}

function showValid() {
    document.getElementById('error-badge').style.display = 'none';
    document.getElementById('valid-badge').style.display = 'inline-flex';
    document.getElementById('stats-badge').style.display = 'inline';
}

function updateStats(str) {
    const lines = str.split('\n').length;
    const bytes = new Blob([str]).size;
    const kb    = bytes >= 1024 ? (bytes / 1024).toFixed(1) + ' KB' : bytes + ' B';
    document.getElementById('stats-badge').textContent = `${lines} linhas · ${kb}`;
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function getIndent() {
    const v = document.getElementById('indentSize').value;
    return v === 'tab' ? '\t' : parseInt(v);
}

function clearInput() {
    document.getElementById('input-area').value = '';
    parsedData   = null;
    formattedStr = '';
    document.getElementById('output-pre').style.display  = 'none';
    document.getElementById('empty-state').style.display = 'flex';
    document.getElementById('diff-wrap').style.display   = 'none';
    document.getElementById('error-badge').style.display = 'none';
    document.getElementById('valid-badge').style.display = 'none';
    document.getElementById('stats-badge').style.display = 'none';
}

async function pasteFromClipboard() {
    try {
        const text = await navigator.clipboard.readText();
        document.getElementById('input-area').value = text;
        format();
    } catch { toast('err', 'Sem permissão para acessar a área de transferência.'); }
}

function copyOutput() {
    if (!formattedStr) return;
    navigator.clipboard.writeText(formattedStr).then(() => {
        toast('ok', 'Copiado!');
        const btn = document.getElementById('copy-btn');
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Copiado';
        setTimeout(() => { btn.innerHTML = '<i class="bi bi-copy"></i> Copiar'; }, 1800);
    }).catch(() => toast('err', 'Erro ao copiar.'));
}

function downloadJson() {
    if (!formattedStr) return;
    const blob = new Blob([formattedStr], { type: 'application/json' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = 'formatted.json'; a.click();
    URL.revokeObjectURL(url);
}

function loadSample() {
    document.getElementById('input-area').value = JSON.stringify({
        "nome": "TechIA Panel",
        "versao": "1.0.0",
        "descricao": "Painel SSH privado",
        "autor": { "nome": "Leonardo", "email": "leo@example.com" },
        "features": ["SSH", "SFTP", "Terminal", "HTTP Client", "JSON Formatter"],
        "configuracao": {
            "porta": 22,
            "timeout": 15,
            "sudo": true,
            "conexoes_max": null
        }
    });
    format();
}

function toast(type, msg) {
    const el = document.getElementById('toast');
    el.className = 'show ' + type;
    el.innerHTML = `<i class="bi ${type === 'ok' ? 'bi-check-circle-fill' : 'bi-x-circle-fill'}"></i> ${msg}`;
    clearTimeout(el._t);
    el._t = setTimeout(() => { el.className = ''; }, 2200);
}

// ── Resize bar ────────────────────────────────────────────────────────────────
(function () {
    const bar   = document.getElementById('resize-bar');
    const left  = document.getElementById('panel-left');
    let dragging = false, startX = 0, startW = 0;

    bar.addEventListener('mousedown', e => {
        dragging = true; startX = e.clientX; startW = left.offsetWidth;
        bar.classList.add('dragging');
        document.body.style.cursor = 'col-resize';
        document.body.style.userSelect = 'none';
    });
    document.addEventListener('mousemove', e => {
        if (!dragging) return;
        const panels = document.getElementById('panels').offsetWidth;
        const newW   = Math.max(200, Math.min(panels - 200, startW + (e.clientX - startX)));
        left.style.flex = `0 0 ${newW}px`;
    });
    document.addEventListener('mouseup', () => {
        if (!dragging) return;
        dragging = false; bar.classList.remove('dragging');
        document.body.style.cursor = ''; document.body.style.userSelect = '';
    });
})();

// ── Keyboard shortcuts ────────────────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); format(); }
    if ((e.ctrlKey || e.metaKey) && e.key === 'k')     { e.preventDefault(); clearInput(); }
});

// ── Auto-format on paste ──────────────────────────────────────────────────────
document.getElementById('input-area').addEventListener('paste', () => {
    setTimeout(format, 30);
});

// ── Indent change ─────────────────────────────────────────────────────────────
document.getElementById('indentSize').addEventListener('change', () => {
    if (parsedData !== null) format();
});
</script>
</body>
</html>
