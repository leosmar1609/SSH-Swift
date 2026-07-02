@extends('layouts.app')

@section('title', 'Documentação API')

@section('content')

<div class="lp-page-header">
    <div>
        <h1 class="lp-page-title">Gerador de Documentação</h1>
        <p class="lp-page-subtitle">Importe uma collection do Postman e gere um PDF profissional</p>
    </div>
</div>

{{-- ── Upload Area ─────────────────────────────────────────── --}}
<div id="upload-area">
    <div id="drop-zone">
        <i class="bi bi-file-earmark-code" style="font-size:2.5rem;color:var(--lp-text-subtle);display:block;margin-bottom:1rem"></i>
        <p style="font-size:15px;font-weight:600;color:var(--lp-text);margin-bottom:.4rem">Arraste a collection aqui</p>
        <p style="font-size:13px;color:var(--lp-text-muted);margin-bottom:1.5rem">ou clique para selecionar o arquivo .json</p>
        <label for="file-input" class="btn-lp-primary" style="cursor:pointer">
            <i class="bi bi-upload"></i> Selecionar Collection
        </label>
        <input type="file" id="file-input" accept=".json" style="display:none">
    </div>
</div>

{{-- ── Toolbar flutuante ────────────────────────────────────── --}}
<div id="doc-toolbar" style="display:none">
    <div style="display:flex;align-items:center;gap:.75rem;flex:1;min-width:0">
        <i class="bi bi-file-earmark-pdf" style="font-size:18px;color:var(--lp-blue);flex-shrink:0"></i>
        <div style="min-width:0">
            <div id="toolbar-name" style="font-weight:600;font-size:14px;color:var(--lp-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></div>
            <div id="toolbar-count" style="font-size:12px;color:var(--lp-text-muted)"></div>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:.5rem;flex-shrink:0">
        <button id="btn-pdf" class="btn-lp-primary">
            <i class="bi bi-file-earmark-pdf"></i> Gerar PDF
        </button>
        <button id="btn-reset" class="btn-lp-secondary">
            <i class="bi bi-arrow-repeat"></i> Nova Collection
        </button>
    </div>
</div>

{{-- ── Prévia da documentação ───────────────────────────────── --}}
<div id="doc-preview-wrap" style="display:none">
    <div id="doc-content">
        {{-- Gerado via JS --}}
    </div>
</div>

@endsection

@push('styles')
<style>
/* ── Upload ── */
#upload-area {
    display: flex;
    justify-content: center;
    padding: 3rem 0;
}

#drop-zone {
    background: var(--lp-surface);
    border: 2px dashed var(--lp-border);
    border-radius: 12px;
    padding: 3rem 4rem;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    max-width: 500px;
    width: 100%;
}

#drop-zone.dragover {
    border-color: var(--lp-blue);
    background: rgba(88,166,255,.06);
}

/* ── Toolbar ── */
#doc-toolbar {
    position: sticky;
    top: 56px;
    z-index: 90;
    background: var(--lp-surface);
    border: 1px solid var(--lp-border);
    border-radius: var(--lp-radius);
    padding: .85rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--lp-shadow-lg);
}

/* ── Doc preview wrapper ── */
#doc-preview-wrap {
    background: #f8f9fa;
    border: 1px solid var(--lp-border);
    border-radius: var(--lp-radius);
    padding: 2rem;
    overflow: hidden;
}

/* ── Doc content (light theme para PDF) ── */
#doc-content {
    max-width: 800px;
    margin: 0 auto;
    font-family: 'Inter', -apple-system, sans-serif;
    color: #1a1a2e;
    font-size: 13px;
    line-height: 1.6;
}

/* Cover */
.doc-cover {
    text-align: center;
    padding: 4rem 2rem 3rem;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 2.5rem;
}

.doc-cover-title {
    font-size: 28px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: .5rem;
    letter-spacing: -.5px;
}

.doc-cover-subtitle {
    font-size: 14px;
    color: #64748b;
}

.doc-cover-badge {
    display: inline-block;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    border-radius: 20px;
    padding: .3rem .9rem;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 1rem;
}

/* Folder separator */
.doc-folder {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #94a3b8;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: .4rem;
    margin: 2rem 0 1rem;
}

/* Endpoint card */
.doc-endpoint {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 1.25rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    page-break-inside: avoid;
    break-inside: avoid;
}

