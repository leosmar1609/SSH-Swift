<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — LeoPanel</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --lp-bg:         #0d1117;
            --lp-surface:    #161b22;
            --lp-border:     #30363d;
            --lp-text:       #e6edf3;
            --lp-text-muted: #8b949e;
            --lp-text-subtle:#6e7681;
            --lp-blue:       #58a6ff;
            --lp-blue-dim:   #1f6feb;
            --lp-accent:     #238636;
            --lp-accent-hover:#2ea043;
            --lp-red:        #f85149;
        }

        * { box-sizing: border-box; }

        body {
            background: var(--lp-bg);
            color: var(--lp-text);
            font-family: 'Inter', -apple-system, sans-serif;
            font-size: 14px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-wrap {
            width: 100%;
            max-width: 380px;
            padding: 1.5rem;
        }

        .login-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            margin-bottom: 2rem;
            text-decoration: none;
        }

        .login-brand-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--lp-blue-dim), var(--lp-blue));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .login-brand-name {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -.4px;
            color: var(--lp-text);
        }

        .login-brand-name span { color: var(--lp-blue); }

        .login-card {
            background: var(--lp-surface);
            border: 1px solid var(--lp-border);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 24px rgba(0,0,0,.4);
        }

        .login-title {
            font-size: 17px;
            font-weight: 600;
            color: var(--lp-text);
            margin-bottom: .35rem;
        }

        .login-subtitle {
            font-size: 13px;
            color: var(--lp-text-muted);
            margin-bottom: 1.75rem;
        }

        .lp-form-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--lp-text);
            margin-bottom: .35rem;
            display: block;
        }

        .lp-input {
            background: var(--lp-bg);
            border: 1px solid var(--lp-border);
            color: var(--lp-text);
            border-radius: 7px;
            padding: .55rem .85rem;
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

        .lp-input.is-invalid { border-color: var(--lp-red); }

        .lp-invalid-feedback {
            font-size: 12px;
            color: var(--lp-red);
            margin-top: .3rem;
        }

        .btn-login {
            background: var(--lp-accent);
            color: #fff;
            border: none;
            font-weight: 600;
            font-size: 14px;
            padding: .6rem 1rem;
            border-radius: 7px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            transition: background .15s, box-shadow .15s;
            cursor: pointer;
            margin-top: .25rem;
        }

        .btn-login:hover {
            background: var(--lp-accent-hover);
            box-shadow: 0 0 0 3px rgba(35,134,54,.25);
        }

        .login-remember {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: 13px;
            color: var(--lp-text-muted);
            cursor: pointer;
        }

        .login-remember input[type=checkbox] {
            accent-color: var(--lp-blue);
            width: 15px;
            height: 15px;
            cursor: pointer;
        }

        .login-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 12px;
            color: var(--lp-text-subtle);
        }
    </style>
</head>
<body>

<div class="login-wrap">
    <a href="#" class="login-brand">
        <div class="login-brand-icon">
            <i class="bi bi-terminal-fill" style="color:#fff"></i>
        </div>
        <span class="login-brand-name">Leo<span>Panel</span></span>
    </a>

    <div class="login-card">
        <h1 class="login-title">Entrar</h1>
        <p class="login-subtitle">Acesse seu workspace de servidores</p>

        @if($errors->any())
            <div style="background:rgba(248,81,73,.1);border:1px solid rgba(248,81,73,.3);color:var(--lp-red);border-radius:7px;padding:.75rem 1rem;font-size:13px;margin-bottom:1.25rem;display:flex;align-items:center;gap:.5rem">
                <i class="bi bi-x-circle-fill" style="flex-shrink:0"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf

            <div class="mb-3">
                <label class="lp-form-label" for="email">E-mail</label>
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

            <div class="mb-3">
                <label class="lp-form-label" for="password">Senha</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    required
                    class="lp-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                    placeholder="••••••••"
                >
            </div>

            <div class="mb-4">
                <label class="login-remember">
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    Manter conectado
                </label>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i>
                Entrar
            </button>
        </form>
    </div>

    <div class="login-footer">
        LeoPanel &mdash; Painel SSH privado
    </div>
</div>

</body>
</html>
