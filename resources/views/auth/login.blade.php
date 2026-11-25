<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#070a13">
    <title>Login — TechIA Panel</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/techia-theme.css') }}?v={{ @filemtime(public_path('css/techia-theme.css')) }}">

    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-wrap {
            width: 100%;
            max-width: 400px;
            animation: lp-rise .7s var(--lp-ease) backwards;
        }

        .login-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .85rem;
            margin-bottom: 1.75rem;
            text-decoration: none;
        }
        .login-brand-icon {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #fff;
            background: var(--lp-grad);
            background-size: 200% 200%;
            animation: lp-grad-shift 8s ease infinite, lp-float 5s ease-in-out infinite;
            box-shadow: 0 18px 40px -12px rgba(99,102,241,.9), inset 0 1px 0 rgba(255,255,255,.35);
        }
        .login-brand-name {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -.8px;
            color: #fff;
        }
        .login-brand-name span {
            margin-left: .3rem;
            background: var(--lp-grad);
            -webkit-background-clip: text;
                    background-clip: text;
            color: transparent;
        }

        /* Cartão de vidro com borda em gradiente girando */
        .login-card {
            position: relative;
            padding: 2.1rem 2rem 2rem;
            border-radius: 22px;
            background: linear-gradient(160deg, rgba(255,255,255,.09), rgba(255,255,255,.02) 45%, rgba(255,255,255,.04));
            -webkit-backdrop-filter: blur(22px) saturate(160%);
                    backdrop-filter: blur(22px) saturate(160%);
            box-shadow: 0 30px 80px -30px rgba(0,0,0,.9), inset 0 1px 0 rgba(255,255,255,.14);
        }
        .login-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            padding: 1px;
            background: conic-gradient(from var(--lp-ang), rgba(34,211,238,.7), rgba(99,102,241,.25), rgba(168,85,247,.7), rgba(99,102,241,.25), rgba(34,211,238,.7));
            -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            -webkit-mask-composite: xor;
                    mask-composite: exclude;
            animation: lp-ang-spin 7s linear infinite;
            pointer-events: none;
        }
        @keyframes lp-ang-spin { to { --lp-ang: 360deg; } }

        .login-title { font-size: 19px; font-weight: 700; color: #fff; margin-bottom: .3rem; letter-spacing: -.3px; }
        .login-subtitle { font-size: 13.5px; color: var(--lp-text-muted); margin-bottom: 1.6rem; }

        .login-field { position: relative; }
        .login-field > i {
            position: absolute;
            left: .9rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--lp-text-subtle);
            pointer-events: none;
            transition: color .2s;
        }
        .login-field .lp-input { padding-left: 2.5rem; padding-top: .7rem; padding-bottom: .7rem; }
        .login-field:focus-within > i { color: #a5b4fc; }
        .login-eye {
            position: absolute;
            right: .55rem;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: var(--lp-text-subtle);
            padding: .25rem .4rem;
            border-radius: 8px;
            cursor: pointer;
        }
        .login-eye:hover { color: #fff; background: rgba(255,255,255,.06); }

        .login-remember {
            display: flex;
            align-items: center;
            gap: .55rem;
            font-size: 13px;
            color: var(--lp-text-muted);
            cursor: pointer;
            user-select: none;
        }
        .login-remember input[type=checkbox] { accent-color: #6366f1; width: 15px; height: 15px; cursor: pointer; }

        .btn-login { width: 100%; justify-content: center; padding: .75rem 1rem; font-size: 14px; border-radius: 12px; }

        .login-error {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .75rem 1rem;
            margin-bottom: 1.25rem;
            border-radius: 12px;
            font-size: 13px;
            color: #fca5a5;
            background: rgba(248,113,113,.1);
            border: 1px solid rgba(248,113,113,.3);
            animation: login-shake .45s ease;
        }
        @keyframes login-shake {
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }

        .login-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 12px;
            color: var(--lp-text-subtle);
        }
        .login-footer i { color: #a5b4fc; }
    </style>
</head>
<body>

<div class="lp-aurora" aria-hidden="true"><span></span><span></span><span></span></div>
<div class="lp-grid-bg" aria-hidden="true"></div>

<div class="login-wrap">
    <div class="login-brand">
        <div class="login-brand-icon">
            <i class="bi bi-terminal-fill"></i>
        </div>
        <span class="login-brand-name">TechIA<span>Panel</span></span>
    </div>

    <div class="login-card">
        <h1 class="login-title">Bem-vindo de volta</h1>
        <p class="login-subtitle">Entre para acessar seu workspace de servidores</p>

        @if($errors->any())
            <div class="login-error">
                <i class="bi bi-x-circle-fill" style="flex-shrink:0"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" id="loginForm">
            @csrf

            <div class="mb-3">
                <label class="lp-form-label" for="email">E-mail</label>
                <div class="login-field">
                    <i class="bi bi-envelope"></i>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        autofocus
                        required
                        class="lp-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                        placeholder="seu@email.com"
                    >
                </div>
            </div>

            <div class="mb-3">
                <label class="lp-form-label" for="password">Senha</label>
                <div class="login-field">
                    <i class="bi bi-lock"></i>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required
                        class="lp-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                        placeholder="••••••••"
                        style="padding-right:2.6rem"
                    >
                    <button type="button" class="login-eye" id="togglePass" title="Mostrar senha" aria-label="Mostrar senha">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <label class="login-remember">
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    Manter conectado
                </label>
            </div>

            <button type="submit" class="btn-lp-primary btn-login" id="loginBtn">
                <i class="bi bi-box-arrow-in-right"></i>
                Entrar
            </button>
        </form>
    </div>

    <div class="login-footer">
        <i class="bi bi-shield-lock-fill"></i> TechIA Panel &mdash; Painel SSH privado
    </div>
</div>

<script>
    document.getElementById('togglePass').addEventListener('click', function () {
        const input = document.getElementById('password');
        const show  = input.type === 'password';
        input.type  = show ? 'text' : 'password';
        this.innerHTML = `<i class="bi ${show ? 'bi-eye-slash' : 'bi-eye'}"></i>`;
        this.title = show ? 'Esconder senha' : 'Mostrar senha';
    });

    document.getElementById('loginForm').addEventListener('submit', () => {
        const btn = document.getElementById('loginBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat lp-spin"></i> Entrando…';
    });
</script>

</body>
</html>
