/*
 * Guia do Log — parser e renderização do log da API (tudo roda no navegador,
 * nada do log é enviado ao servidor).
 *
 * Formato de cada linha do log:
 *   DD/MM/AAAA HH:MM:SS:micro (grupo A) (grupo B)  conteúdo
 * Um bloco começa com a linha "(callid) (Bloco) CallID: <callid>", seguida de
 * "Type: <Bloco>"; as linhas seguintes são o conteúdo do bloco. Em listas o
 * grupo A/B traz o índice do item, por isso o agrupamento é por bloco, e não
 * por "linhas que contêm o CallID".
 */
(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory();
    else root.LogGuide = factory();
}(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    const STAGES = {
        1:   { n: '1', key: 's1',  title: 'AGV chama a API' },
        2:   { n: '2', key: 's2',  title: 'API escolhe o destino' },
        3:   { n: '3', key: 's3',  title: 'Monta a requisição' },
        4:   { n: '4', key: 's4',  title: 'Cliente responde' },
        5:   { n: '5', key: 's5',  title: 'API devolve ao AGV' },
        err: { n: '!', key: 'err', title: 'Erro' },
        x:   { n: '·', key: 'sx',  title: 'Outro' },
    };

    // Ordem importa: o primeiro que casar vence.
    const BLOCKS = [
        { re: /^INICIO\b/i,               stage: 1, name: 'INICIO (método)',
          desc: 'Início da chamada: mostra qual método o AGV chamou (o nome entre parênteses).' },
        { re: /^Tentativas\b/i,           stage: 1, name: 'Tentativas (método)',
          desc: 'Número da tentativa dessa chamada. Acima de 1 indica que a chamada foi repetida.' },
        { re: /^CamposValues\b/i,         stage: 1, name: 'CamposValues (método)',
          desc: 'Campos e valores exatamente como o AGV enviou para a API.' },
        { re: /^WebServer\s*\(URL\)/i,    stage: 2, name: 'WebServer (URL) - rota',
          desc: 'Endereço base da API do cliente para onde a chamada vai ser enviada.' },
        { re: /^WebServer\s*\(Envio\)/i,  stage: 3, name: 'WebServer(Envio) - rota',
          desc: 'Os dados que serão enviados, já com os nomes de campo que o cliente espera.' },
        { re: /^Dados\[/i,                stage: 3, name: 'Dados[rota][formato]',
          desc: 'O corpo da requisição exatamente como foi enviado (JSON, XML ou URL).' },
        { re: /^URL\[/i,                  stage: 3, name: 'URL[rota]',
          desc: 'Endereço completo que foi chamado (base + rota).' },
        { re: /^Header\[/i,               stage: 3, name: 'Header[rota]',
          desc: 'Cabeçalhos enviados: autenticação, tipo de conteúdo e tamanho.' },
        { re: /^RetornoObj\[/i,           stage: 4, name: 'RetornoObj[rota]',
          desc: 'A resposta do cliente exatamente como chegou, sem nenhum tratamento.' },
        { re: /^RetornoHeader\[/i,        stage: 4, name: 'RetornoHeader[rota]',
          desc: 'Código HTTP que o cliente devolveu (200, 201, 400, 401, 500…).' },
        { re: /^Retorno\[/i,              stage: 5, name: 'Retorno[rota]-Array',
          desc: 'A resposta do cliente convertida em lista, campo a campo.' },
        { re: /^WebServer\s*\(Retorno\)/i, stage: 5, name: 'WebServer(Retorno) - Tratado',
          desc: 'O que o AGV recebe: a resposta já tratada e formatada pela API.' },
        { re: /^Error\s*\(Geral\)/i,      stage: 'err', name: 'Error (Geral)',
          desc: 'Erro geral da chamada. O AGV recebe código 500 com essa mensagem.' },
        { re: /^Error\b/i,                stage: 'err', name: 'Error (método)',
          desc: 'Erro dentro do método. A mensagem diz o motivo.' },
    ];

    const OTHER = { stage: 'x', name: 'Outro', desc: '' };

    function classify(label) {
        for (const b of BLOCKS) if (b.re.test(label)) return b;
        return OTHER;
    }

    // ── Parsing ────────────────────────────────────────────────────────────

    const TS_RE = /^(\d{2}\/\d{2}\/\d{4})\s+(\d{2}):(\d{2}):(\d{2})(?:[:.,](\d+))?\s*/;

    /** Lê um grupo "( … )" com parênteses aninhados a partir de pos. */
    function readGroup(s, pos) {
        if (s[pos] !== '(') return null;
        let depth = 0;
        for (let i = pos; i < s.length; i++) {
            if (s[i] === '(') depth++;
            else if (s[i] === ')' && --depth === 0) return { text: s.slice(pos + 1, i), end: i + 1 };
        }
        return null;
    }

    function parseLine(line) {
        const m = TS_RE.exec(line);
        if (!m) return null;
        const g1 = readGroup(line, m[0].length);
        if (!g1) return null;
        let pos = g1.end;
        while (line[pos] === ' ') pos++;
        const g2 = readGroup(line, pos);
        if (!g2) return null;
        const ms = ((+m[2]) * 3600 + (+m[3]) * 60 + (+m[4])) * 1000 + (m[5] ? Number('0.' + m[5]) * 1000 : 0);
        return {
            date: m[1],
            time: `${m[2]}:${m[3]}:${m[4]}`,
            ms,
            a: g1.text.trim(),
            b: g2.text.trim(),
            rest: line.slice(g2.end).replace(/^\s+/, '').replace(/\s+$/, ''),
        };
    }

    function newBlock(callid, label, p, lineNo) {
        return {
            callid, label, date: p.date, time: p.time, ms: p.ms, lineNo,
            info: classify(label), lines: [],
        };
    }

    function parse(text) {
        const lines = String(text || '').split(/\r?\n/);
        const blocks = [];
        let cur = null;

        for (let i = 0; i < lines.length; i++) {
            const raw = lines[i];
            if (!raw.trim()) continue;

            const p = parseLine(raw);
            if (!p) {
                // Linha sem data: continuação do bloco atual (ex.: JSON quebrado em várias linhas).
                if (cur) cur.lines.push({ text: raw.replace(/\s+$/, ''), idx: null });
                continue;
            }

            if (/^CallID:/i.test(p.rest)) {
                cur = newBlock(p.rest.replace(/^CallID:\s*/i, ''), p.b, p, i + 1);
                blocks.push(cur);
                continue;
            }

            if (cur && /^Type:/i.test(p.rest) && p.rest.replace(/^Type:\s*/i, '') === cur.label) continue;

            if (!cur) {
                cur = newBlock(p.a || '', p.b || '(sem cabeçalho)', p, i + 1);
                blocks.push(cur);
            }

            // Em listas o 1º grupo é o índice do item, ex.: "(1) (1)  Campo => valor".
            cur.lines.push({ text: p.rest, idx: /^\d+$/.test(p.a) ? p.a : null });
        }

        return blocks;
    }

    // ── Agrupamento por chamada ────────────────────────────────────────────

    function methodOf(label) {
        const m = /\(([^()]+)\)\s*$/.exec(label);
        return m ? m[1].trim() : null;
    }

    function endpointOf(label) {
        let m = /\[([^\]]+)\]/.exec(label);
        if (m) return m[1].trim();
        m = /\)\s*-\s*(?:Tratado)?\s*(.+)$/i.exec(label);
        return m ? m[1].trim() : null;
    }

    function blockText(b) {
        return b.lines.map(l => l.text).join('\n').trim();
    }

    function tryJson(s) {
        s = (s || '').trim();
        if (!/^[\[{]/.test(s) || s.length > 2_000_000) return undefined;
        try { return JSON.parse(s); } catch { return undefined; }
    }

    function summarize(call) {
        const methods = [], endpoints = new Map(), errors = [];
        let attempts = null, hasReturn = false;

        for (const b of call.blocks) {
            const st = b.info.stage;

            if (/^INICIO\b/i.test(b.label)) {
                const m = methodOf(b.label);
                if (m && !methods.includes(m)) methods.push(m);
            }

            if (/^Tentativas\b/i.test(b.label)) {
                const m = /Tentativas\s*:\s*(\d+)/i.exec(blockText(b));
                if (m) attempts = Math.max(attempts || 0, +m[1]);
            }

            if (st === 'err') {
                const t = blockText(b).replace(/^(Erro|Exception)\s*:\s*/i, '');
                const clean = stripInternal(t);
                if (clean && !errors.includes(clean)) errors.push(clean);
            }

            if (st === 5) hasReturn = true;

            if (st === 2 || st === 3 || st === 4 || st === 5) {
                const ep = endpointOf(b.label);
                if (!ep) continue;
                if (!endpoints.has(ep)) endpoints.set(ep, { endpoint: ep, sentMs: null, recvMs: null, status: null, url: null, clientMessage: null, clientFail: false });
                const e = endpoints.get(ep);

                if (st === 3) e.sentMs = b.ms;
                if (/^URL\[/i.test(b.label)) e.url = blockText(b);
                if (/^RetornoObj\[/i.test(b.label)) {
                    e.recvMs = b.ms;
                    const j = tryJson(blockText(b));
                    if (j && typeof j === 'object' && !Array.isArray(j)) {
                        if (j.Sucesso === false || j.sucesso === false || j.success === false) e.clientFail = true;
                        e.clientMessage = j.Mensagem || j.mensagem || j.message || j.Message || null;
                    }
                }
                if (/^RetornoHeader\[/i.test(b.label)) {
                    const m = /\b(\d{3})\b/.exec(blockText(b));
                    if (m) e.status = +m[1];
                }
            }
        }

        const eps = [...endpoints.values()].map(e => ({
            ...e,
            elapsed: e.sentMs != null && e.recvMs != null && e.recvMs >= e.sentMs ? Math.round(e.recvMs - e.sentMs) : null,
        }));

        const httpFail = eps.some(e => e.status != null && e.status >= 400);
        const clientFail = eps.some(e => e.clientFail);

        let verdict = 'ok';
        if (errors.length || httpFail) verdict = 'erro';
        else if (clientFail) verdict = 'alerta';
        else if (!hasReturn) verdict = 'incompleto';

        const first = call.blocks[0], last = call.blocks[call.blocks.length - 1];
        const duration = last.ms >= first.ms ? Math.round(last.ms - first.ms) : null;

        return { methods, endpoints: eps, errors, attempts, verdict, duration, date: first.date, time: first.time };
    }

    function groupCalls(blocks) {
        const map = new Map();
        for (const b of blocks) {
            const key = b.callid || '(sem CallID)';
            if (!map.has(key)) map.set(key, { callid: key, blocks: [] });
            map.get(key).blocks.push(b);
        }
        const calls = [...map.values()];
        for (const c of calls) c.summary = summarize(c);
        return calls;
    }

    // ── Segurança na exibição ──────────────────────────────────────────────

    const MASK = '••••••••';

    /** Esconde credenciais (Authorization, token, senha…) antes de mostrar. */
    function maskSecrets(s) {
        return String(s)
            .replace(/(Authorization\s*["']?\s*[:=]>?\s*["']?\s*(?:Basic|Bearer|Token|Digest)?\s*)[^\s"',}]+/gi, '$1' + MASK)
            .replace(/(["']?)\b(senha|password|passwd|pass|pwd|token|access_token|refresh_token|id_token|client_secret|secret|apikey|api_key|x-api-key)\1(\s*(?:=>|:|=)\s*["']?)([^\s"',}&<]+)/gi,
                (all, q, key, sep) => `${q}${key}${q}${sep}${MASK}`);
    }

    /** Tira caminhos internos de arquivo das mensagens de erro ("… in /var/www/…php:605"). */
    function stripInternal(s) {
        return String(s)
            .replace(/\s+in\s+(?:[A-Za-z]:)?[\\/][^\s]*\.(?:php|ts|js)(?::\d+)?/gi, '')
            .replace(/(?:[A-Za-z]:)?(?:[\\/][\w.\-]+){2,}\.(?:php|ts|js)(?::\d+)?/gi, '[arquivo interno]')
            .trim();
    }

    function clean(s) {
        return stripInternal(maskSecrets(s));
    }

    // ── Renderização (HTML) ────────────────────────────────────────────────

    function esc(s) {
        return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function stageOf(b) { return STAGES[b.info.stage] || STAGES.x; }

    function renderLines(b) {
        const text = blockText(b);
        const json = tryJson(text);
        if (json !== undefined && b.lines.length <= 3) {
            return `<pre class="lg-pre lg-json">${esc(clean(JSON.stringify(json, null, 2)))}</pre>`;
        }

        const distinctIdx = new Set(b.lines.map(l => l.idx).filter(Boolean));
        const showIdx = distinctIdx.size > 1;
        let lastIdx = null;
        const out = [];

        for (const l of b.lines) {
            if (showIdx && l.idx && l.idx !== lastIdx) {
                out.push(`<span class="lg-item">— item ${esc(l.idx)} —</span>`);
                lastIdx = l.idx;
            }
            const t = clean(l.text);
            const kv = /^(.*?)\s=>\s?(.*)$/.exec(t);
            if (kv) out.push(`<span class="lg-k">${esc(kv[1])}</span> <span class="lg-op">=&gt;</span> <span class="lg-v">${kv[2] === '' ? '<span class="lg-empty">(vazio)</span>' : esc(kv[2])}</span>`);
            else out.push(esc(t));
        }

        // Blocos só de cabeçalho (ex.: INICIO) não têm conteúdo: não mostra caixa vazia.
        return out.length ? `<pre class="lg-pre">${out.join('\n')}</pre>` : '<div style="height:.5rem"></div>';
    }

    function renderBlock(b, opts = {}) {
        const st = stageOf(b);
        return `
<div class="lg-block lg-${st.key}">
  <div class="lg-block-head">
    <span class="lg-dot" title="${esc(st.title)}">${st.n}</span>
    <code class="lg-label">${esc(clean(b.label))}</code>
    ${opts.showCallid ? `<span class="lg-callid">${esc(b.callid)}</span>` : ''}
    <span class="lg-time">${esc(b.date)} ${esc(b.time)}</span>
  </div>
  ${b.info.desc ? `<div class="lg-block-desc">${esc(b.info.desc)}</div>` : ''}
  ${renderLines(b)}
</div>`;
    }

    function verdictBadge(v) {
        const map = {
            ok:         ['ok',     'bi-check-circle-fill',        'OK'],
            erro:       ['erro',   'bi-x-circle-fill',            'Erro'],
            alerta:     ['alerta', 'bi-exclamation-triangle-fill','Cliente recusou'],
            incompleto: ['inc',    'bi-hourglass-split',          'Incompleta'],
        };
        const [cls, icon, text] = map[v] || map.incompleto;
        return `<span class="lg-verdict lg-v-${cls}"><i class="bi ${icon}"></i> ${text}</span>`;
    }

    function statusClass(code) {
        if (code == null) return '';
        if (code < 300) return 'lg-http-ok';
        if (code < 500) return 'lg-http-warn';
        return 'lg-http-err';
    }

    function fmtMs(ms) {
        if (ms == null) return '—';
        return ms < 1000 ? `${ms} ms` : `${(ms / 1000).toFixed(ms < 10000 ? 2 : 1).replace('.', ',')} s`;
    }

    function renderCallDetail(call) {
        const s = call.summary;
        const eps = s.endpoints.map(e => `
      <div class="lg-ep">
        <code>${esc(e.endpoint)}</code>
        ${e.status != null ? `<span class="lg-http ${statusClass(e.status)}">HTTP ${e.status}</span>` : '<span class="lg-http">sem resposta</span>'}
        ${e.elapsed != null ? `<span class="lg-muted">cliente respondeu em ${fmtMs(e.elapsed)}</span>` : ''}
        ${e.clientFail ? `<span class="lg-http lg-http-warn">Sucesso = false${e.clientMessage ? ': ' + esc(clean(e.clientMessage)) : ''}</span>` : ''}
      </div>`).join('');

        const errs = s.errors.length
            ? `<div class="lg-alert-err"><i class="bi bi-x-octagon-fill"></i><div><strong>O AGV recebeu erro.</strong> Motivo registrado:<ul>${s.errors.map(e => `<li>${esc(e)}</li>`).join('')}</ul></div></div>`
            : '';

        const counts = [1, 2, 3, 4, 5].map(n => {
            const c = call.blocks.filter(b => b.info.stage === n).length;
            return `<span class="lg-mini lg-s${n} ${c ? '' : 'lg-mini-off'}"><span class="lg-dot">${n}</span>${esc(STAGES[n].title)}</span>`;
        }).join('<i class="bi bi-chevron-right lg-mini-arrow"></i>');

        return `
<div class="lg-detail-head">
  <div>
    <div class="lg-detail-callid"><span class="lg-muted">CallID</span> <code>${esc(call.callid)}</code>
      <button type="button" class="lg-copy" data-copy="${esc(call.callid)}" title="Copiar CallID"><i class="bi bi-clipboard"></i></button>
    </div>
    <div class="lg-detail-meta">
      ${verdictBadge(s.verdict)}
      <span><i class="bi bi-calendar3"></i> ${esc(s.date)} ${esc(s.time)}</span>
      ${s.methods.length ? `<span><i class="bi bi-box-arrow-in-right"></i> Método: <strong>${s.methods.map(esc).join(', ')}</strong></span>` : ''}
      ${s.attempts ? `<span><i class="bi bi-arrow-repeat"></i> Tentativa ${s.attempts}</span>` : ''}
      <span><i class="bi bi-stopwatch"></i> Total ${fmtMs(s.duration)}</span>
    </div>
  </div>
</div>
<div class="lg-mini-flow">${counts}</div>
${errs}
${eps ? `<div class="lg-eps">${eps}</div>` : ''}
<div class="lg-blocks">${call.blocks.map(b => renderBlock(b)).join('')}</div>`;
    }

    return {
        STAGES, BLOCKS, classify, parseLine, parse, groupCalls, summarize,
        maskSecrets, stripInternal, esc, renderBlock, renderCallDetail, verdictBadge,
        statusClass, fmtMs,
    };
}));
