<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminal — {{ $connection->name }} — TechIA Panel</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@xterm/xterm@5.5.0/css/xterm.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg: #0d1117; --surface: #161b22; --border: #30363d;
            --text: #e6edf3; --muted: #8b949e; --subtle: #6e7681;
            --green: #3fb950; --red: #f85149; --orange: #d29922; --blue: #58a6ff;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; overflow: hidden; background: var(--bg); font-family: 'Inter', sans-serif; }
        body { display: flex; flex-direction: column; }

        #bar {
            height: 42px; background: var(--surface); border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 10px; padding: 0 14px; flex-shrink: 0; min-width: 0;
        }
        #bar .name { font-size: 13px; font-weight: 600; color: var(--text); white-space: nowrap; }
        #bar .host { font-size: 11.5px; color: var(--muted); font-family: 'JetBrains Mono', monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #hint { font-size: 11px; color: var(--subtle); margin-left: auto; white-space: nowrap; }
        #hint kbd { font-family: 'JetBrains Mono', monospace; font-size: 10.5px; background: rgba(255,255,255,.06); border: 1px solid var(--border); border-radius: 3px; padding: 0 4px; color: var(--muted); }
        #size { font-size: 11px; color: var(--subtle); font-family: 'JetBrains Mono', monospace; white-space: nowrap; }
        #status { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; white-space: nowrap; }
        #status .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--muted); }
        #status.connecting .dot { background: var(--orange); animation: pulse 1.2s ease-in-out infinite; }
        #status.connected .dot { background: var(--green); }
        #status.error .dot, #status.closed .dot { background: var(--red); }
        #status.connecting { color: var(--orange); }
        #status.connected { color: var(--green); }
        #status.error, #status.closed { color: var(--red); }
        @keyframes pulse { 50% { opacity: .35; } }

        #btnReload {
            background: var(--blue); border: none; color: #0d1117; font-weight: 600;
            border-radius: 6px; padding: 5px 12px; font-size: 11.5px; cursor: pointer; display: none; white-space: nowrap;
        }
        #btnReload:hover { filter: brightness(1.1); }

        #term-wrap { flex: 1; min-height: 0; padding: 8px 4px 4px 10px; }
        #term { width: 100%; height: 100%; }
        .xterm .xterm-viewport::-webkit-scrollbar { width: 8px; }
        .xterm .xterm-viewport::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

        #toast {
            position: fixed; bottom: 16px; left: 50%; transform: translateX(-50%);
            background: var(--surface); border: 1px solid var(--border); color: var(--text);
            font-size: 12px; padding: 6px 12px; border-radius: 6px; opacity: 0; pointer-events: none; transition: opacity .2s;
        }
        #toast.show { opacity: 1; }

        @media (max-width: 760px) { #hint { display: none; } #status { margin-left: auto; } }
    </style>
</head>
<body>

<div id="bar">
    <i class="bi bi-terminal-fill" style="color:var(--blue)"></i>
    <span class="name">{{ $connection->name }}</span>
    <span class="host">{{ $connection->username }}&#64;{{ $connection->host }}:{{ $connection->port }}</span>
    <span id="hint"><kbd>Ctrl+Shift+C</kbd> copiar · <kbd>Ctrl+Shift+V</kbd> colar · selecionar + botão direito copia</span>
    <span id="size"></span>
    <button id="btnReload" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i> Reconectar</button>
    <span id="status" class="connecting"><span class="dot"></span><span id="status-label">Conectando…</span></span>
</div>

<div id="term-wrap"><div id="term"></div></div>
<div id="toast"></div>

{{-- xterm 5 + addons of the same major. (The old page mixed xterm 4 with a
     fit addon built for xterm 5, so fit() always threw and the terminal was
     stuck at 80x24 — wrong wrapping, broken nano/vim/top, resize did nothing.) --}}
<script src="https://cdn.jsdelivr.net/npm/@xterm/xterm@5.5.0/lib/xterm.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@xterm/addon-fit@0.10.0/lib/addon-fit.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@xterm/addon-web-links@0.11.0/lib/addon-web-links.js"></script>
<script>
const WS_HOST  = @json($wsHost);
const WS_PORT  = @json($wsPort);
const WS_TOKEN = @json($token);
// From the URL the browser is actually on, not from what the server thinks
// (behind an HTTPS proxy the server sees plain HTTP and would pick ws:// —
// which an https page is not allowed to open).
const WS_PROTOCOL = location.protocol === 'https:' ? 'wss' : 'ws';

const PING_EVERY_MS = 25000;   // keeps proxies/firewalls from dropping an idle tab
const DEAD_AFTER_MS = 70000;   // no reply at all for this long = connection is gone

const termEl  = document.getElementById('term');
const wrapEl  = document.getElementById('term-wrap');
const encoder = new TextEncoder();

const term = new Terminal({
    theme: {
        background: '#0d1117', foreground: '#e6edf3', cursor: '#58a6ff', cursorAccent: '#0d1117',
        selectionBackground: 'rgba(88,166,255,.35)',
        black: '#484f58', red: '#ff7b72', green: '#3fb950', yellow: '#d29922',
        blue: '#58a6ff', magenta: '#bc8cff', cyan: '#39c5cf', white: '#b1bac4',
        brightBlack: '#6e7681', brightRed: '#ffa198', brightGreen: '#56d364', brightYellow: '#e3b341',
        brightBlue: '#79c0ff', brightMagenta: '#d2a8ff', brightCyan: '#56d4dd', brightWhite: '#f0f6fc',
    },
    fontFamily: "'JetBrains Mono', Consolas, 'Courier New', monospace",
    fontSize: 14,
    lineHeight: 1.15,
    cursorBlink: true,
    scrollback: 10000,
    allowProposedApi: false,
});
const fitAddon = new FitAddon.FitAddon();
term.loadAddon(fitAddon);
term.loadAddon(new WebLinksAddon.WebLinksAddon());

let ws = null;
let state = 'connecting';        // connecting | connected | error | closed
let lastSeen = Date.now();
let pingTimer = null;
let lastSent = { cols: 0, rows: 0 };

// ── UI helpers ───────────────────────────────────────────────────────────
function setStatus(next, label) {
    state = next;
    const el = document.getElementById('status');
    el.className = next;
    document.getElementById('status-label').textContent = label;
    document.getElementById('btnReload').style.display = (next === 'error' || next === 'closed') ? '' : 'none';
    document.title = (next === 'connected' ? '' : '● ') + 'Terminal — ' + @json($connection->name);
}

let toastTimer = null;
function toast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 1600);
}

