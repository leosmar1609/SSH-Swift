<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#070a13">
    <title>@yield('title', 'TechIA Panel') — TechIA Panel</title>

    {{-- Bootstrap 5.3 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    {{-- Inter font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    {{-- Tema TechIA (vidro + aurora) --}}
    <link rel="stylesheet" href="{{ asset('css/techia-theme.css') }}?v={{ @filemtime(public_path('css/techia-theme.css')) }}">

    @stack('styles')
</head>
<body>

    {{-- Fundo animado --}}
    <div class="lp-aurora" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="lp-grid-bg" aria-hidden="true"></div>

    {{-- Navbar --}}
    <nav class="lp-navbar">
        <a href="{{ route('connections.index') }}" class="lp-brand" title="TechIA Panel">
            <div class="lp-brand-icon">
                <i class="bi bi-terminal-fill"></i>
            </div>
            <span class="lp-brand-text">TechIA<span>Panel</span></span>
        </a>

        <div class="lp-nav-sep"></div>

        <div class="lp-nav-links">
            <a href="{{ route('connections.index') }}"
               class="lp-nav-link {{ request()->routeIs('connections.*') ? 'active' : '' }}">
                <i class="bi bi-hdd-network me-1"></i>Servidores
            </a>

            <a href="{{ route('docs.index') }}"
               class="lp-nav-link {{ request()->routeIs('docs.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-pdf me-1"></i>Docs
            </a>

            <a href="{{ route('logguide.index') }}"
               class="lp-nav-link {{ request()->routeIs('logguide.*') ? 'active' : '' }}">
                <i class="bi bi-journal-code me-1"></i>Guia do Log
            </a>

            <a href="{{ route('httpclient.index') }}" class="lp-nav-link" target="_blank" rel="noopener">
                <i class="bi bi-send me-1"></i>HTTP
            </a>

            <a href="{{ route('jsonformatter.index') }}" class="lp-nav-link" target="_blank" rel="noopener">
                <i class="bi bi-braces me-1"></i>JSON
            </a>

            <a href="{{ route('xmlformatter.index') }}" class="lp-nav-link" target="_blank" rel="noopener">
                <i class="bi bi-code-slash me-1"></i>XML
            </a>

            @auth
                @if(auth()->user()->is_admin)
                    <a href="{{ route('users.index') }}"
                       class="lp-nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-1"></i>Usuários
                    </a>
                @endif
            @endauth
        </div>

        <div class="lp-navbar-right">
            @auth
                <span class="lp-user">
                    <span class="lp-user-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="lp-user-name">{{ auth()->user()->name }}</span>
                    @if(auth()->user()->is_admin)
                        <span class="lp-admin-badge">ADMIN</span>
                    @endif
                </span>

                <form method="POST" action="{{ route('logout') }}" style="margin:0">
                    @csrf
                    <button type="submit" class="btn-lp-secondary" style="padding:.35rem .75rem;font-size:12px" title="Sair">
                        <i class="bi bi-box-arrow-right"></i><span class="lp-user-name">Sair</span>
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
