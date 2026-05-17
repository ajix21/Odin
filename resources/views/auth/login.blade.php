<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Project ODIN</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0C1B33 0%, #0F2857 40%, #1B3F8A 100%);
            padding: 24px;
        }
        .login-wrap {
            width: 100%;
            max-width: 400px;
            animation: fadeUp .4s ease;
        }
        .login-brand {
            text-align: center;
            margin-bottom: 28px;
        }
        .login-brand img {
            width: 72px; height: 72px;
            object-fit: contain;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(14,165,233,0.3);
            margin-bottom: 14px;
        }
        .login-brand h1 {
            font-family: 'Sora', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: #fff;
            letter-spacing: 4px;
        }
        .login-brand p {
            color: rgba(255,255,255,0.5);
            font-size: 12px;
            letter-spacing: 1px;
            margin-top: 4px;
        }
        .login-card {
            background: rgba(255,255,255,0.97);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.35);
        }
        .login-card h2 {
            font-size: 17px;
            font-weight: 700;
            color: var(--c-text);
            margin-bottom: 20px;
        }
        .password-wrap { position: relative; }
        .password-toggle {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            cursor: pointer; color: var(--c-text-3);
            font-size: 15px; padding: 0;
        }
        .remember-row {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: var(--c-text-2);
            margin-bottom: 18px;
        }
        .login-footer {
            text-align: center; margin-top: 20px;
            font-size: 11.5px; color: rgba(255,255,255,0.35);
        }
    </style>
</head>
<body>
<div class="login-wrap">

    <div class="login-brand">
        <img src="{{ asset('icon.png') }}" alt="ODIN">
        <h1>ODIN</h1>
        <p>OPEN SOURCE INTELLIGENCE PLATFORM</p>
    </div>

    <div class="login-card">
        <h2>Masuk ke Sistem</h2>

        @if($errors->any())
        <div class="alert alert-error mb-4">
            <span>⚠</span>
            <div>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
        @endif

        <form method="POST" action="/login">
            @csrf

            <div class="form-group">
                <label class="form-label" for="username">Username atau Email</label>
                <input type="text" id="username" name="username"
                    class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}"
                    value="{{ old('username') }}"
                    placeholder="Masukkan username atau email"
                    autocomplete="username" autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password"
                        class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                        placeholder="Masukkan password"
                        autocomplete="current-password">
                    <button type="button" class="password-toggle" onclick="togglePassword()">👁</button>
                </div>
            </div>

            <label class="remember-row">
                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                Ingat saya
            </label>

            <button type="submit" class="btn btn-primary btn-full">
                🔐 Masuk ke ODIN
            </button>
        </form>
    </div>

    <p class="login-footer">Project ODIN &mdash; Platform OSINT Terpadu</p>
</div>

<script>
function togglePassword() {
    const p = document.getElementById('password');
    p.type = p.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
