<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XML / HTML Formatter — TechIA Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root{--bg:#0d1117;--surface:#161b22;--surface2:#1c2128;--border:#30363d;--border2:#21262d;--text:#e6edf3;--muted:#8b949e;--subtle:#6e7681;--blue:#58a6ff;--blue-dim:#1f6feb;--green:#3fb950;--red:#f85149;--orange:#d29922;--yellow:#e3b341;--purple:#bc8cff;--cyan:#39c5cf}
        *{box-sizing:border-box;margin:0;padding:0}
        html,body{height:100%;overflow:hidden;background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;font-size:13px}
        .topbar{height:46px;background:var(--surface);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 16px;gap:12px;flex-shrink:0}
        .brand{display:flex;align-items:center;gap:7px;text-decoration:none;font-weight:700;font-size:15px;color:var(--text)}
        .brand-icon{width:26px;height:26px;background:linear-gradient(135deg,var(--blue-dim),var(--blue));border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:13px}
        .brand span{color:var(--blue)}
        .topbar-sep{width:1px;height:18px;background:var(--border)}
        .topbar-title{font-size:13px;font-weight:600;color:var(--muted);display:flex;align-items:center;gap:6px}
        .topbar-title i{color:var(--blue)}
        .ms-auto{margin-left:auto}
        #mode-badge{padding:3px 10px;border-radius:12px;font-size:11.5px;font-weight:600;letter-spacing:.3px;display:none}
        #mode-badge.xml{background:rgba(88,166,255,.12);border:1px solid rgba(88,166,255,.3);color:var(--blue)}
        #mode-badge.html{background:rgba(210,153,34,.12);border:1px solid rgba(210,153,34,.3);color:var(--orange)}
        #mode-badge.soap{background:rgba(188,140,255,.12);border:1px solid rgba(188,140,255,.3);color:var(--purple)}
        .btn{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:6px;font-size:12.5px;font-weight:500;cursor:pointer;border:1px solid var(--border);transition:background .12s,color .12s;font-family:inherit;white-space:nowrap}
        .btn-primary{background:var(--blue-dim);border-color:var(--blue-dim);color:#fff}.btn-primary:hover{background:#2176d2}
        .btn-ghost{background:transparent;color:var(--muted)}.btn-ghost:hover{background:rgba(255,255,255,.07);color:var(--text)}
        #toolbar{height:44px;background:var(--surface2);border-bottom:1px solid var(--border2);display:flex;align-items:center;padding:0 14px;gap:8px;flex-shrink:0}
        .indent-select{background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:5px;padding:4px 8px;font-size:12px;font-family:inherit;outline:none;cursor:pointer}.indent-select:focus{border-color:var(--blue)}
        .toolbar-sep{width:1px;height:20px;background:var(--border2);margin:0 2px}
        #error-badge{display:none;align-items:center;gap:5px;padding:3px 10px;background:rgba(248,81,73,.12);border:1px solid rgba(248,81,73,.3);border-radius:5px;color:var(--red);font-size:11.5px;max-width:500px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-family:'JetBrains Mono',monospace}
        #valid-badge{display:none;align-items:center;gap:5px;padding:3px 10px;background:rgba(63,185,80,.1);border:1px solid rgba(63,185,80,.25);border-radius:5px;color:var(--green);font-size:11.5px}
        #stats-badge{font-size:11.5px;color:var(--subtle);display:none}
        .toggle-wrap{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);cursor:pointer;user-select:none}
        .toggle{width:32px;height:17px;background:var(--border);border-radius:999px;position:relative;transition:background .15s}.toggle.on{background:var(--blue-dim)}
        .toggle::after{content:'';position:absolute;width:13px;height:13px;background:#fff;border-radius:50%;top:2px;left:2px;transition:left .15s}.toggle.on::after{left:17px}
        #app{display:flex;flex-direction:column;height:calc(100vh - 46px)}
        #panels{display:flex;flex:1;overflow:hidden}
        .panel{display:flex;flex-direction:column;flex:1;min-width:0;overflow:hidden}
        .panel-hdr{height:34px;background:var(--surface);border-bottom:1px solid var(--border2);display:flex;align-items:center;padding:0 12px;gap:8px;flex-shrink:0;font-size:11.5px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
        .panel-hdr .actions{margin-left:auto;display:flex;gap:4px}
        #resize-bar{width:4px;background:var(--border2);cursor:col-resize;flex-shrink:0;transition:background .12s}
        #resize-bar:hover,#resize-bar.dragging{background:var(--blue-dim)}
        #input-area{width:100%;height:100%;resize:none;background:var(--bg);color:var(--text);border:none;outline:none;padding:14px 16px;font-family:'JetBrains Mono',monospace;font-size:13px;line-height:1.6;tab-size:2}
        #input-area::placeholder{color:var(--subtle)}
        #output-wrap{flex:1;overflow:auto;padding:14px 16px}
        #output-wrap::-webkit-scrollbar{width:5px;height:5px}
        #output-wrap::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
        #output-pre{font-family:'JetBrains Mono',monospace;font-size:12.5px;line-height:1.7;white-space:pre;margin:0}
        /* Syntax colours */
        .xt{color:var(--blue)}.xa{color:var(--cyan)}.xv{color:var(--orange)}
        .xc{color:var(--subtle);font-style:italic}.xp{color:var(--muted)}
        .xs{color:var(--green)}.xpi{color:var(--purple)}.xcd{color:var(--yellow)}
        #empty-state{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:var(--subtle);text-align:center;gap:10px;padding:40px}
        #empty-state i{font-size:36px;color:var(--border)}
        #toast{position:fixed;bottom:20px;right:20px;padding:8px 16px;background:var(--surface);border:1px solid var(--border);border-radius:6px;font-size:12.5px;display:flex;align-items:center;gap:7px;opacity:0;transition:opacity .2s;pointer-events:none;z-index:999}
        #toast.show{opacity:1}#toast.ok{border-color:rgba(63,185,80,.4);color:var(--green)}#toast.err{border-color:rgba(248,81,73,.4);color:var(--red)}
    </style>
</head>
<body>

<header class="topbar">
    <a href="{{ route('connections.index') }}" class="brand">
        <div class="brand-icon"><i class="bi bi-terminal-fill" style="color:#fff"></i></div>
        Leo<span>Panel</span>
    </a>
    <div class="topbar-sep"></div>
    <div class="topbar-title"><i class="bi bi-code-slash"></i> XML / HTML Formatter</div>
    <span id="mode-badge"></span>
    <div class="ms-auto" style="display:flex;align-items:center;gap:6px">
        <button class="btn btn-ghost" onclick="loadSampleSOAP()"><i class="bi bi-lightbulb"></i> SOAP</button>
        <button class="btn btn-ghost" onclick="loadSampleHTML()"><i class="bi bi-lightbulb"></i> HTML</button>
    </div>
</header>

<div id="app">
    <div id="toolbar">
        <button class="btn btn-primary" onclick="doFormat()" title="Ctrl+Enter"><i class="bi bi-magic"></i> Formatar</button>
        <button class="btn btn-ghost" onclick="doMinify()"><i class="bi bi-dash-circle"></i> Minificar</button>
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
        <label class="toggle-wrap" onclick="toggleExpand()">
            <div class="toggle on" id="expandToggle"></div>
            Expandir inner XML
        </label>
        <div class="toolbar-sep"></div>
        <span id="error-badge"><i class="bi bi-x-circle-fill"></i> <span id="error-msg"></span></span>
        <span id="valid-badge"><i class="bi bi-check-circle-fill"></i> <span id="valid-msg">Válido</span></span>
        <span id="stats-badge"></span>
    </div>

    <div id="panels">
        <div class="panel" id="panel-left" style="flex:0 0 50%">
            <div class="panel-hdr">
                <i class="bi bi-pencil" style="color:var(--blue)"></i> Entrada
                <div class="actions">
                    <button class="btn btn-ghost" style="padding:2px 8px;font-size:11.5px" onclick="clearAll()"><i class="bi bi-trash3"></i></button>
                    <button class="btn btn-ghost" style="padding:2px 8px;font-size:11.5px" onclick="pasteClip()"><i class="bi bi-clipboard"></i> Colar</button>
                </div>
            </div>
            <textarea id="input-area" spellcheck="false" placeholder="Cole seu XML, HTML ou SOAP aqui…&#10;&#10;Suporte a SOAP com inner XML entity-encoded (&lt;Result&gt;&lt;?xml...&gt;&lt;/Result&gt;)"></textarea>
        </div>

        <div id="resize-bar"></div>

        <div class="panel" id="panel-right">
            <div class="panel-hdr">
                <i class="bi bi-eye" style="color:var(--green)"></i> Resultado
                <div class="actions">
                    <button class="btn btn-ghost" style="padding:2px 8px;font-size:11.5px" id="copy-btn" onclick="doCopy()"><i class="bi bi-copy"></i> Copiar</button>
                    <button class="btn btn-ghost" style="padding:2px 8px;font-size:11.5px" onclick="doDownload()"><i class="bi bi-download"></i></button>
                </div>
            </div>
            <div id="output-wrap">
                <div id="empty-state">
                    <i class="bi bi-code-slash"></i>
                    <p>O XML / HTML formatado aparecerá aqui</p>
                </div>
                <pre id="output-pre" style="display:none"></pre>
            </div>
        </div>
    </div>
</div>

<div id="toast"></div>

<script>
'use strict';
// ── State (cleanOutput = pure text, never contains HTML tags) ─────────────────
let cleanOutput  = '';
let expandInner  = true;
let detectedMode = 'xml';

// ── Escape helpers (must be defined first) ────────────────────────────────────
function esc(s)     { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escAttr(s) { return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function indentStr() {
    const v = document.getElementById('indentSize').value;
    return v === 'tab' ? '\t' : ' '.repeat(+v);
}

function toggleExpand() {
    expandInner = !expandInner;
    document.getElementById('expandToggle').classList.toggle('on', expandInner);
    if (cleanOutput) doFormat();
}

// ── Mode ──────────────────────────────────────────────────────────────────────
function detectMode(raw) {
    const t = raw.trim().toLowerCase();
    if (t.includes('soap-env:') || t.includes(':envelope') || t.includes('soap:envelope')) return 'soap';
    if (t.startsWith('<!doctype html') || t.startsWith('<html')) return 'html';
    return 'xml';
}
function showModeBadge(mode) {
    const el = document.getElementById('mode-badge');
    el.textContent = {xml:'XML',html:'HTML',soap:'SOAP/XML'}[mode] || mode.toUpperCase();
    el.className = mode === 'soap' ? 'soap' : mode;
    el.style.display = 'inline-block';
}

// ── Entry point ───────────────────────────────────────────────────────────────
function doFormat() {
    const raw = document.getElementById('input-area').value.trim();
    if (!raw) { setError('Nenhum conteúdo para formatar.'); return; }
    detectedMode = detectMode(raw);
    showModeBadge(detectedMode);
    try {
        cleanOutput = (detectedMode === 'html') ? fmtHTML(raw) : fmtXML(raw);
        setValid(detectedMode);
        renderOutput();
    } catch(e) {
        cleanOutput = '';
        setError(String(e.message || e).replace(/\s+/g,' ').slice(0,280));
        document.getElementById('output-pre').style.display  = 'none';
        document.getElementById('empty-state').style.display = 'flex';
        document.getElementById('stats-badge').style.display = 'none';
    }
}

function doMinify() {
    const raw = document.getElementById('input-area').value.trim();
    if (!raw) return;
    try {
        const mode = detectMode(raw);
        let out;
        if (mode === 'html') {
            out = raw.replace(/>\s+</g,'><').replace(/\s{2,}/g,' ').trim();
        } else {
            const doc = new DOMParser().parseFromString(raw, 'text/xml');
            if (doc.querySelector('parsererror')) throw new Error('XML inválido');
            out = new XMLSerializer().serializeToString(doc).replace(/>\s+</g,'><');
        }
        document.getElementById('input-area').value = out;
        cleanOutput = '';
        document.getElementById('output-pre').style.display  = 'none';
        document.getElementById('empty-state').style.display = 'flex';
        hideStatus();
        toast('ok','Minificado.');
    } catch(e) { setError(String(e.message||e)); }
}

// ═══════════════════════════════════════════════════════════════════════════════
// XML FORMATTER
// ═══════════════════════════════════════════════════════════════════════════════

function fmtXML(raw) {
    const doc = new DOMParser().parseFromString(raw, 'text/xml');
    const err = doc.querySelector('parsererror');
    if (err) {
        throw new Error(err.textContent.replace(/\s+/g,' ').trim().slice(0,220));
    }
    const IND = indentStr();
    let out = '';
    // Restore XML declaration (DOMParser does not keep it in the DOM)
    const declMatch = raw.match(/^\s*(<\?xml[^?]*\?>)/i);
    if (declMatch) out = declMatch[1] + '\n';

    for (const child of doc.childNodes) {
        // Skip the xml PI since we already added it from the raw source
        if (child.nodeType === Node.PROCESSING_INSTRUCTION_NODE && child.target.toLowerCase() === 'xml') continue;
        out += xmlNode(child, 0, IND);
    }
    return out.replace(/\n+$/,'');
}

function xmlNode(node, depth, IND) {
    switch (node.nodeType) {
        case Node.ELEMENT_NODE:                return xmlEl(node, depth, IND);
        case Node.TEXT_NODE: {
            const t = node.nodeValue.trim();
            if (!t) return '';
            if (expandInner) {
                const exp = tryInnerXML(t, depth, IND);
                if (exp !== null) return exp;
            }
            return '\n' + IND.repeat(depth) + t;
        }
        case Node.COMMENT_NODE:
            return '\n' + IND.repeat(depth) + '<!-- ' + node.data + ' -->';
        case Node.CDATA_SECTION_NODE:
            return '\n' + IND.repeat(depth) + '<![CDATA[' + node.data + ']]>';
        case Node.PROCESSING_INSTRUCTION_NODE:
            return '\n' + IND.repeat(depth) + '<?' + node.target + (node.data ? ' '+node.data : '') + '?>';
        default: return '';
    }
}

function xmlEl(el, depth, IND) {
    const pad   = IND.repeat(depth);
    const tag   = el.nodeName;          // qualified name including prefix (e.g. SOAP-ENV:Body)
    const attrs = xmlAttrs(el);

    if (!el.hasChildNodes()) return '\n' + pad + '<' + tag + attrs + '/>';

    const kids = [...el.childNodes];

    // ── Single text-only child ────────────────────────────────────────────────
    if (kids.length === 1 && kids[0].nodeType === Node.TEXT_NODE) {
        // nodeValue is ALREADY entity-decoded by DOMParser
        const raw   = kids[0].nodeValue;
        const trimm = raw.trim();

        if (!trimm) return '\n' + pad + '<' + tag + attrs + '></' + tag + '>';

        // Try to detect and expand entity-encoded inner XML
        if (expandInner) {
            const exp = tryInnerXML(trimm, depth + 1, IND);
            if (exp !== null) {
                return '\n' + pad + '<' + tag + attrs + '>' + exp + '\n' + pad + '</' + tag + '>';
            }
        }

        // Short value inline, long value indented
        if (trimm.length <= 120 && !trimm.includes('\n')) {
            return '\n' + pad + '<' + tag + attrs + '>' + trimm + '</' + tag + '>';
        }
        return '\n' + pad + '<' + tag + attrs + '>\n' + pad + IND + trimm + '\n' + pad + '</' + tag + '>';
    }

    // ── Multiple / mixed children ─────────────────────────────────────────────
    let inner = '';
    for (const child of kids) inner += xmlNode(child, depth + 1, IND);
    return '\n' + pad + '<' + tag + attrs + '>' + inner + '\n' + pad + '</' + tag + '>';
}

function xmlAttrs(el) {
    let s = '';
    for (const a of el.attributes) {
        s += ' ' + a.nodeName + '="' + escAttr(a.value) + '"';
    }
    return s;
}

// Detect whether text (already entity-decoded) looks like XML
function looksLikeXML(txt) {
    const t = txt.trimStart();
    if (t.startsWith('<?xml')) return true;                          // XML declaration
    if (/^<[a-zA-Z_:]/.test(t) && t.includes('</')) return true;   // element with closing tag
    return false;
}

// Try to parse txt as XML and return formatted string; null on any failure
function tryInnerXML(txt, depth, IND) {
    if (!looksLikeXML(txt)) return null;
    try {
        const inner = new DOMParser().parseFromString(txt, 'text/xml');
        if (inner.querySelector('parsererror')) return null;

        let out = '';
        // Re-attach declaration if present in original text
        const decl = txt.match(/^\s*(<\?xml[^?]*\?>)/i);
        if (decl) out += '\n' + IND.repeat(depth) + decl[1];

        for (const child of inner.childNodes) {
            if (child.nodeType === Node.PROCESSING_INSTRUCTION_NODE && child.target.toLowerCase() === 'xml') continue;
            out += xmlNode(child, depth, IND);
        }
        return out || null;
    } catch { return null; }
}

// ═══════════════════════════════════════════════════════════════════════════════
// HTML FORMATTER
// ═══════════════════════════════════════════════════════════════════════════════

const VOID_TAGS   = new Set(['area','base','br','col','embed','hr','img','input','link','meta','param','source','track','wbr']);
const INLINE_TAGS = new Set(['a','abbr','b','bdo','br','cite','code','dfn','em','i','img','input','kbd','label','q','samp','select','small','span','strong','sub','sup','textarea','time','tt','var']);

function fmtHTML(raw) {
    const doc  = new DOMParser().parseFromString(raw, 'text/html');
    const IND  = indentStr();
    const full = /^\s*<!doctype/i.test(raw) || /^\s*<html/i.test(raw);
    if (full) return '<!DOCTYPE html>\n' + htmlNode(doc.documentElement, 0, IND).trimStart();
    let out = '';
    for (const c of doc.body.childNodes) out += htmlNode(c, 0, IND);
    return out.trim();
}

function htmlNode(node, depth, IND) {
    if (node.nodeType === Node.TEXT_NODE) {
        const t = node.textContent.trim();
        return t ? '\n' + IND.repeat(depth) + t : '';
    }
    if (node.nodeType === Node.COMMENT_NODE) {
        return '\n' + IND.repeat(depth) + '<!-- ' + node.data.trim() + ' -->';
    }
    if (node.nodeType !== Node.ELEMENT_NODE) return '';

    const pad  = IND.repeat(depth);
    const tag  = node.tagName.toLowerCase();
    let   attrs = '';
    for (const a of node.attributes) {
        attrs += a.value === '' ? ' ' + a.name : ' ' + a.name + '="' + escAttr(a.value) + '"';
    }

    if (VOID_TAGS.has(tag)) return '\n' + pad + '<' + tag + attrs + '>';
    if (!node.hasChildNodes()) return '\n' + pad + '<' + tag + attrs + '></' + tag + '>';

    if (INLINE_TAGS.has(tag)) {
        let inner = '';
        for (const c of node.childNodes) {
            if (c.nodeType === Node.TEXT_NODE) inner += c.textContent.replace(/\s+/g,' ');
            else inner += htmlNode(c, 0, IND).trimStart();
        }
        return '\n' + pad + '<' + tag + attrs + '>' + inner + '</' + tag + '>';
    }

    let inner = '';
    for (const c of node.childNodes) inner += htmlNode(c, depth + 1, IND);
    return '\n' + pad + '<' + tag + attrs + '>' + inner + '\n' + pad + '</' + tag + '>';
}

// ═══════════════════════════════════════════════════════════════════════════════
// SYNTAX HIGHLIGHT
// Takes cleanOutput (pure text, no embedded HTML) and produces innerHTML string.
// ═══════════════════════════════════════════════════════════════════════════════

function highlight(str) {
    return str.split('\n').map(hlLine).join('\n');
}

function hlLine(line) {
    const t = line.trimStart();
    const indent = line.slice(0, line.length - t.length);

    if (!t) return '';

    // Comment
    if (t.startsWith('<!--')) {
        return esc(indent) + '<span class="xc">' + esc(t) + '</span>';
    }
    // CDATA
    if (t.startsWith('<![CDATA[')) {
        const m = t.match(/^(<!\[CDATA\[)([\s\S]*?)(\]\]>)([\s\S]*)$/);
        if (m) return esc(indent)+'<span class="xp">'+esc(m[1])+'</span><span class="xcd">'+esc(m[2])+'</span><span class="xp">'+esc(m[3])+'</span>'+esc(m[4]);
        return esc(line);
    }
    // Processing instruction
    if (t.startsWith('<?')) {
        const m = t.match(/^(<\?)([^\s?>]+)([\s\S]*?)(\?>)([\s\S]*)$/);
        if (m) return esc(indent)+'<span class="xp">'+esc(m[1])+'</span><span class="xpi">'+esc(m[2])+'</span>'+hlAttrs(m[3])+'<span class="xp">'+esc(m[4])+'</span>'+esc(m[5]);
        return esc(line);
    }
    // Closing tag
    if (t.startsWith('</')) {
        const m = t.match(/^(<\/)([^\s>]+)(>)([\s\S]*)$/);
        if (m) return esc(indent)+'<span class="xp">'+esc(m[1])+'</span><span class="xt">'+esc(m[2])+'</span><span class="xp">'+esc(m[3])+'</span>'+esc(m[4]);
        return esc(line);
    }
    // Opening / self-closing tag
    if (t.startsWith('<') && /^<[a-zA-Z_!:]/.test(t)) {
        // Split: <tagname  attributes  /> or >  afterText
        const m = t.match(/^(<)([^\s>/]+)([\s\S]*?)(\/?>)([\s\S]*)$/);
        if (m) {
            const afterHtml = m[5].trim() ? '<span class="xs">'+esc(m[5])+'</span>' : esc(m[5]);
            return esc(indent)+'<span class="xp">'+esc(m[1])+'</span><span class="xt">'+esc(m[2])+'</span>'+hlAttrs(m[3])+'<span class="xp">'+esc(m[4])+'</span>'+afterHtml;
        }
        return esc(line);
    }
    // Pure text line (element content)
    return esc(indent) + '<span class="xs">' + esc(t) + '</span>';
}

function hlAttrs(str) {
    if (!str.trim()) return esc(str);
    // Highlight attr="value" pairs; remaining text is escaped normally
    let result = '';
    let last   = 0;
    const re   = /(\s+)([\w:\-\.]+)(=)("(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*')/g;
    let m;
    while ((m = re.exec(str)) !== null) {
        result += esc(str.slice(last, m.index));
        result += esc(m[1])
               + '<span class="xa">'+esc(m[2])+'</span>'
               + '<span class="xp">'+esc(m[3])+'</span>'
               + '<span class="xv">'+esc(m[4])+'</span>';
        last = m.index + m[0].length;
    }
    result += esc(str.slice(last));
    return result;
}

// ── Render ────────────────────────────────────────────────────────────────────
function renderOutput() {
    const pre   = document.getElementById('output-pre');
    const empty = document.getElementById('empty-state');
    pre.innerHTML        = highlight(cleanOutput);
    pre.style.display    = 'block';
    empty.style.display  = 'none';
    const bytes = new TextEncoder().encode(cleanOutput).length;
    const lines = cleanOutput.split('\n').length;
    const sb = document.getElementById('stats-badge');
    sb.style.display = 'inline';
    sb.textContent   = lines + ' linhas · ' + (bytes >= 1024 ? (bytes/1024).toFixed(1)+' KB' : bytes+' B');
}

// ── Status ─────────────────────────────────────────────────────────────────────
function setError(msg) {
    document.getElementById('error-msg').textContent     = msg;
    document.getElementById('error-badge').style.display = 'inline-flex';
    document.getElementById('valid-badge').style.display = 'none';
    document.getElementById('stats-badge').style.display = 'none';
}
function setValid(mode) {
    const labels = {xml:'XML válido',html:'HTML válido',soap:'SOAP/XML válido'};
    document.getElementById('valid-msg').textContent     = labels[mode] || 'Válido';
    document.getElementById('error-badge').style.display = 'none';
    document.getElementById('valid-badge').style.display = 'inline-flex';
}
function hideStatus() {
    ['error-badge','valid-badge','stats-badge'].forEach(id => document.getElementById(id).style.display='none');
    document.getElementById('mode-badge').style.display = 'none';
}

// ── Actions ───────────────────────────────────────────────────────────────────
function clearAll() {
    document.getElementById('input-area').value = '';
    cleanOutput = '';
    document.getElementById('output-pre').style.display  = 'none';
    document.getElementById('empty-state').style.display = 'flex';
    hideStatus();
}
async function pasteClip() {
    try {
        const t = await navigator.clipboard.readText();
        document.getElementById('input-area').value = t;
        doFormat();
    } catch { toast('err','Sem permissão para acessar a área de transferência.'); }
}
function doCopy() {
    if (!cleanOutput) return;
    navigator.clipboard.writeText(cleanOutput).then(() => {
        toast('ok','Copiado!');
        const btn = document.getElementById('copy-btn');
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Copiado';
        setTimeout(() => { btn.innerHTML = '<i class="bi bi-copy"></i> Copiar'; }, 1800);
    }).catch(() => toast('err','Erro ao copiar.'));
}
function doDownload() {
    if (!cleanOutput) return;
    const ext  = detectedMode === 'html' ? 'html' : 'xml';
    const mime = detectedMode === 'html' ? 'text/html' : 'application/xml';
    const blob = new Blob([cleanOutput], {type: mime});
    const a    = Object.assign(document.createElement('a'), {href: URL.createObjectURL(blob), download:'formatted.'+ext});
    a.click(); URL.revokeObjectURL(a.href);
}

// ── Samples ───────────────────────────────────────────────────────────────────
function loadSampleSOAP() {
    // The &lt; / &gt; inside the JS string ARE literal characters, so textarea.value
    // will contain the raw entity-encoded SOAP — exactly as received from a web service.
    document.getElementById('input-area').value =
        '<?xml version="1.0" encoding="utf-8"?>' +
        '<SOAP-ENV:Envelope' +
        ' xmlns:xsd="http://www.w3.org/2001/XMLSchema"' +
        ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' +
        ' xmlns:HNS="http://tempuri.org/"' +
        ' xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"' +
        ' xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">' +
        '<SOAP-ENV:Header>' +
        '<HNS:ROClientID SOAP-ENV:mustUnderstand="0">{B5197473-0A47-47A5-BD68-FC5E5D02DD1C}</HNS:ROClientID>' +
        '</SOAP-ENV:Header>' +
        '<SOAP-ENV:Body SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:ro="http://tempuri.org/">' +
        '<NS1:listaAcordoResponse xmlns:NS1="urn:DWLibrary-CSLogService">' +
        '<Result xsi:type="xsd:string">' +
        '&lt;?xml version="1.0" encoding="utf-8" standalone="yes"?&gt;' +
        '&lt;XML ambiente="PROD" versao="325680"&gt;' +
        '&lt;Retorno&gt;&lt;Status&gt;OK&lt;/Status&gt;&lt;/Retorno&gt;' +
        '&lt;Acordos&gt;' +
        '&lt;Acordo&gt;&lt;IdAcordo&gt;3842525&lt;/IdAcordo&gt;&lt;Data&gt;2026-03-17&lt;/Data&gt;&lt;Funcionario&gt;9999&lt;/Funcionario&gt;&lt;Status&gt;Liquidado&lt;/Status&gt;&lt;TotalAcordo&gt;1.336,36&lt;/TotalAcordo&gt;&lt;Parcelas&gt;&lt;Parcela&gt;&lt;NumParc&gt;1&lt;/NumParc&gt;&lt;Vencimento&gt;2026-03-16&lt;/Vencimento&gt;&lt;Total&gt;1.336,36&lt;/Total&gt;&lt;Status&gt;Paga&lt;/Status&gt;&lt;/Parcela&gt;&lt;/Parcelas&gt;&lt;/Acordo&gt;' +
        '&lt;Acordo&gt;&lt;IdAcordo&gt;4043405&lt;/IdAcordo&gt;&lt;Data&gt;2026-06-30&lt;/Data&gt;&lt;Funcionario&gt;tech.ia&lt;/Funcionario&gt;&lt;Status&gt;Válido&lt;/Status&gt;&lt;TotalAcordo&gt;2.956,34&lt;/TotalAcordo&gt;&lt;Parcelas&gt;&lt;Parcela&gt;&lt;NumParc&gt;1&lt;/NumParc&gt;&lt;Vencimento&gt;2026-07-01&lt;/Vencimento&gt;&lt;Total&gt;2.956,34&lt;/Total&gt;&lt;Status&gt;Em aberto&lt;/Status&gt;&lt;/Parcela&gt;&lt;/Parcelas&gt;&lt;/Acordo&gt;' +
        '&lt;/Acordos&gt;&lt;/XML&gt;' +
        '</Result>' +
        '</NS1:listaAcordoResponse>' +
        '</SOAP-ENV:Body>' +
        '</SOAP-ENV:Envelope>';
    doFormat();
}
function loadSampleHTML() {
    document.getElementById('input-area').value =
        '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Exemplo</title></head>' +
        '<body><header><nav><ul><li><a href="/">Início</a></li><li><a href="/sobre">Sobre</a></li></ul></nav></header>' +
        '<main><section id="hero"><h1>Olá, Mundo!</h1><p>Formatado pelo <strong>TechIA Panel</strong>.</p></section></main>' +
        '<footer><p>© 2026 TechIA Panel</p></footer></body></html>';
    doFormat();
}

// ── Toast ─────────────────────────────────────────────────────────────────────
function toast(type, msg) {
    const el = document.getElementById('toast');
    el.className = 'show ' + type;
    el.innerHTML = '<i class="bi '+(type==='ok'?'bi-check-circle-fill':'bi-x-circle-fill')+'"></i> ' + msg;
    clearTimeout(el._t);
    el._t = setTimeout(() => { el.className = ''; }, 2400);
}

// ── Resize ────────────────────────────────────────────────────────────────────
(function(){
    const bar  = document.getElementById('resize-bar');
    const left = document.getElementById('panel-left');
    let drag=false, sx=0, sw=0;
    bar.addEventListener('mousedown', e => { drag=true;sx=e.clientX;sw=left.offsetWidth;bar.classList.add('dragging');document.body.style.cssText='cursor:col-resize;user-select:none'; });
    document.addEventListener('mousemove', e => {
        if (!drag) return;
        const w = Math.max(200, Math.min(document.getElementById('panels').offsetWidth - 200, sw + e.clientX - sx));
        left.style.flex = '0 0 ' + w + 'px';
    });
    document.addEventListener('mouseup', () => { if(!drag)return; drag=false; bar.classList.remove('dragging'); document.body.style.cssText=''; });
})();

// ── Keyboard ──────────────────────────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if ((e.ctrlKey||e.metaKey) && e.key==='Enter') { e.preventDefault(); doFormat(); }
    if ((e.ctrlKey||e.metaKey) && e.key==='k')     { e.preventDefault(); clearAll(); }
});
document.getElementById('input-area').addEventListener('paste', () => setTimeout(doFormat, 30));
document.getElementById('indentSize').addEventListener('change', () => { if (cleanOutput) doFormat(); });
</script>
</body>
</html>
