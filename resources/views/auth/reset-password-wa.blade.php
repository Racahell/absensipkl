<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password WhatsApp</title>
    <style>
        body{font-family:Segoe UI,Tahoma,sans-serif;background:#fff7ed;padding:20px}
        .card{max-width:480px;margin:20px auto;background:#fff;border:1px solid #fdba74;border-radius:12px;padding:18px}
        label{display:block;margin-top:8px}
        input{width:100%;padding:10px;border:1px solid #fdba74;border-radius:8px}
        .btn{margin-top:10px;background:#ea580c;color:#fff;border:0;padding:10px;border-radius:8px;cursor:pointer}
    </style>
</head>
<body>
    <div class="card">
        <h3>Reset Password via WhatsApp</h3>
        @if(session('success'))
            <p style="color:#166534;">{{ session('success') }}</p>
        @endif
        <form method="POST" action="{{ route('password.whatsapp.update') }}">
            @csrf
            <label>WhatsApp Number</label>
            <input name="phone" inputmode="numeric" pattern="[0-9]*" placeholder="62812xxxx" required>
            <label>WhatsApp Code</label>
            <input name="code" required>
            <label>New Password</label>
            <input type="password" name="password" required>
            <label>Confirm Password</label>
            <input type="password" name="password_confirmation" required>
            @if($errors->any())
                <p style="color:#b91c1c;">{{ $errors->first() }}</p>
            @endif
            <button class="btn" type="submit">Update Password</button>
        </form>
        <p style="margin-top:10px;"><a href="{{ route('password.request') }}">Back to forgot password</a></p>
    </div>
    <script>
        document.querySelectorAll('input[name="phone"]').forEach((input) => {
            const sanitize = () => {
                input.value = (input.value || '').replace(/\D+/g, '');
            };
            input.addEventListener('input', sanitize);
            input.addEventListener('blur', sanitize);
        });
    </script>
</body>
</html>
