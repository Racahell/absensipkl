<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email</title>
    <style>
        body { font-family: Segoe UI, Tahoma, sans-serif; background: #fff7ed; padding: 20px; }
        .card { max-width: 520px; margin: 20px auto; background: #fff; border: 1px solid #fdba74; border-radius: 12px; padding: 20px; }
        .btn { background: #ea580c; color: #fff; border: 0; padding: 10px 12px; border-radius: 8px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Aktivasi Email</h2>
        <p>Akun belum aktif. Klik link di email Anda. Jika belum masuk, kirim ulang.</p>

        @if (session('success'))
            <p style="color:#166534;">{{ session('success') }}</p>
        @endif

        @if (session('error'))
            <p style="color:#b91c1c;">{{ session('error') }}</p>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="btn" type="submit">Kirim Ulang Link Verifikasi</button>
        </form>

        <p style="margin-top:10px;">
            <a href="{{ route('login') }}">Kembali ke Login</a>
        </p>
    </div>
</body>
</html>
