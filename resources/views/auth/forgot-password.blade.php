<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        :root {
            --bg: {{ $appProfile['theme_background'] ?? '#fff7ed' }};
            --surface: #ffffff;
            --accent: {{ $appProfile['theme_primary'] ?? '#ea580c' }};
            --line: {{ $appProfile['theme_primary'] ?? '#ea580c' }};
            --accent-soft: color-mix(in srgb, {{ $appProfile['theme_primary'] ?? '#ea580c' }} 12%, #ffffff);
            --accent-text: {{ $appProfile['theme_primary'] ?? '#ea580c' }};
            --text: #1f2937;
        }
        body { font-family: "Segoe UI", Tahoma, sans-serif; background: var(--bg); padding: 20px; color: var(--text); }
        .card { max-width: 520px; margin: 20px auto; background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 16px; }
        label { display: block; margin-top: 8px; font-weight: 600; }
        input { width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 8px; box-sizing: border-box; }
        .btn { margin-top: 10px; background: var(--accent); color: #fff; border: 0; padding: 10px; border-radius: 8px; cursor: pointer; }
        .btn:hover { filter: brightness(0.96); }
        .ok { color: #166534; }
        a { color: var(--accent-text); }
    </style>
</head>
<body>
    <div class="card">
        <h3>Reset Password via Email</h3>
        <p>Enter your account email, we will send a password reset link.</p>
        @if (session('success'))
            <p class="ok">{{ session('success') }}</p>
        @endif
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" type="email" name="email" required>
            <button class="btn" type="submit">Send Reset Link</button>
        </form>
    </div>
    <p style="text-align:center;"><a href="{{ route('login') }}">Back to login</a></p>
</body>
</html>