function notice(text, color = '90') {
    term.write(`\r\n\x1b[${color}m${text}\x1b[0m\r\n`);
}

// ── Sizing ───────────────────────────────────────────────────────────────
function fit() {
    try { fitAddon.fit(); } catch (e) { console.warn('fit() falhou', e); }
    document.getElementById('size').textContent = `${term.cols}×${term.rows}`;
}

function sendResizeIfChanged() {
    fit();
    if (term.cols === lastSent.cols && term.rows === lastSent.rows) return;
    if (send({ type: 'resize', cols: term.cols, rows: term.rows })) {
        lastSent = { cols: term.cols, rows: term.rows };
    }
}

// Container-based (covers window resize, zoom, devtools opening…), debounced.
let resizeTimer = null;
new ResizeObserver(() => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(sendResizeIfChanged, 80);
}).observe(wrapEl);

// ── Clipboard ────────────────────────────────────────────────────────────
// navigator.clipboard only exists on HTTPS/localhost; the panel is usually
// opened over plain HTTP by IP, so fall back to execCommand('copy').
function copyText(text) {
    if (!text) return;
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => toast('Copiado'), () => fallbackCopy(text));
    } else {
        fallbackCopy(text);
    }
}
function fallbackCopy(text) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;top:0;left:0;opacity:0';
    document.body.appendChild(ta);
    ta.select();
    let ok = false;
    try { ok = document.execCommand('copy'); } catch {}
    ta.remove();
    term.focus();
    toast(ok ? 'Copiado' : 'Não foi possível copiar');
}

term.attachCustomKeyEventHandler(e => {
    if (e.type !== 'keydown') return true;
    const ctrl = e.ctrlKey || e.metaKey;
    const key  = e.key.toLowerCase();

    // Copy: Ctrl+Shift+C, or Ctrl+C while there's a selection (otherwise Ctrl+C = interrupt).
    if (ctrl && key === 'c' && (e.shiftKey || term.hasSelection())) {
        copyText(term.getSelection());
        term.clearSelection();
        e.preventDefault();
        return false;
    }

    // Paste: let the browser fire its native paste event, which xterm handles
    // (works on plain HTTP too, unlike the async clipboard API).
    if (ctrl && key === 'v') return false;

    return true;
});

