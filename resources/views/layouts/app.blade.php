<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LeoPanel') — LeoPanel</title>

    {{-- Bootstrap 5.3 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    {{-- Inter font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
        :root {
            --lp-bg:          #0d1117;
            --lp-surface:     #161b22;
            --lp-surface-2:   #1c2128;
            --lp-border:      #30363d;
            --lp-border-muted:#21262d;
            --lp-text:        #e6edf3;
            --lp-text-muted:  #8b949e;
            --lp-text-subtle: #6e7681;
            --lp-accent:      #238636;
            --lp-accent-hover:#2ea043;
            --lp-blue:        #58a6ff;
            --lp-blue-dim:    #1f6feb;
            --lp-red:         #f85149;
            --lp-orange:      #d29922;
            --lp-purple:      #bc8cff;
            --lp-shadow:      0 1px 3px rgba(0,0,0,.4), 0 4px 12px rgba(0,0,0,.25);
            --lp-shadow-lg:   0 4px 16px rgba(0,0,0,.5);
            --lp-radius:      8px;
            --lp-radius-sm:   6px;
        }

        * { box-sizing: border-box; }

        body {
            background: var(--lp-bg);
            color: var(--lp-text);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            min-height: 100vh;
        }

        /* ── Navbar ── */
        .lp-navbar {
            background: var(--lp-surface);
            border-bottom: 1px solid var(--lp-border);
            padding: 0 1.5rem;
            height: 56px;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(8px);
        }

        .lp-brand {
            display: flex;
            align-items: center;
            gap: .5rem;
            text-decoration: none;
            font-weight: 700;
            font-size: 16px;
            color: var(--lp-text);
            letter-spacing: -.3px;
        }

        .lp-brand-icon {
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, var(--lp-blue-dim), var(--lp-blue));
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .lp-brand span { color: var(--lp-blue); }

        .lp-nav-sep {
            width: 1px;
            height: 20px;
            background: var(--lp-border);
        }

        .lp-nav-link {
            color: var(--lp-text-muted);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            padding: .3rem .6rem;
            border-radius: var(--lp-radius-sm);
            transition: color .15s, background .15s;
        }

        .lp-nav-link:hover,
        .lp-nav-link.active {
            color: var(--lp-text);
            background: rgba(255,255,255,.06);
        }

        .lp-navbar-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        /* ── Botões ── */
        .btn-lp-primary {
            background: var(--lp-accent);
            color: #fff;
            border: 1px solid rgba(255,255,255,.1);
            font-weight: 500;
            font-size: 13px;
            padding: .45rem 1rem;
            border-radius: var(--lp-radius-sm);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            transition: background .15s, box-shadow .15s;
            cursor: pointer;
        }

        .btn-lp-primary:hover {
            background: var(--lp-accent-hover);
            color: #fff;
            box-shadow: 0 0 0 3px rgba(35,134,54,.25);
        }

        .btn-lp-secondary {
            background: var(--lp-surface-2);
            color: var(--lp-text);
            border: 1px solid var(--lp-border);
            font-weight: 500;
            font-size: 13px;
            padding: .45rem 1rem;
            border-radius: var(--lp-radius-sm);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            transition: background .15s, border-color .15s;
            cursor: pointer;
        }

        .btn-lp-secondary:hover {
            background: rgba(255,255,255,.06);
            border-color: var(--lp-text-subtle);
            color: var(--lp-text);
        }

        .btn-lp-danger {
            background: transparent;
            color: var(--lp-red);
            border: 1px solid var(--lp-border);
            font-weight: 500;
            font-size: 13px;
            padding: .45rem 1rem;
            border-radius: var(--lp-radius-sm);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            transition: background .15s, border-color .15s;
            cursor: pointer;
        }

        .btn-lp-danger:hover {
            background: rgba(248,81,73,.1);
            border-color: var(--lp-red);
            color: var(--lp-red);
        }

        /* ── Layout ── */
        .lp-main {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem;
        }

        /* ── Page Header ── */
        .lp-page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .lp-page-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--lp-text);
            letter-spacing: -.4px;
            margin: 0;
        }

        .lp-page-subtitle {
            color: var(--lp-text-muted);
            font-size: 13px;
            margin: .2rem 0 0;
        }

        /* ── Cards ── */
        .lp-card {
            background: var(--lp-surface);
            border: 1px solid var(--lp-border);
            border-radius: var(--lp-radius);
            box-shadow: var(--lp-shadow);
            transition: border-color .15s, box-shadow .15s;
        }

        .lp-card:hover {
            border-color: #444c56;
            box-shadow: var(--lp-shadow-lg);
        }

        .lp-card-body {
            padding: 1.25rem 1.5rem;
        }

        .lp-card-footer {
            padding: .85rem 1.5rem;
            border-top: 1px solid var(--lp-border-muted);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
        }

        /* ── Form ── */
        .lp-form-section {
            background: var(--lp-surface);
            border: 1px solid var(--lp-border);
            border-radius: var(--lp-radius);
            padding: 1.75rem 2rem;
            box-shadow: var(--lp-shadow);
        }

        .lp-form-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--lp-text);
            margin-bottom: .35rem;
            display: block;
        }

        .lp-form-hint {
            font-size: 12px;
            color: var(--lp-text-muted);
            margin-top: .3rem;
        }

        .lp-input {
            background: var(--lp-bg);
            border: 1px solid var(--lp-border);
            color: var(--lp-text);
            border-radius: var(--lp-radius-sm);
            padding: .5rem .8rem;
            font-size: 14px;
            width: 100%;
            transition: border-color .15s, box-shadow .15s;
            font-family: inherit;
        }

        .lp-input:focus {
            outline: none;
            border-color: var(--lp-blue);
            box-shadow: 0 0 0 3px rgba(88,166,255,.15);
            background: var(--lp-bg);
            color: var(--lp-text);
        }

        .lp-input::placeholder { color: var(--lp-text-subtle); }

        .lp-input.is-invalid {
            border-color: var(--lp-red);
        }

        .lp-input.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(248,81,73,.15);
        }

        .lp-textarea {
            resize: vertical;
            min-height: 110px;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 13px;
            line-height: 1.7;
        }

        .lp-file-input {
            background: var(--lp-bg);
            border: 1px dashed var(--lp-border);
            color: var(--lp-text);
            border-radius: var(--lp-radius-sm);
            padding: .75rem 1rem;
            font-size: 13px;
            width: 100%;
            cursor: pointer;
            transition: border-color .15s;
        }

        .lp-file-input:hover { border-color: var(--lp-blue); }

        .lp-invalid-feedback {
            font-size: 12px;
            color: var(--lp-red);
            margin-top: .3rem;
        }

        /* ── Alerts / Toasts ── */
        .lp-alert {
            padding: .85rem 1.1rem;
            border-radius: var(--lp-radius-sm);
            font-size: 13px;
            display: flex;
            align-items: flex-start;
            gap: .6rem;
            margin-bottom: 1.5rem;
        }

        .lp-alert-success {
            background: rgba(35,134,54,.12);
            border: 1px solid rgba(35,134,54,.3);
            color: #3fb950;
        }

        .lp-alert-danger {
            background: rgba(248,81,73,.1);
            border: 1px solid rgba(248,81,73,.3);
            color: var(--lp-red);
        }

        .lp-alert-warning {
            background: rgba(210,153,34,.1);
            border: 1px solid rgba(210,153,34,.3);
            color: var(--lp-orange);
        }

        /* ── Empty State ── */
        .lp-empty {
            text-align: center;
            padding: 5rem 2rem;
            color: var(--lp-text-muted);
        }

        .lp-empty-icon {
            font-size: 3rem;
            color: var(--lp-text-subtle);
            margin-bottom: 1rem;
            display: block;
        }

        .lp-empty h3 {
            font-size: 17px;
            font-weight: 600;
            color: var(--lp-text);
            margin-bottom: .4rem;
        }

        /* ── Badge ── */
        .lp-badge {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .2rem .6rem;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            background: rgba(88,166,255,.1);
            color: var(--lp-blue);
            border: 1px solid rgba(88,166,255,.2);
        }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--lp-bg); }
        ::-webkit-scrollbar-thumb { background: var(--lp-border); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #444c56; }

        /* ── Modal ── */
        .lp-modal .modal-content {
            background: var(--lp-surface);
            border: 1px solid var(--lp-border);
            border-radius: var(--lp-radius);
            color: var(--lp-text);
        }

        .lp-modal .modal-header {
            border-bottom: 1px solid var(--lp-border);
            padding: 1.1rem 1.5rem;
        }

        .lp-modal .modal-body { padding: 1.5rem; }

        .lp-modal .modal-footer {
            border-top: 1px solid var(--lp-border);
            padding: 1rem 1.5rem;
        }

        .lp-modal .btn-close {
            filter: invert(1) grayscale(1) brightness(1.5);
        }

        /* ── Test result ── */
        .lp-test-result {
            background: var(--lp-bg);
            border: 1px solid var(--lp-border);
            border-radius: var(--lp-radius-sm);
            padding: 1rem 1.25rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12.5px;
            line-height: 1.8;
            min-height: 60px;
        }

        .lp-test-result.success { border-color: rgba(35,134,54,.5); }
        .lp-test-result.error   { border-color: rgba(248,81,73,.4); }

        @keyframes lp-spin {
            to { transform: rotate(360deg); }
        }
        .lp-spin { animation: lp-spin .8s linear infinite; display: inline-block; }
    </style>

    @stack('styles')
</head>
<body>

    {{-- Navbar --}}
    <nav class="lp-navbar">
        <a href="{{ route('connections.index') }}" class="lp-brand">
            <div class="lp-brand-icon">
                <i class="bi bi-terminal-fill" style="color:#fff"></i>
            </div>
            Leo<span>Panel</span>
        </a>

        <div class="lp-nav-sep"></div>

        <a href="{{ route('connections.index') }}"
           class="lp-nav-link {{ request()->routeIs('connections.*') ? 'active' : '' }}">
            <i class="bi bi-hdd-network me-1"></i>Servidores
        </a>

        <div class="lp-navbar-right">
            @auth
                @if(auth()->user()->is_admin)
                    <a href="{{ route('users.index') }}"
                       class="lp-nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-1"></i>Usuários
                    </a>
                    <div class="lp-nav-sep"></div>
                @endif

                <span style="color:var(--lp-text-muted);font-size:12px;display:flex;align-items:center;gap:.4rem">
                    <i class="bi bi-person-circle" style="font-size:15px"></i>
                    {{ auth()->user()->name }}
                    @if(auth()->user()->is_admin)
                        <span style="background:rgba(88,166,255,.15);color:var(--lp-blue);font-size:10px;font-weight:600;padding:.1rem .45rem;border-radius:4px;border:1px solid rgba(88,166,255,.25)">ADMIN</span>
                    @endif
                </span>

                <form method="POST" action="{{ route('logout') }}" style="margin:0">
                    @csrf
                    <button type="submit" class="btn-lp-secondary" style="padding:.3rem .7rem;font-size:12px">
                        <i class="bi bi-box-arrow-right"></i> Sair
                    </button>
                </form>
            @endauth
        </div>
    </nav>

    {{-- Main --}}
    <main class="lp-main">

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="lp-alert lp-alert-success">
                <i class="bi bi-check-circle-fill" style="font-size:15px;flex-shrink:0;margin-top:1px"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="lp-alert lp-alert-danger">
                <i class="bi bi-x-circle-fill" style="font-size:15px;flex-shrink:0;margin-top:1px"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @yield('content')
    </main>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    {{-- Axios --}}
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script>
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document
            .querySelector('meta[name="csrf-token"]').getAttribute('content');
    </script>

    @stack('scripts')
</body>
</html>
