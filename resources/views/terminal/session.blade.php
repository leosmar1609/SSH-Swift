<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminal — {{ $connection->name }} — TechIA Panel</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@4.19.0/css/xterm.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg: #0d1117; --surface: #161b22; --border: #30363d;
            --text: #e6edf3; --muted: #8b949e; --subtle: #6e7681;
            --green: #3fb950; --red: #f85149; --orange: #d29922; --blue: #58a6ff;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; overflow: hidden; background: var(--bg); font-family: 'Inter', sans-serif; }

        #bar {
            height: 42px; background: var(--surface); border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 10px; padding: 0 14px; flex-shrink: 0;
        }
        #bar .name { font-size: 13px; font-weight: 600; color: var(--text); }
        #bar .host { font-size: 11.5px; color: var(--muted); font-family: 'JetBrains Mono', monospace; }
        #status { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; margin-left: auto; }
        #status .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--muted); }
        #status.connecting .dot { background: var(--orange); animation: pulse 1.2s ease-in-out infinite; }
        #status.connected .dot { background: var(--green); }
        #status.error .dot, #status.closed .dot { background: var(--red); }
        #status.connecting { color: var(--orange); }
        #status.connected { color: var(--green); }
        #status.error, #status.closed { color: var(--red); }
        @keyframes pulse { 50% { opacity: .35; } }

        #btnReload {
            background: transparent; border: 1px solid var(--border); color: var(--muted);
            border-radius: 6px; padding: 4px 10px; font-size: 11.5px; cursor: pointer; display: none;
        }
        #btnReload:hover { color: var(--text); background: rgba(255,255,255,.06); }

        #term-wrap { flex: 1; overflow: hidden; padding: 8px 10px 0; }
        #term { height: 100%; }
        .xterm .xterm-viewport::-webkit-scrollbar { width: 6px; }
        .xterm .xterm-viewport::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

        body { display: flex; flex-direction: column; }
    </style>
</head>
<body>

<div id="bar">
    <i class="bi bi-terminal-fill" style="color:var(--blue)"></i>
    <span class="name">{{ $connection->name }}</span>
    <span class="host">{{ $connection->username }}&#64;{{ $connection->host }}:{{ $connection->port }}</span>
    <button id="btnReload" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i> Reconectar</button>
    <span id="status" class="connecting"><span class="dot"></span><span id="status-label">Conectando…</span></span>
</div>

<div id="term-wrap"><div id="term"></div></div>

<script src="https://cdn.jsdelivr.net/npm/xterm@4.19.0/lib/xterm.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.7.0/lib/xterm-addon-fit.js"></script>
<script>
const term = new Terminal({
    theme: {
        background: '#0d1117', foreground: '#e6edf3', cursor: '#58a6ff',
        selectionBackground: 'rgba(88,166,255,.25)',
        black: '#484f58', red: '#ff7b72', green: '#3fb950', yellow: '#d29922',
        blue: '#58a6ff', magenta: '#bc8cff', cyan: '#39c5cf', white: '#b1bac4',
    },
    fontFamily: "'JetBrains Mono', monospace",
    fontSize: 14,
    cursorBlink: true,
    scrollback: 5000,
});
const fitAddon = new FitAddon.FitAddon();
term.loadAddon(fitAddon);

const termEl = document.getElementById('term');
term.open(termEl);

// fitAddon.fit() reads the renderer's char-cell measurements, which aren't
// ready until the container actually has a laid-out size — on a cold page
// load a single requestAnimationFrame isn't always enough and fit() throws
// ("Cannot read properties of undefined (reading 'cell')"), which (if
// unguarded) halts the rest of the script, including the WebSocket connect()
// call below. Wait for a real size, and never let a fit() failure block
// connecting — worst case the terminal just keeps its default 80x24.
function initTerminal(attempt = 0) {
    if (termEl.offsetWidth === 0 && attempt < 60) {
        requestAnimationFrame(() => initTerminal(attempt + 1));
        return;
    }

    try {
        fitAddon.fit();
    } catch (e) {
        console.warn('fitAddon.fit() failed, keeping default size', e);
    }

    term.focus();
    connect();
}

requestAnimationFrame(() => initTerminal());

function setStatus(state, label) {
    const el = document.getElementById('status');
    el.className = state;
    document.getElementById('status-label').textContent = label;
    document.getElementById('btnReload').style.display = (state === 'error' || state === 'closed') ? '' : 'none';
}

// UTF-8-safe byte <-> base64 helpers (btoa/atob only handle Latin1).
const utf8Encoder = new TextEncoder();
const utf8Decoder = new TextDecoder('utf-8');

function bytesToBase64(bytes) {
    let binary = '';
    for (let i = 0; i < bytes.length; i++) binary += String.fromCharCode(bytes[i]);
    return btoa(binary);
}
function base64ToBytes(b64) {
    const binary = atob(b64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
    return bytes;
}

const wsProtocol = {!! request()->isSecure() ? "'wss'" : "'ws'" !!};

let ws = null;
let sessionEnded = false;

function connect() {
    setStatus('connecting', 'Conectando…');
    const wsUrl = `${wsProtocol}://{{ $wsHost }}:{{ $wsPort }}/?token={{ $token }}&cols=${term.cols}&rows=${term.rows}`;
    ws = new WebSocket(wsUrl);

    ws.onopen = () => {
        setStatus('connected', 'Conectado');
    };

    ws.onmessage = (ev) => {
        let msg;
        try { msg = JSON.parse(ev.data); } catch { return; }

        if (msg.type === 'output') {
            term.write(utf8Decoder.decode(base64ToBytes(msg.data), { stream: true }));
        } else if (msg.type === 'error') {
            sessionEnded = true;
            term.write(`\r\n\x1b[31m${msg.message}\x1b[0m\r\n`);
            setStatus('error', 'Erro');
        } else if (msg.type === 'closed') {
            sessionEnded = true;
            term.write('\r\n\x1b[90m[sessão encerrada]\x1b[0m\r\n');
            setStatus('closed', 'Encerrado');
        }
    };

    ws.onclose = () => {
        if (!sessionEnded) setStatus('closed', 'Desconectado');
    };

    ws.onerror = () => {
        if (!sessionEnded) setStatus('error', 'Erro de conexão');
    };
}

term.onData(data => {
    if (ws && ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify({ type: 'input', data: bytesToBase64(utf8Encoder.encode(data)) }));
    }
});

function sendResize() {
    try {
        fitAddon.fit();
    } catch (e) {
        console.warn('fitAddon.fit() failed on resize', e);
    }
    if (ws && ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify({ type: 'resize', cols: term.cols, rows: term.rows }));
    }
}

let resizeTimer = null;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(sendResize, 150);
});
</script>
</body>
</html>