// Right-click with a selection copies it; without one, the browser menu
// (with "Colar") opens over the terminal as usual.
termEl.addEventListener('contextmenu', e => {
    if (term.hasSelection()) {
        e.preventDefault();
        copyText(term.getSelection());
        term.clearSelection();
    }
});

// ── Connection ───────────────────────────────────────────────────────────
function send(obj) {
    if (ws && ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify(obj));
        return true;
    }
    return false;
}

function bytesToBase64(bytes) {
    let bin = '';
    for (let i = 0; i < bytes.length; i += 0x8000) bin += String.fromCharCode.apply(null, bytes.subarray(i, i + 0x8000));
    return btoa(bin);
}

term.onData(data => {
    if (state === 'connected') send({ type: 'input', data: bytesToBase64(encoder.encode(data)) });
});

// Some mouse-reporting modes emit raw bytes rather than UTF-16 text.
term.onBinary(data => {
    if (state !== 'connected') return;
    const bytes = new Uint8Array(data.length);
    for (let i = 0; i < data.length; i++) bytes[i] = data.charCodeAt(i) & 0xff;
    send({ type: 'input', data: bytesToBase64(bytes) });
});

function connect() {
    setStatus('connecting', 'Conectando…');
    lastSent = { cols: term.cols, rows: term.rows };

    const url = `${WS_PROTOCOL}://${WS_HOST}:${WS_PORT}/?token=${encodeURIComponent(WS_TOKEN)}&cols=${term.cols}&rows=${term.rows}`;
    ws = new WebSocket(url);
    ws.binaryType = 'arraybuffer';

    ws.onopen = () => {
        lastSeen = Date.now();
        setStatus('connected', 'Conectado');
        // Size may have changed while connecting.
        sendResizeIfChanged();
        clearInterval(pingTimer);
        pingTimer = setInterval(() => {
            if (Date.now() - lastSeen > DEAD_AFTER_MS) {
                notice('[conexão perdida — sem resposta do servidor]', '31');
                setStatus('closed', 'Conexão perdida');
                ws.close();
                return;
            }
            send({ type: 'ping' });
        }, PING_EVERY_MS);
    };

    ws.onmessage = ev => {
        lastSeen = Date.now();

        // Terminal output: raw UTF-8 bytes — xterm decodes them, including
        // multi-byte characters split across messages.
        if (ev.data instanceof ArrayBuffer) {
            term.write(new Uint8Array(ev.data));
            return;
        }

        let msg;
        try { msg = JSON.parse(ev.data); } catch { return; }

        if (msg.type === 'error') {
            notice(msg.message, '31');
            setStatus('error', 'Erro');
        } else if (msg.type === 'closed') {
            notice('[sessão encerrada]');
            setStatus('closed', 'Encerrado');
        }
    };

    ws.onclose = () => {
        clearInterval(pingTimer);
        if (state === 'connected' || state === 'connecting') {
            notice('[conexão com o painel perdida]', '31');
            setStatus('closed', 'Desconectado');
        }
    };

    ws.onerror = () => {
        if (state === 'connecting') {
            notice(`Não foi possível conectar ao servidor de terminal (${WS_PROTOCOL}://${WS_HOST}:${WS_PORT}). Verifique se o serviço do terminal está rodando e se a porta ${WS_PORT} está liberada.`, '31');
            setStatus('error', 'Sem conexão');
        }
    };
}

// Closing the tab by accident (Ctrl+W is "delete word" in the shell, but the
// browser grabs it) would kill the session — ask first while it's live.
window.addEventListener('beforeunload', e => {
    if (state === 'connected') { e.preventDefault(); e.returnValue = ''; }
});

// ── Start ────────────────────────────────────────────────────────────────
// Measure the character cell with the real font: if the web font loads after
// the terminal opens, xterm keeps the fallback font's metrics and text
// overlaps / the cursor drifts. Don't wait forever for it, though.
(async () => {
    try {
        await Promise.race([
            document.fonts.load('14px "JetBrains Mono"').then(() => document.fonts.load('bold 14px "JetBrains Mono"')),
            new Promise(r => setTimeout(r, 2500)),
        ]);
    } catch {}

    term.open(termEl);
    fit();
    term.focus();
    connect();
})();
</script>
</body>
</html>