.doc-endpoint-header,
.doc-section-title,
.doc-response-header {
    page-break-after: avoid;
    break-after: avoid;
}

.doc-code {
    page-break-inside: auto;
    break-inside: auto;
}

.doc-folder {
    page-break-before: auto;
    break-before: auto;
    page-break-after: avoid;
    break-after: avoid;
}

.doc-endpoint-header {
    padding: .9rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: .75rem;
    background: #f8fafc;
}

.doc-endpoint-name {
    font-weight: 600;
    font-size: 14px;
    color: #0f172a;
    flex: 1;
}

/* Method badge */
.method-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: .2rem .6rem;
    border-radius: 5px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .5px;
    min-width: 56px;
    flex-shrink: 0;
}

.method-GET     { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
.method-POST    { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
.method-PUT     { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
.method-PATCH   { background:#fefce8; color:#a16207; border:1px solid #fde68a; }
.method-DELETE  { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
.method-HEAD    { background:#f8fafc; color:#475569; border:1px solid #cbd5e1; }
.method-OPTIONS { background:#faf5ff; color:#7e22ce; border:1px solid #e9d5ff; }

/* URL */
.doc-url {
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    font-size: 12px;
    color: #475569;
    background: #f1f5f9;
    padding: .1rem .5rem;
    border-radius: 4px;
    word-break: break-all;
    flex: 1;
    outline: none;
    cursor: text;
    transition: background .15s, box-shadow .15s;
}

.doc-url:hover {
    background: #e0e7ff;
    box-shadow: inset 0 0 0 1px #a5b4fc;
    color: #3730a3;
}

.doc-url:focus {
    background: #e0e7ff;
    box-shadow: inset 0 0 0 1.5px #6366f1;
    color: #1e1b4b;
}

/* Section */
.doc-section {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
}

.doc-section:last-child { border-bottom: none; }

.doc-section-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: #94a3b8;
    margin-bottom: .75rem;
}

/* Params table */
.doc-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
}

.doc-table th {
    background: #f1f5f9;
    color: #475569;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .5px;
    padding: .4rem .75rem;
    text-align: left;
    border: 1px solid #e2e8f0;
}

.doc-table td {
    padding: .45rem .75rem;
    border: 1px solid #e2e8f0;
    vertical-align: top;
    color: #334155;
}

.doc-table tr:nth-child(even) td { background: #f8fafc; }

.param-key {
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
    color: #0f172a;
    font-weight: 500;
}

.param-source {
    display: inline-block;
    font-size: 10px;
    font-weight: 600;
    padding: .05rem .35rem;
    border-radius: 3px;
    margin-left: .3rem;
    vertical-align: middle;
}

.param-source.query  { background:#eff6ff; color:#1d4ed8; }
.param-source.body   { background:#f0fdf4; color:#15803d; }
.param-source.form   { background:#fff7ed; color:#c2410c; }

/* Code block (response) */
.doc-code {
    background: #0f172a;
    color: #e2e8f0;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    font-size: 11.5px;
    line-height: 1.7;
    padding: 1rem 1.25rem;
    border-radius: 6px;
    overflow-x: auto;
    white-space: pre-wrap;
    word-break: break-word;
    margin: 0;
}

/* Response tabs */
.doc-response-item {
    margin-bottom: .75rem;
}

.doc-response-header {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-bottom: .4rem;
}

.doc-status-badge {
    display: inline-block;
    padding: .15rem .5rem;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    font-family: 'JetBrains Mono', monospace;
}

.status-2xx { background:#dcfce7; color:#15803d; }
.status-3xx { background:#fefce8; color:#a16207; }
.status-4xx { background:#fef2f2; color:#b91c1c; }
.status-5xx { background:#fdf4ff; color:#7e22ce; }
.status-xxx { background:#f1f5f9; color:#475569; }

/* Description */
.doc-desc {
    font-size: 13px;
    color: #64748b;
    padding: .75rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    font-style: italic;
    outline: none;
    cursor: text;
    border-radius: 0;
    transition: background .15s, box-shadow .15s;
    min-height: 2.2rem;
}

.doc-desc:empty::before {
    content: attr(data-placeholder);
    color: #cbd5e1;
    pointer-events: none;
}

.doc-desc:hover {
    background: rgba(99, 102, 241, .05);
    box-shadow: inset 3px 0 0 #a5b4fc;
}

.doc-desc:focus {
    background: rgba(99, 102, 241, .07);
    box-shadow: inset 3px 0 0 #6366f1;
    color: #334155;
    font-style: normal;
}

/* Editable table cell */
.doc-td-editable {
    outline: none;
    cursor: text;
    transition: background .15s, box-shadow .15s;
    min-width: 80px;
}

.doc-td-editable:empty::before {
    content: attr(data-placeholder);
    color: #cbd5e1;
    pointer-events: none;
}

.doc-td-editable:hover {
    background: rgba(99, 102, 241, .06) !important;
    box-shadow: inset 2px 0 0 #a5b4fc;
}

.doc-td-editable:focus {
    background: rgba(99, 102, 241, .09) !important;
    box-shadow: inset 2px 0 0 #6366f1;
    color: #334155 !important;
}

/* Empty state */
.doc-empty {
    text-align: center;
    color: #94a3b8;
    font-size: 12px;
    padding: .5rem 0;
}

/* @media print */
@media print {
    #doc-toolbar, #upload-area, .lp-navbar, .lp-main > *:not(#doc-preview-wrap) { display:none !important; }
    #doc-preview-wrap { border:none !important; padding:0 !important; background:white !important; }
    body { background: white !important; }
}
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
// ── State ───────────────────────────────────────────────────────
let currentCollection = null;

// ── Upload handling ─────────────────────────────────────────────
const fileInput  = document.getElementById('file-input');
const dropZone   = document.getElementById('drop-zone');

dropZone.addEventListener('click', () => fileInput.click());

dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) loadFile(file);
});

fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) loadFile(fileInput.files[0]);
});

function loadFile(file) {
    if (!file.name.endsWith('.json')) {
        alert('Selecione um arquivo .json do Postman.');
        return;
    }
    const reader = new FileReader();
    reader.onload = e => {
        try {
            const data = JSON.parse(e.target.result);
            currentCollection = parseCollection(data);
            renderDoc(currentCollection);
        } catch {
            alert('Arquivo inválido. Certifique-se de exportar a collection no formato JSON v2.1.');
        }
    };
    reader.readAsText(file);
}

// ── Postman parser ──────────────────────────────────────────────
function parseCollection(data) {
    const name = data.info?.name || 'API Documentation';
    const description = stripHtml(data.info?.description || '');
    const endpoints = [];

    function parseItems(items, folder) {
        for (const item of (items || [])) {
            if (item.item) {
                // É uma pasta/grupo
                parseItems(item.item, folder ? folder + ' › ' + item.name : item.name);
            } else if (item.request) {
                endpoints.push(parseEndpoint(item, folder || ''));
            }
        }
    }

    parseItems(data.item, '');
    return { name, description, endpoints };
}

function safeDecodeURI(str) {
    try { return decodeURIComponent(str); } catch { return str; }
}

function parseEndpoint(item, folder) {
    try {
        const req = item.request || {};
        const rawUrl = typeof req.url === 'string' ? req.url : (req.url?.raw || '');
        const method = (req.method || 'GET').toUpperCase();

        // ── URL sem query string ──
        const urlBase = rawUrl.split('?')[0];

        // ── Query params ──
        const queryParams = [];
        if (Array.isArray(req.url?.query)) {
            for (const q of req.url.query) {
                if (!q.disabled && q.key) {
                    queryParams.push({
                        key: q.key,
                        value: q.value ?? '',
                        description: stripHtml(q.description || ''),
                        source: 'query',
                    });
                }
            }
        } else {
            const qs = rawUrl.includes('?') ? rawUrl.split('?')[1] : '';
            if (qs) {
                for (const part of qs.split('&')) {
                    const idx = part.indexOf('=');
                    const k = safeDecodeURI(idx >= 0 ? part.slice(0, idx) : part);
                    const v = safeDecodeURI(idx >= 0 ? part.slice(idx + 1) : '');
                    if (k) queryParams.push({ key: k, value: v, description: '', source: 'query' });
                }
            }
        }

        // ── Body params ──
        const bodyParams = [];
        const body = req.body;
        if (body) {
            if (body.mode === 'raw' && body.raw) {
                try {
                    const parsed = JSON.parse(body.raw);
                    if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
                        flattenObj(parsed, '', bodyParams, 'body');
                    } else if (Array.isArray(parsed)) {
                        bodyParams.push({ key: '[array]', value: JSON.stringify(parsed), description: '', source: 'body' });
                    }
                } catch {
                    // raw body não é JSON válido — ignora params, mantém só o raw
                }
            } else if (body.mode === 'formdata' && Array.isArray(body.formdata)) {
                for (const f of body.formdata) {
                    if (!f.disabled && f.key) {
                        bodyParams.push({ key: f.key, value: f.value ?? '', description: stripHtml(f.description || ''), source: 'form' });
                    }
                }
            } else if (body.mode === 'urlencoded' && Array.isArray(body.urlencoded)) {
                for (const f of body.urlencoded) {
                    if (!f.disabled && f.key) {
                        bodyParams.push({ key: f.key, value: f.value ?? '', description: '', source: 'form' });
                    }
                }
            }
        }

        // ── Responses ──
        const responses = (item.response || []).filter(Boolean).map(r => {
            let bodyText = '';
            if (r.body) {
                try { bodyText = JSON.stringify(JSON.parse(r.body), null, 2); }
                catch { bodyText = r.body; }
            }
            return {
                name: r.name || 'Response',
                status: r.code ?? r.status ?? '',
                body: bodyText,
            };
        });

        const descRaw = req.description ?? '';
        const description = stripHtml(
            typeof descRaw === 'string' ? descRaw : (descRaw?.content ?? '')
        );

        return {
            folder,
            name: item.name || 'Endpoint',
            method,
            url: urlBase,
            allParams: [...queryParams, ...bodyParams],
            responses,
            description,
        };
    } catch (err) {
        // Endpoint mal formatado — retorna um card de erro visível
        return {
            folder,
            name: item.name || '(endpoint inválido)',
            method: 'GET',
            url: '',
            allParams: [],
            responses: [],
            description: 'Não foi possível processar este endpoint.',
        };
    }
}

// Flatten JSON object into key/value param rows
function flattenObj(obj, prefix, out, source, depth = 0) {
    if (!obj || typeof obj !== 'object' || depth > 4) return;
    for (const [k, v] of Object.entries(obj)) {
        const key = prefix ? prefix + '.' + k : k;
        if (v !== null && typeof v === 'object' && !Array.isArray(v) && depth < 3) {
            flattenObj(v, key, out, source, depth + 1);
        } else {
            out.push({ key, value: Array.isArray(v) ? JSON.stringify(v) : String(v ?? ''), description: '', source });
        }
    }
}

function stripHtml(str) {
    return str.replace(/<[^>]*>/g, '').replace(/&[a-z]+;/g, ' ').trim();
}

// ── Render ──────────────────────────────────────────────────────
function renderDoc(col) {
    document.getElementById('upload-area').style.display = 'none';

    const toolbar = document.getElementById('doc-toolbar');
    toolbar.style.display = 'flex';
    document.getElementById('toolbar-name').textContent = col.name;
    document.getElementById('toolbar-count').textContent = col.endpoints.length + ' endpoint' + (col.endpoints.length !== 1 ? 's' : '');

    const wrap = document.getElementById('doc-preview-wrap');
    wrap.style.display = 'block';

    const content = document.getElementById('doc-content');
    content.innerHTML = buildHtml(col);
}

function buildHtml(col) {
    const now = new Date().toLocaleDateString('pt-BR', { day:'2-digit', month:'long', year:'numeric' });
    let html = `
    <div class="doc-cover">
        <div class="doc-cover-badge"><i class="bi bi-braces"></i> API Documentation</div>
        <h1 class="doc-cover-title">${esc(col.name)}</h1>
        <p class="doc-cover-subtitle">${col.endpoints.length} endpoint${col.endpoints.length !== 1 ? 's' : ''} &nbsp;·&nbsp; Gerado em ${now}</p>
        ${col.description ? `<p style="font-size:13px;color:#64748b;margin-top:1rem;max-width:600px;margin-left:auto;margin-right:auto">${esc(col.description)}</p>` : ''}
    </div>`;

    let lastFolder = null;

    for (const ep of col.endpoints) {
        if (ep.folder !== lastFolder) {
            lastFolder = ep.folder;
            if (ep.folder) {
                html += `<div class="doc-folder"><i class="bi bi-folder2-open" style="margin-right:.4rem"></i>${esc(ep.folder)}</div>`;
            }
        }
        html += buildEndpoint(ep);
    }

    return html;
}

function buildEndpoint(ep) {
    const methodClass = 'method-' + (ep.method.toUpperCase());
    let html = `<div class="doc-endpoint">`;

    // ── Header ──
    html += `<div class="doc-endpoint-header">
        <span class="method-badge ${methodClass}">${esc(ep.method)}</span>
        <span class="doc-url" contenteditable="true" spellcheck="false">${esc(ep.url)}</span>
        <span class="doc-endpoint-name">${esc(ep.name)}</span>
    </div>`;

    // ── Description (sempre visível, editável) ──
    html += `<div class="doc-desc" contenteditable="true" data-placeholder="Clique para adicionar uma descrição...">${esc(ep.description)}</div>`;

    // ── Params (query + body merged) ──
    if (ep.allParams.length > 0) {
        html += `<div class="doc-section">
            <div class="doc-section-title">Parâmetros</div>
            <table class="doc-table">
                <thead>
                    <tr>
                        <th style="width:30%">Campo</th>
                        <th style="width:35%">Valor</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody>`;
        for (const p of ep.allParams) {
            const srcLabel = p.source === 'query' ? 'query' : (p.source === 'form' ? 'form' : 'body');
            html += `<tr>
                <td><span class="param-key">${esc(p.key)}</span><span class="param-source ${p.source}">${srcLabel}</span></td>
                <td><code style="font-size:11.5px;font-family:'JetBrains Mono',monospace;color:#475569">${esc(p.value)}</code></td>
                <td class="doc-td-editable" contenteditable="true" data-placeholder="—" style="color:#64748b">${esc(p.description)}</td>
            </tr>`;
        }
        html += `</tbody></table></div>`;
    } else if (!ep.responses.length) {
        html += `<div class="doc-section"><p class="doc-empty">Sem parâmetros</p></div>`;
    }

    // ── Raw body (se houver e não foi flattenado) ──
    // (já está representado nos params acima via flattenObj)

    // ── Responses ──
    if (ep.responses.length > 0) {
        html += `<div class="doc-section"><div class="doc-section-title">Respostas</div>`;
        for (const r of ep.responses) {
            const statusClass = statusColor(r.status);
            html += `<div class="doc-response-item">
                <div class="doc-response-header">
                    <span class="doc-status-badge ${statusClass}">${esc(String(r.status))}</span>
                    <span style="font-size:12.5px;color:#475569;font-weight:500">${esc(r.name)}</span>
                </div>`;
            if (r.body) {
                html += `<pre class="doc-code">${esc(r.body)}</pre>`;
            } else {
                html += `<p class="doc-empty" style="font-size:12px">Sem corpo de resposta</p>`;
            }
            html += `</div>`;
        }
        html += `</div>`;
    }

    html += `</div>`;
    return html;
}

function statusColor(code) {
    const n = parseInt(code);
    if (n >= 200 && n < 300) return 'status-2xx';
    if (n >= 300 && n < 400) return 'status-3xx';
    if (n >= 400 && n < 500) return 'status-4xx';
    if (n >= 500) return 'status-5xx';
    return 'status-xxx';
}

function esc(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// ── PDF generation ──────────────────────────────────────────────
document.getElementById('btn-pdf').addEventListener('click', async () => {
    const btn = document.getElementById('btn-pdf');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat lp-spin"></i> Gerando...';

    const el = document.getElementById('doc-content');
    const filename = (currentCollection?.name || 'api-documentation')
        .replace(/[^a-z0-9\-_ ]/gi, '')
        .trim()
        .replace(/\s+/g, '-')
        .toLowerCase() + '.pdf';

    const opt = {
        margin:      [14, 12, 14, 12],
        filename,
        image:       { type: 'jpeg', quality: 0.95 },
        html2canvas: { scale: 1.5, useCORS: true, backgroundColor: '#f8f9fa', logging: false },
        jsPDF:       { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak:   { mode: 'css', avoid: '.doc-endpoint-header, .doc-section-title, .doc-response-header, .doc-folder' },
    };

    await html2pdf().set(opt).from(el).save();

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-file-earmark-pdf"></i> Gerar PDF';
});

// ── Reset ───────────────────────────────────────────────────────
document.getElementById('btn-reset').addEventListener('click', () => {
    currentCollection = null;
    document.getElementById('upload-area').style.display = 'flex';
    document.getElementById('doc-toolbar').style.display = 'none';
    document.getElementById('doc-preview-wrap').style.display = 'none';
    document.getElementById('doc-content').innerHTML = '';
    document.getElementById('file-input').value = '';
});
</script>
@endpush
