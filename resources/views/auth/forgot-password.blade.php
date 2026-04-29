<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        body { font-family: "Segoe UI", Tahoma, sans-serif; background: #fff7ed; padding: 20px; }
        .card { max-width: 520px; margin: 20px auto; background: #fff; border: 1px solid #fdba74; border-radius: 12px; padding: 16px; }
        label { display: block; margin-top: 8px; font-weight: 600; }
        input { width: 100%; padding: 10px; border: 1px solid #fdba74; border-radius: 8px; box-sizing: border-box; }
        .btn { margin-top: 10px; background: #ea580c; color: #fff; border: 0; padding: 10px; border-radius: 8px; cursor: pointer; }
        .ok { color: #166534; }
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
