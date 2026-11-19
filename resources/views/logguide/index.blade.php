@extends('layouts.app')

@section('title', 'Guia do Log')

@push('styles')
<style>
    :root {
        --lg-s1:  #58a6ff;
        --lg-s2:  #bc8cff;
        --lg-s3:  #d29922;
        --lg-s4:  #39c5cf;
        --lg-s5:  #3fb950;
        --lg-err: #f85149;
        --lg-sx:  #8b949e;
    }
    .lg-s1  { --c: var(--lg-s1); }
    .lg-s2  { --c: var(--lg-s2); }
    .lg-s3  { --c: var(--lg-s3); }
    .lg-s4  { --c: var(--lg-s4); }
    .lg-s5  { --c: var(--lg-s5); }
    .lg-err { --c: var(--lg-err); }
    .lg-sx  { --c: var(--lg-sx); }

    .lg-section { margin-top: 2.75rem; scroll-margin-top: 72px; }
    .lg-h2 { font-size: 18px; font-weight: 700; letter-spacing: -.2px; margin: 0 0 .35rem; display: flex; align-items: center; gap: .55rem; }
    .lg-lead { color: var(--lp-text-muted); margin: 0 0 1.1rem; max-width: 760px; }
    .lg-muted { color: var(--lp-text-muted); }
    code, .lg-mono { font-family: 'JetBrains Mono', monospace; font-size: 12.5px; }
    p code, li code, td code { color: var(--lp-text); background: rgba(255,255,255,.06); padding: .05rem .35rem; border-radius: 4px; }

    .lg-dot {
        width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 11.5px; font-weight: 700; color: #0d1117; background: var(--c, var(--lg-sx));
    }

    /* ── Fluxo ── */
    .lg-flow { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .5rem; }
    .lg-step {
        position: relative; display: block; text-decoration: none; color: var(--lp-text);
        background: var(--lp-surface); border: 1px solid var(--lp-border); border-top: 3px solid var(--c);
        border-radius: var(--lp-radius); padding: .9rem .85rem 1rem; transition: border-color .15s, transform .15s;
    }
    .lg-step:hover { color: var(--lp-text); transform: translateY(-2px); border-color: var(--c); }
    .lg-step-top { display: flex; align-items: center; gap: .45rem; font-weight: 600; font-size: 13.5px; line-height: 1.3; }
    .lg-step p { color: var(--lp-text-muted); font-size: 12.5px; margin: .5rem 0 .6rem; line-height: 1.45; }
    .lg-chips { display: flex; flex-wrap: wrap; gap: .25rem; }
    .lg-chip { font-family: 'JetBrains Mono', monospace; font-size: 10.5px; padding: .05rem .4rem; border-radius: 4px; color: var(--c); background: color-mix(in srgb, var(--c) 12%, transparent); border: 1px solid color-mix(in srgb, var(--c) 30%, transparent); }
    .lg-step:not(:last-child)::after {
        content: '\F285'; font-family: 'bootstrap-icons'; position: absolute; right: -.55rem; top: 50%;
        transform: translateY(-50%); z-index: 2; color: var(--lp-text-subtle); font-size: 12px;
        background: var(--lp-bg); border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center;
    }

    /* ── Anatomia da linha ── */
    .lg-anatomy { background: var(--lp-surface); border: 1px solid var(--lp-border); border-radius: var(--lp-radius); padding: 1.1rem 1.2rem; }
    .lg-anatomy-line { font-family: 'JetBrains Mono', monospace; font-size: 13px; overflow-x: auto; white-space: nowrap; padding-bottom: .4rem; }
    .lg-anatomy-line span { padding: .1rem .2rem; border-radius: 4px; border-bottom: 2px solid var(--c); }
    .lg-legend { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: .75rem; margin-top: .9rem; }
    .lg-legend div { font-size: 12.5px; color: var(--lp-text-muted); border-left: 3px solid var(--c); padding-left: .6rem; line-height: 1.45; }
    .lg-legend strong { color: var(--lp-text); display: block; font-size: 12.5px; }
    .lg-a1 { --c: #8b949e; } .lg-a2 { --c: #58a6ff; } .lg-a3 { --c: #d29922; } .lg-a4 { --c: #3fb950; }

    .lg-tips { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: .75rem; margin-top: .9rem; }
    .lg-tip { background: var(--lp-surface); border: 1px solid var(--lp-border); border-radius: var(--lp-radius); padding: .85rem 1rem; font-size: 13px; color: var(--lp-text-muted); line-height: 1.5; }
    .lg-tip i { color: var(--lp-blue); margin-right: .3rem; }
    .lg-tip strong { color: var(--lp-text); }

    /* ── Etapas detalhadas ── */
    .lg-stage { display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); gap: 1.25rem; padding: 1.4rem 0; border-top: 1px solid var(--lp-border-muted); scroll-margin-top: 72px; }
    .lg-stage:first-of-type { border-top: 0; }
    .lg-stage h3 { font-size: 15.5px; font-weight: 700; display: flex; align-items: center; gap: .5rem; margin: 0 0 .5rem; }
    .lg-stage p { color: var(--lp-text-muted); font-size: 13.5px; margin-bottom: .7rem; }
    .lg-check { list-style: none; padding: 0; margin: 0; font-size: 13px; }
    .lg-check li { position: relative; padding-left: 1.35rem; margin-bottom: .4rem; color: var(--lp-text-muted); line-height: 1.5; }
    .lg-check li::before { content: '\F26E'; font-family: 'bootstrap-icons'; position: absolute; left: 0; top: 0; color: var(--c); }
    .lg-check strong { color: var(--lp-text); }
    .lg-check-title { font-size: 11px; text-transform: uppercase; letter-spacing: .6px; font-weight: 600; color: var(--lp-text-subtle); margin: .9rem 0 .45rem; }

    /* ── Blocos de log ── */
    .lg-blocks { display: flex; flex-direction: column; gap: .55rem; }
    .lg-block { background: var(--lp-surface); border: 1px solid var(--lp-border); border-left: 3px solid var(--c); border-radius: var(--lp-radius-sm); overflow: hidden; }
    .lg-block-head { display: flex; align-items: center; gap: .5rem; padding: .5rem .75rem; background: color-mix(in srgb, var(--c) 7%, transparent); flex-wrap: wrap; }
    .lg-block-head .lg-dot { width: 18px; height: 18px; font-size: 10px; }
    .lg-label { color: var(--c); font-weight: 500; font-size: 12.5px; word-break: break-all; }
    .lg-callid { font-family: 'JetBrains Mono', monospace; font-size: 11px; color: var(--lp-text-muted); }
    .lg-time { margin-left: auto; font-family: 'JetBrains Mono', monospace; font-size: 11px; color: var(--lp-text-subtle); white-space: nowrap; }
    .lg-block-desc { font-size: 12.5px; color: var(--lp-text-muted); padding: .35rem .75rem 0; }
    .lg-pre { margin: 0; padding: .55rem .75rem .65rem; font-family: 'JetBrains Mono', monospace; font-size: 12px; line-height: 1.55; color: var(--lp-text); white-space: pre-wrap; word-break: break-word; max-height: 340px; overflow: auto; }
    .lg-k { color: #79c0ff; } .lg-op { color: var(--lp-text-subtle); } .lg-v { color: #e6edf3; }
    .lg-empty { color: var(--lp-text-subtle); font-style: italic; }
    .lg-item { color: var(--lp-text-subtle); }
    .lg-json { color: #a5d6ff; }

    /* ── Tabelas ── */
    .lg-table { width: 100%; border-collapse: collapse; font-size: 13px; background: var(--lp-surface); border: 1px solid var(--lp-border); border-radius: var(--lp-radius); overflow: hidden; }
    .lg-table th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--lp-text-subtle); font-weight: 600; padding: .6rem .85rem; border-bottom: 1px solid var(--lp-border); background: var(--lp-surface-2); }
    .lg-table td { padding: .6rem .85rem; border-bottom: 1px solid var(--lp-border-muted); color: var(--lp-text-muted); vertical-align: top; }
    .lg-table tr:last-child td { border-bottom: 0; }
    .lg-table td:first-child { color: var(--lp-text); }
    .lg-table-wrap { overflow-x: auto; border-radius: var(--lp-radius); }

    .lg-http { font-family: 'JetBrains Mono', monospace; font-size: 11.5px; padding: .05rem .45rem; border-radius: 4px; background: rgba(139,148,158,.15); color: var(--lp-text-muted); white-space: nowrap; }
    .lg-http-ok   { background: rgba(63,185,80,.14);  color: #3fb950; }
    .lg-http-warn { background: rgba(210,153,34,.15); color: #e3b341; }
    .lg-http-err  { background: rgba(248,81,73,.15);  color: #f85149; }

    /* ── Analisador ── */
    .lg-analyzer { background: var(--lp-surface); border: 1px solid var(--lp-border); border-radius: var(--lp-radius); padding: 1.1rem; }
    .lg-input { width: 100%; min-height: 150px; resize: vertical; background: var(--lp-bg); color: var(--lp-text); border: 1px solid var(--lp-border); border-radius: var(--lp-radius-sm); padding: .65rem .8rem; font-family: 'JetBrains Mono', monospace; font-size: 12px; outline: none; }
    .lg-input:focus { border-color: var(--lp-blue); box-shadow: 0 0 0 3px rgba(88,166,255,.15); }
    .lg-actions { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; margin-top: .65rem; }
    .lg-privacy { font-size: 12px; color: var(--lp-text-subtle); margin-left: auto; display: flex; align-items: center; gap: .3rem; }
    .lg-results { margin-top: 1.1rem; }
    .lg-results-bar { display: flex; gap: .65rem; align-items: center; flex-wrap: wrap; margin-bottom: .65rem; }
    .lg-results-bar .lp-input { max-width: 340px; }
    .lg-calls tbody tr { cursor: pointer; }
    .lg-calls tbody tr:hover td { background: rgba(255,255,255,.03); }
    .lg-calls tbody tr.active td { background: rgba(88,166,255,.08); }
    .lg-calls td code { word-break: break-all; }
    .lg-calls-scroll { max-height: 340px; overflow: auto; border: 1px solid var(--lp-border); border-radius: var(--lp-radius); }
    .lg-calls-scroll .lg-table { border: 0; border-radius: 0; }
    .lg-calls-scroll th { position: sticky; top: 0; z-index: 1; }

    .lg-verdict { display: inline-flex; align-items: center; gap: .3rem; font-size: 11.5px; font-weight: 600; padding: .1rem .5rem; border-radius: 999px; white-space: nowrap; }
    .lg-v-ok     { background: rgba(63,185,80,.14);  color: #3fb950; }
    .lg-v-erro   { background: rgba(248,81,73,.15);  color: #f85149; }
    .lg-v-alerta { background: rgba(210,153,34,.15); color: #e3b341; }
    .lg-v-inc    { background: rgba(139,148,158,.15); color: #8b949e; }

    .lg-detail { margin-top: 1.1rem; border-top: 1px solid var(--lp-border); padding-top: 1.1rem; }
    .lg-detail-callid { font-size: 14px; display: flex; align-items: center; gap: .45rem; flex-wrap: wrap; }
    .lg-detail-callid code { font-size: 13px; word-break: break-all; }
    .lg-detail-meta { display: flex; flex-wrap: wrap; gap: .4rem 1rem; align-items: center; margin-top: .45rem; font-size: 12.5px; color: var(--lp-text-muted); }
    .lg-detail-meta strong { color: var(--lp-text); }
    .lg-copy { background: none; border: 1px solid var(--lp-border); color: var(--lp-text-muted); border-radius: 5px; padding: 0 .35rem; font-size: 12px; cursor: pointer; }
    .lg-copy:hover { color: var(--lp-text); border-color: var(--lp-text-subtle); }
    .lg-mini-flow { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; margin: .9rem 0; }
    .lg-mini { display: inline-flex; align-items: center; gap: .35rem; font-size: 12px; padding: .2rem .55rem .2rem .25rem; border-radius: 999px; border: 1px solid color-mix(in srgb, var(--c) 35%, transparent); color: var(--lp-text); }
    .lg-mini .lg-dot { width: 18px; height: 18px; font-size: 10px; }
    .lg-mini-off { opacity: .35; }
    .lg-mini-arrow { color: var(--lp-text-subtle); font-size: 10px; }
    .lg-alert-err { display: flex; gap: .65rem; background: rgba(248,81,73,.08); border: 1px solid rgba(248,81,73,.35); border-radius: var(--lp-radius-sm); padding: .7rem .9rem; font-size: 13px; margin-bottom: .75rem; }
    .lg-alert-err > i { color: var(--lg-err); font-size: 16px; }
    .lg-alert-err ul { margin: .25rem 0 0; padding-left: 1.1rem; }
    .lg-eps { display: flex; flex-direction: column; gap: .4rem; margin-bottom: .9rem; }
    .lg-ep { display: flex; flex-wrap: wrap; align-items: center; gap: .55rem; font-size: 12.5px; background: var(--lp-surface-2); border: 1px solid var(--lp-border-muted); border-radius: var(--lp-radius-sm); padding: .45rem .7rem; }
    .lg-ep code { color: var(--lp-text); }

    .lg-toc { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .9rem; }
    .lg-toc a { font-size: 12.5px; color: var(--lp-text-muted); text-decoration: none; border: 1px solid var(--lp-border); border-radius: 999px; padding: .2rem .7rem; }
    .lg-toc a:hover { color: var(--lp-text); border-color: var(--lp-text-subtle); }

    @media (max-width: 960px) {
        .lg-flow { grid-template-columns: 1fr 1fr; }
        .lg-step:not(:last-child)::after { display: none; }
        .lg-stage { grid-template-columns: 1fr; }
        .lg-legend, .lg-tips { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 560px) {
        .lg-flow, .lg-legend, .lg-tips { grid-template-columns: 1fr; }
        .lp-main { padding: 1.5rem 1rem; }
        .lg-privacy { margin-left: 0; }
    }
</style>
@endpush

@section('content')

<div class="lp-page-header">
    <div>
        <h1 class="lp-page-title">Guia do Log da API</h1>
        <p class="lp-page-subtitle">Como ler o log de uma chamada: do pedido do AGV até a resposta que ele recebe.</p>
    </div>
    <a href="#analisar" class="btn-lp-primary"><i class="bi bi-search"></i> Analisar um log</a>
</div>

<nav class="lg-toc" aria-label="Seções">
    <a href="#caminho">O caminho de uma chamada</a>
    <a href="#ler-linha">Como ler uma linha</a>
    <a href="#etapas">As 5 etapas no log</a>
    <a href="#erros">Quando dá erro</a>
    <a href="#diagnostico">Onde olhar</a>
    <a href="#analisar">Analisar um log</a>
    <a href="#glossario">Glossário</a>
</nav>

{{-- ── 1. Fluxo ─────────────────────────────────────────────────────────── --}}
<section class="lg-section" id="caminho">
    <h2 class="lg-h2"><i class="bi bi-diagram-3" style="color:var(--lp-blue)"></i> O caminho de uma chamada</h2>
    <p class="lg-lead">Toda chamada passa pelas mesmas 5 etapas, sempre nesta ordem. Cada etapa aparece no log como um ou mais blocos. Clique numa etapa para ver como ela fica no log.</p>

    <div class="lg-flow">
        <a href="#etapa-1" class="lg-step lg-s1">
            <div class="lg-step-top"><span class="lg-dot">1</span>AGV chama a API</div>
            <p>O AGV pede um método (ex.: <em>consultaContrato</em>) e envia os campos.</p>
            <div class="lg-chips"><span class="lg-chip">INICIO</span><span class="lg-chip">Tentativas</span><span class="lg-chip">CamposValues</span></div>
        </a>
        <a href="#etapa-2" class="lg-step lg-s2">
            <div class="lg-step-top"><span class="lg-dot">2</span>API escolhe o destino</div>
            <p>A configuração do cliente diz para qual API externa a chamada vai.</p>
            <div class="lg-chips"><span class="lg-chip">WebServer (URL)</span></div>
        </a>
        <a href="#etapa-3" class="lg-step lg-s3">
            <div class="lg-step-top"><span class="lg-dot">3</span>Monta a requisição</div>
            <p>Os campos viram o corpo, a URL completa e os cabeçalhos do envio.</p>
            <div class="lg-chips"><span class="lg-chip">WebServer(Envio)</span><span class="lg-chip">Dados</span><span class="lg-chip">URL</span><span class="lg-chip">Header</span></div>
        </a>
        <a href="#etapa-4" class="lg-step lg-s4">
            <div class="lg-step-top"><span class="lg-dot">4</span>Cliente responde</div>
            <p>Chega a resposta do cliente, sem tratamento, e o código HTTP.</p>
            <div class="lg-chips"><span class="lg-chip">RetornoObj</span><span class="lg-chip">RetornoHeader</span></div>
        </a>
        <a href="#etapa-5" class="lg-step lg-s5">
            <div class="lg-step-top"><span class="lg-dot">5</span>API devolve ao AGV</div>
            <p>A resposta é tratada e formatada. É exatamente isso que o AGV recebe.</p>
            <div class="lg-chips"><span class="lg-chip">Retorno-Array</span><span class="lg-chip">WebServer(Retorno)</span></div>
        </a>
    </div>
</section>

{{-- ── 2. Anatomia ──────────────────────────────────────────────────────── --}}
<section class="lg-section" id="ler-linha">
    <h2 class="lg-h2"><i class="bi bi-text-paragraph" style="color:var(--lp-blue)"></i> Como ler uma linha</h2>
    <p class="lg-lead">Cada chamada tem um <strong>CallID</strong>, um identificador que aparece em quase todas as linhas dela. Para achar uma chamada no log, procure pelo CallID.</p>

    <div class="lg-anatomy">
        <div class="lg-anatomy-line"><span class="lg-a1">25/03/2026 08:47:53:335736</span> <span class="lg-a2">(Teste_Oktor)</span> <span class="lg-a3">(CamposValues (consultaContrato))</span> <span class="lg-a4">crm =&gt; pefisa_negociacao</span></div>
        <div class="lg-legend">
            <div class="lg-a1"><strong>Data e hora</strong>Quando aconteceu, com microssegundos. Serve para medir quanto cada etapa demorou.</div>
            <div class="lg-a2"><strong>CallID</strong>O identificador da chamada. Todas as linhas da mesma chamada têm o mesmo.</div>
            <div class="lg-a3"><strong>Bloco</strong>Qual parte da chamada é esta linha. Entre parênteses, o método chamado.</div>
            <div class="lg-a4"><strong>Conteúdo</strong>O dado em si. Normalmente no formato <code>campo =&gt; valor</code>.</div>
        </div>
    </div>

    <div class="lg-tips">
        <div class="lg-tip"><i class="bi bi-bookmark-fill"></i><strong>Todo bloco começa com duas linhas:</strong> <code>CallID: …</code> e <code>Type: …</code>. O <code>Type</code> diz que bloco é aquele. O conteúdo vem logo abaixo.</div>
        <div class="lg-tip"><i class="bi bi-list-ol"></i><strong>Em listas, a linha mostra <code>(1) (1)</code> no lugar do CallID.</strong> É o número do item. Por isso, leia o bloco inteiro, e não só as linhas que contêm o CallID.</div>
        <div class="lg-tip"><i class="bi bi-shuffle"></i><strong>Várias chamadas acontecem ao mesmo tempo.</strong> Os blocos de CallIDs diferentes podem aparecer intercalados. Siga sempre o mesmo CallID.</div>
    </div>
</section>

{{-- ── 3. Etapas ────────────────────────────────────────────────────────── --}}
<section class="lg-section" id="etapas">
    <h2 class="lg-h2"><i class="bi bi-signpost-split" style="color:var(--lp-blue)"></i> As 5 etapas no log</h2>
    <p class="lg-lead">Exemplo real de uma chamada <code>consultaContrato</code> com CallID <code>Teste_Oktor</code>, separado por etapa.</p>

    <div class="lg-stage lg-s1" id="etapa-1">
        <div>
            <h3><span class="lg-dot">1</span> AGV chama a API</h3>
            <p>Mostra a data e hora em que o AGV mandou a requisição, qual método ele chamou, o número da tentativa e os campos e valores que ele enviou.</p>
            <div class="lg-check-title">O que conferir aqui</div>
            <ul class="lg-check">
                <li>O <strong>método</strong> no <code>INICIO</code> é o esperado?</li>
                <li>Em <code>CamposValues</code>, algum campo veio <strong>vazio ou errado</strong>? Se sim, o problema começa no que o AGV enviou.</li>
                <li><code>Tentativas</code> acima de 1 indica que a chamada foi <strong>repetida</strong>.</li>
            </ul>
        </div>
        <div class="lg-blocks" data-sample="Teste_Oktor" data-stage="1"></div>
    </div>

    <div class="lg-stage lg-s2" id="etapa-2">
        <div>
            <h3><span class="lg-dot">2</span> API escolhe o destino</h3>
            <p>Mostra para onde a chamada vai. O nome depois do <code>-</code> é a rota da API do cliente, e o conteúdo é o endereço base dessa API.</p>
            <div class="lg-check-title">O que conferir aqui</div>
            <ul class="lg-check">
                <li>O endereço é o do <strong>cliente e ambiente certos</strong> (produção ou homologação)?</li>
            </ul>
        </div>
        <div class="lg-blocks" data-sample="Teste_Oktor" data-stage="2"></div>
    </div>

    <div class="lg-stage lg-s3" id="etapa-3">
        <div>
            <h3><span class="lg-dot">3</span> Monta a requisição</h3>
            <p>Mostra como a API transformou os campos do AGV na requisição para o cliente:</p>
            <ul class="lg-check">
                <li><code>WebServer(Envio)</code>: os dados, já com os <strong>nomes de campo do cliente</strong>.</li>
                <li><code>Dados</code>: o <strong>corpo</strong> exatamente como foi enviado (JSON, XML ou URL).</li>
                <li><code>URL</code>: o <strong>endereço completo</strong> que foi chamado.</li>
                <li><code>Header</code>: os <strong>cabeçalhos</strong>, como autenticação e tipo de conteúdo.</li>
            </ul>
            <div class="lg-check-title">O que conferir aqui</div>
            <ul class="lg-check">
                <li>Se o cliente diz que recebeu algo errado, <strong>é aqui que está o que foi realmente enviado</strong>.</li>
                <li>Para reproduzir a chamada, copie <code>URL</code>, <code>Header</code> e <code>Dados</code> para o <a href="{{ route('httpclient.index') }}" target="_blank" rel="noopener">HTTP Client</a> do painel.</li>
            </ul>
        </div>
        <div class="lg-blocks" data-sample="Teste_Oktor" data-stage="3"></div>
    </div>

    <div class="lg-stage lg-s4" id="etapa-4">
        <div>
            <h3><span class="lg-dot">4</span> Cliente responde</h3>
            <p><code>RetornoObj</code> é exatamente o que o cliente devolveu para a API, sem nenhum tratamento. <code>RetornoHeader</code> é o código HTTP da resposta.</p>
            <div class="lg-check-title">O que conferir aqui</div>
            <ul class="lg-check">
                <li>O código HTTP: veja a tabela em <a href="#diagnostico">Onde olhar</a>.</li>
                <li><strong>Código 200 não garante sucesso.</strong> Confira no <code>RetornoObj</code> se veio <code>"Sucesso": false</code> e uma <code>Mensagem</code>.</li>
                <li>A diferença de horário entre o <code>Header</code> (etapa 3) e o <code>RetornoObj</code> é o <strong>tempo que o cliente levou para responder</strong>. No exemplo: 104 ms.</li>
            </ul>
        </div>
        <div class="lg-blocks" data-sample="Teste_Oktor" data-stage="4"></div>
    </div>

    <div class="lg-stage lg-s5" id="etapa-5">
        <div>
            <h3><span class="lg-dot">5</span> API devolve ao AGV</h3>
            <p><code>Retorno-Array</code> é a resposta do cliente convertida em lista. <code>WebServer(Retorno) - Tratado</code> é a resposta já formatada pela API: <strong>o AGV recebe exatamente isso</strong>.</p>
            <div class="lg-check-title">O que conferir aqui</div>
            <ul class="lg-check">
                <li>Os formatos mudam nesta etapa. No exemplo, o vencimento passa de <code>2022-12-21</code> para <code>21/12/2022</code>, e o valor passa de <code>1450.67</code> para <code>1450,67</code>.</li>
                <li>Se o dado está certo no <code>RetornoObj</code> mas errado ou vazio aqui, o problema está na <strong>configuração de retorno</strong>, e não no cliente.</li>
            </ul>
        </div>
        <div class="lg-blocks" data-sample="Teste_Oktor" data-stage="5"></div>
    </div>
</section>

{{-- ── 4. Erros ─────────────────────────────────────────────────────────── --}}
<section class="lg-section lg-err" id="erros">
    <h2 class="lg-h2"><i class="bi bi-x-octagon" style="color:var(--lg-err)"></i> Quando dá erro</h2>
    <p class="lg-lead">Quando algo dá errado, aparecem blocos <code>Error</code>. O <code>Error (método)</code> diz em qual método falhou e o <code>Error (Geral)</code> traz a mensagem final. Nesse caso <strong>o AGV recebe código 500</strong> com essa mensagem.</p>

    <div class="lg-stage" style="border-top:0;padding-top:0">
        <div>
            <p>Neste exemplo, o cliente respondeu <strong>HTTP 200</strong>, mas com <code>"Sucesso": false</code> e a mensagem <em>"O vencimento deve ser menor que 31/03/2026"</em>. Mesmo com o 200, a API tratou como erro e o AGV recebeu erro.</p>
            <div class="lg-check-title">O que conferir aqui</div>
            <ul class="lg-check">
                <li>Leia a <strong>mensagem do erro</strong>. Normalmente ela já diz o motivo.</li>
                <li>Suba no log pelo mesmo CallID e veja o <code>RetornoObj</code>. Se a mensagem veio de lá, foi o <strong>cliente</strong> que recusou.</li>
            </ul>
        </div>
        <div class="lg-blocks" data-sample="uKuemVBDvm5IiYpmQCZzGdSShvqtm581451871" data-stage="all"></div>
    </div>
</section>

{{-- ── 5. Diagnóstico ───────────────────────────────────────────────────── --}}
<section class="lg-section" id="diagnostico">
    <h2 class="lg-h2"><i class="bi bi-compass" style="color:var(--lp-blue)"></i> Onde olhar</h2>
    <p class="lg-lead">Os problemas mais comuns e o bloco do log que responde cada um.</p>

    <div class="lg-table-wrap">
        <table class="lg-table">
            <thead><tr><th style="width:34%">Situação</th><th>Onde olhar no log</th></tr></thead>
            <tbody>
                <tr><td>O AGV recebeu erro</td><td>Blocos <code>Error</code>: a mensagem diz o motivo. Depois veja o <code>RetornoObj</code> para saber se o erro veio do cliente.</td></tr>
                <tr><td>Um campo chegou vazio ou errado no AGV</td><td>Compare o <code>RetornoObj</code> (o cliente mandou o campo?) com o <code>WebServer(Retorno) - Tratado</code> (a API formatou certo?).</td></tr>
                <tr><td>O cliente diz que recebeu dados errados</td><td><code>Dados</code>, <code>URL</code> e <code>Header</code>: é o que foi realmente enviado.</td></tr>
                <tr><td>Faltou informação no envio</td><td><code>CamposValues</code>: veja se o próprio AGV mandou o campo.</td></tr>
                <tr><td>A chamada está lenta</td><td>Compare o horário do <code>Header</code> com o do <code>RetornoObj</code>: essa diferença é o tempo de resposta do cliente.</td></tr>
                <tr><td>A chamada não aparece no log</td><td>Confira o CallID e o dia: o log é separado por dia.</td></tr>
            </tbody>
        </table>
    </div>

    <div class="lg-table-wrap" style="margin-top:.9rem">
        <table class="lg-table">
            <thead><tr><th style="width:34%">Código HTTP (<code>RetornoHeader</code>)</th><th>O que significa</th></tr></thead>
            <tbody>
                <tr><td><span class="lg-http lg-http-ok">200</span> <span class="lg-http lg-http-ok">201</span> <span class="lg-http lg-http-ok">203</span></td><td>O cliente respondeu normalmente. Mesmo assim, confira o <code>Sucesso</code> e a <code>Mensagem</code> no <code>RetornoObj</code>.</td></tr>
                <tr><td><span class="lg-http lg-http-warn">400</span></td><td>O cliente recusou os dados enviados: campo faltando ou em formato inválido.</td></tr>
                <tr><td><span class="lg-http lg-http-warn">401</span> <span class="lg-http lg-http-warn">403</span></td><td>Problema de autenticação: token ou credencial inválidos ou expirados.</td></tr>
                <tr><td><span class="lg-http lg-http-warn">404</span></td><td>A rota chamada não existe no cliente. Confira a <code>URL</code>.</td></tr>
                <tr><td><span class="lg-http lg-http-warn">415</span></td><td>O cliente não aceita o formato do corpo (JSON, XML ou URL). Confira o <code>Content-Type</code> no <code>Header</code>.</td></tr>
                <tr><td><span class="lg-http lg-http-err">500</span> <span class="lg-http lg-http-err">502</span> <span class="lg-http lg-http-err">503</span></td><td>Falha do lado do cliente: a API dele caiu ou está indisponível.</td></tr>
            </tbody>
        </table>
    </div>
</section>

{{-- ── 6. Analisador ────────────────────────────────────────────────────── --}}
<section class="lg-section" id="analisar">
    <h2 class="lg-h2"><i class="bi bi-search" style="color:var(--lp-blue)"></i> Analisar um log</h2>
    <p class="lg-lead">Cole um trecho do log ou abra o arquivo. A ferramenta separa as chamadas por CallID e mostra cada uma dividida nas 5 etapas.</p>

    <div class="lg-analyzer">
        <textarea id="lg-input" class="lg-input" spellcheck="false" placeholder="Cole aqui o trecho do log…"></textarea>
        <div class="lg-actions">
            <button type="button" class="btn-lp-primary" id="lg-run"><i class="bi bi-play-fill"></i> Analisar</button>
            <label class="btn-lp-secondary" style="margin:0;cursor:pointer">
                <i class="bi bi-folder2-open"></i> Abrir arquivo
                <input type="file" id="lg-file" accept=".log,.txt,text/plain" hidden>
            </label>
            <button type="button" class="btn-lp-secondary" id="lg-example"><i class="bi bi-file-earmark-text"></i> Usar exemplo</button>
            <button type="button" class="btn-lp-secondary" id="lg-clear"><i class="bi bi-x-lg"></i> Limpar</button>
            <span class="lg-privacy"><i class="bi bi-shield-lock"></i> O log é lido só no seu navegador e não é enviado ao servidor. Senhas e tokens aparecem mascarados.</span>
        </div>

        <div id="lg-results" class="lg-results" hidden>
            <div class="lg-results-bar">
                <input type="search" id="lg-filter" class="lp-input" placeholder="Filtrar por CallID, método, rota ou texto…">
                <span id="lg-count" class="lg-muted" style="font-size:12.5px"></span>
            </div>
            <div class="lg-calls-scroll">
                <table class="lg-table lg-calls">
                    <thead><tr><th>CallID</th><th>Hora</th><th>Método</th><th>Rota do cliente</th><th>HTTP</th><th>Situação</th></tr></thead>
                    <tbody id="lg-calls-body"></tbody>
                </table>
            </div>
            <div id="lg-detail" class="lg-detail" hidden></div>
        </div>
        <div id="lg-nothing" class="lg-muted" style="margin-top:.9rem;font-size:13px" hidden>
            <i class="bi bi-info-circle"></i> Nenhuma chamada encontrada. Confira se o texto colado é do log da API: as linhas começam com data e hora, como <code>25/03/2026 08:47:53:334931 (CallID) (Bloco)</code>.
        </div>
    </div>
</section>

{{-- ── 7. Glossário ─────────────────────────────────────────────────────── --}}
<section class="lg-section" id="glossario">
    <h2 class="lg-h2"><i class="bi bi-book" style="color:var(--lp-blue)"></i> Glossário dos blocos</h2>
    <p class="lg-lead">Todos os blocos que podem aparecer no log, na ordem em que acontecem.</p>
    <div class="lg-table-wrap">
        <table class="lg-table">
            <thead><tr><th style="width:30%">Bloco</th><th style="width:22%">Etapa</th><th>O que mostra</th></tr></thead>
            <tbody id="lg-glossary"></tbody>
        </table>
    </div>
</section>

<script type="application/json" id="lg-sample-data">@json($sample)</script>

@endsection

@push('scripts')
<script src="{{ asset('js/log-guide.js') }}?v={{ @filemtime(public_path('js/log-guide.js')) }}"></script>
<script>
(function () {
    const LG = window.LogGuide;
    const esc = LG.esc;
    const SAMPLE = JSON.parse(document.getElementById('lg-sample-data').textContent || '""');

    // ── Exemplos nas etapas ──────────────────────────────────────────────
    const sampleBlocks = LG.parse(SAMPLE);
    document.querySelectorAll('[data-sample]').forEach(el => {
        const stage = el.dataset.stage;
        const list = sampleBlocks.filter(b => b.callid === el.dataset.sample && (stage === 'all' || String(b.info.stage) === stage));
        el.innerHTML = list.map(b => LG.renderBlock(b)).join('');
    });

    // ── Glossário ─────────────────────────────────────────────────────────
    document.getElementById('lg-glossary').innerHTML = LG.BLOCKS.map(b => {
        const st = LG.STAGES[b.stage];
        return `<tr class="lg-${st.key}"><td><code style="color:var(--c);background:none;padding:0">${esc(b.name)}</code></td>
            <td><span style="display:inline-flex;align-items:center;gap:.4rem"><span class="lg-dot" style="width:18px;height:18px;font-size:10px">${st.n}</span>${esc(st.title)}</span></td>
            <td>${esc(b.desc)}</td></tr>`;
    }).join('');

    // ── Analisador ────────────────────────────────────────────────────────
    const $ = id => document.getElementById(id);
    const input = $('lg-input'), results = $('lg-results'), nothing = $('lg-nothing');
    const body = $('lg-calls-body'), detail = $('lg-detail'), filter = $('lg-filter'), count = $('lg-count');
    const MAX_ROWS = 500;
    let calls = [], selected = null;

    function searchText(c) {
        const s = c.summary;
        return (c.callid + ' ' + s.methods.join(' ') + ' ' + s.endpoints.map(e => e.endpoint).join(' ') + ' ' + s.errors.join(' ')).toLowerCase();
    }

    function analyze(text) {
        const t0 = performance.now();
        calls = LG.groupCalls(LG.parse(text));
        calls.forEach(c => c._search = searchText(c));
        selected = null;
        detail.hidden = true;
        filter.value = '';

        results.hidden = calls.length === 0;
        nothing.hidden = calls.length !== 0 || !text.trim();
        if (!calls.length) return;

        renderList();
        if (calls.length === 1) select(calls[0]);
        console.debug('[guia-log] %d chamadas em %d ms', calls.length, Math.round(performance.now() - t0));
    }

    function renderList() {
        const q = filter.value.trim().toLowerCase();
        // Mais recentes primeiro: é o que normalmente se procura.
        const list = calls.filter(c => !q || c._search.includes(q)).reverse();
        const shown = list.slice(0, MAX_ROWS);

        count.textContent = `${list.length} de ${calls.length} chamada${calls.length === 1 ? '' : 's'}` +
            (list.length > MAX_ROWS ? ` (mostrando as ${MAX_ROWS} mais recentes; use o filtro)` : '');

        body.innerHTML = shown.map(c => {
            const s = c.summary;
            const http = s.endpoints.map(e => e.status != null ? `<span class="lg-http ${LG.statusClass(e.status)}">${e.status}</span>` : '').join(' ');
            return `<tr data-id="${esc(c.callid)}" class="${c === selected ? 'active' : ''}">
                <td><code>${esc(c.callid)}</code></td>
                <td class="lg-mono" style="white-space:nowrap">${esc(s.time)}</td>
                <td>${esc(s.methods.join(', ') || '—')}</td>
                <td>${s.endpoints.map(e => `<code>${esc(e.endpoint)}</code>`).join('<br>') || '—'}</td>
                <td>${http || '—'}</td>
                <td>${LG.verdictBadge(s.verdict)}</td>
            </tr>`;
        }).join('') || `<tr><td colspan="6" class="lg-muted">Nenhuma chamada com esse filtro.</td></tr>`;
    }

    function select(call) {
        selected = call;
        detail.innerHTML = LG.renderCallDetail(call);
        detail.hidden = false;
        body.querySelectorAll('tr').forEach(tr => tr.classList.toggle('active', tr.dataset.id === call.callid));
    }

    body.addEventListener('click', e => {
        const tr = e.target.closest('tr[data-id]');
        if (!tr) return;
        const call = calls.find(c => c.callid === tr.dataset.id);
        if (call) {
            select(call);
            detail.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    detail.addEventListener('click', e => {
        const btn = e.target.closest('[data-copy]');
        if (!btn) return;
        copyText(btn.dataset.copy).then(() => {
            btn.innerHTML = '<i class="bi bi-check2"></i>';
            setTimeout(() => btn.innerHTML = '<i class="bi bi-clipboard"></i>', 1200);
        });
    });

    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(text);
        // HTTP simples (sem HTTPS): clipboard API não está disponível.
        const ta = document.createElement('textarea');
        ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); } finally { ta.remove(); }
        return Promise.resolve();
    }

    let filterTimer;
    filter.addEventListener('input', () => { clearTimeout(filterTimer); filterTimer = setTimeout(renderList, 120); });

    // Arquivo grande fica guardado aqui em vez de ir para o textarea.
    let fileText = null, filePlaceholder = null;
    const currentText = () => (fileText !== null && input.value === filePlaceholder) ? fileText : input.value;
    const forgetFile = () => { fileText = filePlaceholder = null; };

    $('lg-run').addEventListener('click', () => analyze(currentText()));
    $('lg-example').addEventListener('click', () => { forgetFile(); input.value = SAMPLE; analyze(SAMPLE); });
    $('lg-clear').addEventListener('click', () => { forgetFile(); input.value = ''; calls = []; results.hidden = true; nothing.hidden = true; input.focus(); });
    input.addEventListener('keydown', e => { if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) analyze(currentText()); });

    $('lg-file').addEventListener('change', e => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = () => {
            const text = String(reader.result || '');
            forgetFile();
            // Arquivo grande não vai para o textarea (travaria a página); só é analisado.
            if (text.length > 300000) {
                fileText = text;
                filePlaceholder = `[Arquivo ${file.name}: ${(file.size / 1048576).toFixed(1).replace('.', ',')} MB carregado. O conteúdo não é exibido aqui por ser grande.]`;
                input.value = filePlaceholder;
            } else {
                input.value = text;
            }
            analyze(text);
            e.target.value = '';
        };
        reader.readAsText(file);
    });
})();
</script>
@endpush
