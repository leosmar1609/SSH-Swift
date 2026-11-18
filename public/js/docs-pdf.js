/**
 * Builds the API documentation PDF (pdfmake document definition) from the
 * parsed Postman collection.
 *
 * Why pdfmake instead of the old html2pdf: html2pdf screenshots the page into
 * one huge image and slices it into A4 pages — so text is a blurry JPEG, cuts
 * fall mid-line, long docs exceed the browser's canvas limit (blank pages) and
 * the page's scroll position shifts the capture (blank first pages). pdfmake
 * lays out real, selectable text with real pagination: rows break between
 * lines, table headers repeat on each page, headings never end a page alone,
 * small blocks are never split, pages are numbered and there's a table of
 * contents with page numbers.
 *
 * Plain script, no build step: in the browser it exposes window.DocsPdf; in
 * Node (used to test it) it's a CommonJS module.
 */
(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    else root.DocsPdf = api;
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    // ── Layout ────────────────────────────────────────────────────────────
    const PAGE_W   = 595.28;               // A4 width, pt
    const MARGIN_X = 42;
    const CONTENT_W = PAGE_W - 2 * MARGIN_X;
    const CELL_PAD = 6;                    // left+right padding per side used below

    // Monospace: every glyph is 0.6em wide, so how many characters fit on a
    // line is exact — long tokens (URLs, base64, minified JSON) are wrapped by
    // us instead of overflowing past the page edge.
    const MONO_ADVANCE = 0.6;
    const CODE_SIZE = 7.8;
    const URL_SIZE  = 8.5;
    const MAX_CODE_LINES = 1500;           // per response, to keep huge dumps from producing hundreds of pages

    // Blocks at most this big are never split across pages.
    const SMALL_CODE_LINES = 25;
    const SMALL_PARAM_ROWS = 10;

    // Fixed widths (pt) for the params table so we know each column's capacity.
    // pdfmake widths exclude cell padding and borders, so subtract those.
    const PARAM_COLS = (() => {
        const usable = CONTENT_W - 3 * 2 * CELL_PAD - 4 * 0.6;
        return [135, 150, usable - 135 - 150];
    })();

    const C = {
        text: '#1e293b', muted: '#64748b', subtle: '#94a3b8', border: '#e2e8f0',
        soft: '#f8fafc', codeBg: '#f6f8fa', codeText: '#0f172a', urlBg: '#f1f5f9',
        accent: '#2563eb',
    };

    const METHOD = {
        GET:     ['#1d4ed8', '#dbeafe'],
        POST:    ['#15803d', '#dcfce7'],
        PUT:     ['#c2410c', '#ffedd5'],
        PATCH:   ['#a16207', '#fef9c3'],
        DELETE:  ['#b91c1c', '#fee2e2'],
        HEAD:    ['#475569', '#e2e8f0'],
        OPTIONS: ['#7e22ce', '#f3e8ff'],
    };

    const SOURCE = { query: ['query', '#1d4ed8'], body: ['body', '#15803d'], form: ['form', '#c2410c'] };

    function statusColors(code) {
        const n = parseInt(code, 10);
        if (n >= 200 && n < 300) return ['#15803d', '#dcfce7'];
        if (n >= 300 && n < 400) return ['#a16207', '#fef9c3'];
        if (n >= 400 && n < 500) return ['#b91c1c', '#fee2e2'];
        if (n >= 500)            return ['#7e22ce', '#f3e8ff'];
        return ['#475569', '#e2e8f0'];
    }

    function monoChars(widthPt, fontSize) {
        return Math.max(8, Math.floor((widthPt - 2 * CELL_PAD) / (fontSize * MONO_ADVANCE)));
    }

    // ── Text helpers ──────────────────────────────────────────────────────

    /** Splits one line into chunks of at most `max` chars, preferring to cut after a delimiter. */
    function hardWrap(line, max) {
        const out = [];
        let rest = line;
        while (rest.length > max) {
            let cut = -1;
            for (let i = max; i > max * 0.6; i--) {
                if (' /?&=,;:.-_)}]'.includes(rest[i - 1])) { cut = i; break; }
            }
            if (cut < 0) cut = max;
            out.push(rest.slice(0, cut));
            rest = rest.slice(cut);
        }
        out.push(rest);
        return out;
    }

    /**
     * For proportional text: pdfmake wraps at spaces, but a single token wider
     * than the column (a URL in a description, say) would overflow. Break such
     * tokens with newlines; `max` is a conservative char count for the column.
     */
    function breakLongTokens(str, max) {
        return String(str ?? '').split('\n').map(line =>
            line.split(' ').map(tok => tok.length > max ? hardWrap(tok, max).join('\n') : tok).join(' ')
        ).join('\n');
    }

    /** Mono text wrapped to exactly fit a column (keeps indentation). */
    function wrapMono(str, max) {
        return String(str ?? '').replace(/\t/g, '    ').split(/\r?\n/)
            .flatMap(line => hardWrap(line, max)).join('\n');
    }

    function clean(str) {
        return String(str ?? '').replace(/\r\n?/g, '\n').trim();
    }

    // ── Blocks ────────────────────────────────────────────────────────────

    function badge(text, [fg, bg], extra = {}) {
        return { text: ` ${text} `, bold: true, fontSize: 8, color: fg, background: bg, ...extra };
    }

    function cover(col, dateStr) {
        const desc = clean(col.description);
        return [
            { text: 'DOCUMENTAÇÃO DE API', fontSize: 9, bold: true, color: C.accent, characterSpacing: 2, margin: [0, 190, 0, 10] },
            { text: breakLongTokens(col.name, 40), fontSize: 26, bold: true, color: '#0f172a', margin: [0, 0, 0, 14] },
            {
                canvas: [{ type: 'rect', x: 0, y: 0, w: 60, h: 3, color: C.accent }],
                margin: [0, 0, 0, 16],
            },
            {
                text: [
                    { text: `${col.endpoints.length} endpoint${col.endpoints.length !== 1 ? 's' : ''}`, bold: true, color: C.text },
                    { text: `   ·   Gerado em ${dateStr}`, color: C.muted },
                ],
                fontSize: 10.5,
            },
            desc ? { text: breakLongTokens(desc, 70), fontSize: 10.5, color: C.muted, lineHeight: 1.4, margin: [0, 24, 0, 0] } : null,
        ].filter(Boolean);
    }

    function folderHeading(folder, first) {
        return {
            text: breakLongTokens(folder, 60), fontSize: 15, bold: true, color: '#0f172a',
            margin: [0, first ? 0 : 10, 0, 12],
            tocItem: true, tocStyle: { bold: true }, tocMargin: [0, 8, 0, 2],
        };
    }

    /** `heading` (a folder title) goes in the same unbreakable block, so it can't end a page alone. */
    function endpointIntro(ep, inFolder, heading) {
        const [fg, bg] = METHOD[ep.method] || METHOD.HEAD;
        const desc = clean(ep.description);

        const block = [
            heading,
            {
                table: {
                    widths: [62, '*'],
                    body: [[
                        { text: ep.method, bold: true, fontSize: 8.5, color: fg, fillColor: bg, alignment: 'center', margin: [0, 3, 0, 2] },
                        {
                            text: breakLongTokens(ep.name, 70), bold: true, fontSize: 11.5, color: '#0f172a', margin: [4, 1.5, 0, 0],
                            tocItem: true, tocMargin: [inFolder ? 14 : 0, 2, 0, 0],
                        },
                    ]],
                },
                layout: 'noBorders',
                margin: [0, 0, 0, 5],
            },
        ];

        if (ep.url) {
            block.push({
                table: {
                    widths: ['*'],
                    body: [[{
                        text: wrapMono(ep.url, monoChars(CONTENT_W, URL_SIZE)),
                        font: 'Mono', fontSize: URL_SIZE, color: '#334155', fillColor: C.urlBg,
                        margin: [CELL_PAD - 4, 3, 0, 3],
                    }]],
                },
                layout: 'noBorders',
                margin: [0, 0, 0, 6],
            });
        }

        if (desc) {
            block.push({ text: breakLongTokens(desc, 90), color: '#475569', lineHeight: 1.35, margin: [0, 0, 0, 6] });
        }

        // Kept together so a page never ends between an endpoint's title and its URL/description.
        return { stack: block.filter(Boolean), unbreakable: true };
    }

    /** Small blocks move to the next page whole instead of splitting. */
    function keepTogetherIf(small, node) {
        return small ? { stack: [node], unbreakable: true } : node;
    }

    function sectionTitleRow(title, colSpan) {
        const row = [{ text: title.toUpperCase(), fontSize: 7.5, bold: true, color: C.subtle, characterSpacing: 1, colSpan, border: [false, false, false, false], margin: [-CELL_PAD + 2, 6, 0, 3] }];
        for (let i = 1; i < colSpan; i++) row.push({});
        return row;
    }

    function paramsTable(params) {
        const keyMax = monoChars(PARAM_COLS[0], 8) - 6;  // room for the source tag
        const valMax = monoChars(PARAM_COLS[1], 7.8);
        const head = { bold: true, fontSize: 7.5, color: '#475569', fillColor: '#f1f5f9', margin: [0, 2, 0, 2] };

        const body = [
            sectionTitleRow('Parâmetros', 3),
            [{ text: 'CAMPO', ...head }, { text: 'VALOR', ...head }, { text: 'DESCRIÇÃO', ...head }],
        ];

        params.forEach((p, i) => {
            const [label, color] = SOURCE[p.source] || SOURCE.body;
            const fill = i % 2 ? C.soft : null;
            body.push([
                { text: [
                    { text: wrapMono(p.key, keyMax), font: 'Mono', fontSize: 8, color: '#0f172a' },
                    { text: `  ${label}`, fontSize: 6.5, bold: true, color },
                ], fillColor: fill },
                { text: wrapMono(p.value, valMax), font: 'Mono', fontSize: 7.8, color: '#475569', fillColor: fill },
                { text: breakLongTokens(clean(p.description), 40) || '—', color: p.description ? '#334155' : C.subtle, fillColor: fill },
            ]);
        });

        return keepTogetherIf(params.length <= SMALL_PARAM_ROWS, {
            table: { widths: PARAM_COLS, headerRows: 2, keepWithHeaderRows: 1, dontBreakRows: true, body },
            layout: {
                hLineColor: () => C.border, vLineColor: () => C.border,
                hLineWidth: (i) => (i === 0 ? 0 : 0.6), vLineWidth: (i, node) => (0.6),
                paddingLeft: () => CELL_PAD, paddingRight: () => CELL_PAD,
                paddingTop: () => 3, paddingBottom: () => 3,
            },
            fontSize: 8.5,
            margin: [0, 2, 0, 8],
        });
    }

    /**
     * One response = a single-column table: header row (status + name) that
     * repeats on every page the response spans, then one row per (wrapped)
     * code line — so it breaks cleanly between lines, never mid-glyph.
     * The first response also carries the "RESPOSTAS" title as a header row,
     * so the title can never be left alone at the bottom of a page.
     */
    function responseTable(r, withTitle) {
        const [fg, bg] = statusColors(r.status);
        const max = monoChars(CONTENT_W, CODE_SIZE);

        let lines = String(r.body ?? '').replace(/\t/g, '    ').split(/\r?\n/).flatMap(l => hardWrap(l, max));
        let omitted = 0;
        if (lines.length > MAX_CODE_LINES) {
            omitted = lines.length - MAX_CODE_LINES;
            lines = lines.slice(0, MAX_CODE_LINES);
        }

        const body = [];
        if (withTitle) {
            body.push([{ text: 'RESPOSTAS', fontSize: 7.5, bold: true, color: C.subtle, characterSpacing: 1, margin: [-CELL_PAD, 4, 0, 2] }]);
        }
        body.push([{
            text: [
                r.status !== '' && r.status != null ? badge(String(r.status), [fg, bg], { font: 'Mono' }) : '',
                { text: '  ' + breakLongTokens(r.name || 'Response', 80), bold: true, color: '#334155' },
            ],
            fontSize: 9, margin: [0, 3, 0, 4],
        }]);
        const headerRows = body.length;

        if (!r.body) {
            body.push([{ text: 'Sem corpo de resposta', italics: true, color: C.subtle, fontSize: 8.5, fillColor: C.codeBg, margin: [0, 4, 0, 4] }]);
        } else {
            lines.forEach((line, i) => body.push([{
                text: line === '' ? ' ' : line,
                font: 'Mono', fontSize: CODE_SIZE, color: C.codeText, fillColor: C.codeBg, preserveLeadingSpaces: true,
                margin: [0, i === 0 ? 4 : 0, 0, i === lines.length - 1 ? 4 : 0],
            }]));
            if (omitted) {
                body.push([{ text: `… ${omitted} linhas omitidas`, italics: true, color: C.muted, fontSize: 8, fillColor: C.codeBg, margin: [0, 2, 0, 4] }]);
            }
        }

        return keepTogetherIf(lines.length <= SMALL_CODE_LINES, {
            table: { widths: ['*'], headerRows, keepWithHeaderRows: 1, dontBreakRows: true, body },
            layout: {
                hLineWidth: () => 0, vLineWidth: () => 0,
                paddingLeft: () => CELL_PAD + 2, paddingRight: () => CELL_PAD,
                paddingTop: () => 0, paddingBottom: () => 0,
            },
            margin: [0, 0, 0, 8],
        });
    }

    /** Rough height of an endpoint, in "lines": params rows + response code lines. */
    function endpointSize(ep) {
        const max = monoChars(CONTENT_W, CODE_SIZE);
        const codeLines = (ep.responses || []).reduce((n, r) =>
            n + 2 + String(r.body ?? '').split(/\r?\n/).reduce((m, l) => m + Math.max(1, Math.ceil(l.length / max)), 0), 0);
        return (ep.allParams || []).length * 1.5 + codeLines;
    }

    function endpointBlock(ep, inFolder, heading) {
        const out = endpointBody(ep, inFolder, heading);
        const divider = out.pop();

        // A small endpoint moves to the next page whole rather than leaving
        // its title at the bottom of one page and its content on the next.
        // (Inner unbreakables are dropped: nested ones make pdfmake over-estimate
        // the height and push blocks to the next page with lots of room left.)
        return endpointSize(ep) <= 40
            ? [{ stack: out.map(withoutUnbreakable), unbreakable: true }, divider]
            : [...out, divider];
    }

    function withoutUnbreakable(node) {
        if (!node || typeof node !== 'object' || !Array.isArray(node.stack)) return node;
        const { unbreakable, ...rest } = node;
        return { ...rest, stack: rest.stack.map(withoutUnbreakable) };
    }

    function endpointBody(ep, inFolder, heading) {
        const out = [endpointIntro(ep, inFolder, heading)];

        if (ep.allParams && ep.allParams.length) out.push(paramsTable(ep.allParams));

        if (ep.responses && ep.responses.length) {
            ep.responses.forEach((r, i) => out.push(responseTable(r, i === 0)));
        } else if (!ep.allParams || !ep.allParams.length) {
            out.push({ text: 'Sem parâmetros nem exemplos de resposta.', italics: true, color: C.subtle, fontSize: 8.5, margin: [0, 0, 0, 8] });
        }

        // Divider between endpoints
        out.push({ canvas: [{ type: 'line', x1: 0, y1: 0, x2: CONTENT_W, y2: 0, lineWidth: 0.6, lineColor: C.border }], margin: [0, 6, 0, 16] });
        return out;
    }

    // ── Document ──────────────────────────────────────────────────────────

    function build(col, opts = {}) {
        const dateStr = opts.date || new Date().toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' });
        const content = [...cover(col, dateStr), { text: '', pageBreak: 'after' }];

        content.push({
            toc: {
                title: { text: 'Sumário', fontSize: 18, bold: true, color: '#0f172a', margin: [0, 0, 0, 16] },
                numberStyle: { color: C.muted },
            },
        });
        content.push({ text: '', pageBreak: 'after' });

        let lastFolder = null;
        for (const ep of col.endpoints) {
            const heading = ep.folder && ep.folder !== lastFolder ? folderHeading(ep.folder, lastFolder === null) : null;
            lastFolder = ep.folder;
            content.push(...endpointBlock(ep, !!ep.folder, heading));
        }

        return {
            info: { title: col.name, subject: 'Documentação de API', creator: 'TechIA Panel' },
            pageSize: 'A4',
            pageMargins: [MARGIN_X, 58, MARGIN_X, 50],
            defaultStyle: { font: 'Roboto', fontSize: 9.5, color: C.text, lineHeight: 1.2 },
            content,

            header: (page) => page === 1 ? null : {
                margin: [MARGIN_X, 24, MARGIN_X, 0],
                stack: [
                    { columns: [
                        { text: col.name, fontSize: 8, color: C.muted, bold: true },
                        { text: 'Documentação de API', fontSize: 8, color: C.subtle, alignment: 'right' },
                    ] },
                    { canvas: [{ type: 'line', x1: 0, y1: 6, x2: CONTENT_W, y2: 6, lineWidth: 0.6, lineColor: C.border }] },
                ],
            },

            footer: (page, pages) => page === 1 ? null : {
                margin: [MARGIN_X, 18, MARGIN_X, 0],
                columns: [
                    { text: `Gerado em ${dateStr}`, fontSize: 7.5, color: C.subtle },
                    { text: `Página ${page} de ${pages}`, fontSize: 7.5, color: C.muted, alignment: 'right' },
                ],
            },
        };
    }

    return { build, _internals: { hardWrap, breakLongTokens, wrapMono, monoChars } };
});
