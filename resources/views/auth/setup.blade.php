<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Keamanan Akun | Absensi PKL</title>
    <style>
        body{margin:0;min-height:100vh;background:#f5ede4;font-family:Segoe UI,Tahoma,sans-serif;padding:18px}
        .wrap{max-width:860px;margin:0 auto}
        .card{background:#fff;border:1px solid #fdba74;border-radius:14px;padding:16px;margin-bottom:12px}
        h1{margin:0 0 10px;color:#9a3412}
        h2{margin:0 0 10px;font-size:18px;color:#9a3412}
        p{margin:0 0 8px;color:#6b7280}
        label{display:block;margin:8px 0 4px;font-weight:600}
        input{width:100%;border:1px solid #fdba74;border-radius:8px;padding:10px;box-sizing:border-box}
        .btn{border:1px solid #ea580c;background:#ea580c;color:#fff;border-radius:8px;padding:9px 12px;font-weight:700;cursor:pointer}
        .btn-ghost{border:1px solid #fdba74;background:#fff;color:#9a3412;border-radius:8px;padding:9px 12px;font-weight:700;cursor:pointer}
        .row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        .msg{margin:8px 0;padding:10px;border-radius:8px;font-size:14px}
        .ok{border:1px solid #bbf7d0;background:#f0fdf4;color:#166534}
        .err{border:1px solid #fecaca;background:#fff1f2;color:#b91c1c}
        .tag{display:inline-block;border:1px solid #fdba74;background:#fffaf5;color:#9a3412;padding:4px 10px;border-radius:999px;font-size:13px;font-weight:700}
        @media (max-width:760px){.row{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Setup Keamanan Akun</h1>
        <p>Lengkapi verifikasi akun dulu. Setelah selesai, Anda bisa lanjut ke dashboard.</p>
        <p>
            Status Email:
            <span class="tag">{{ $user->email_verified_at ? 'Terverifikasi' : 'Belum' }}</span>
            &nbsp; Status HP:
            <span class="tag">{{ $user->phone_verified_at ? 'Terverifikasi' : 'Belum' }}</span>
        </p>
        <p>
            Google:
            <span class="tag">{{ $googleLinked ? 'Terhubung' : 'Belum' }}</span>
            &nbsp; OTP Login:
            <span class="tag">{{ $otpActive ? 'Aktif' : 'Nonaktif' }}</span>
        </p>
    </div>

    @if(session('success'))<div class="msg ok">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="msg err">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="card">
        <h2>1) Konfirmasi Kontak</h2>
        <form method="POST" action="{{ route('auth.setup.contact') }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div>
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                </div>
                <div>
                    <label>Nomor HP</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" required>
                </div>
            </div>
            <button class="btn" type="submit" style="margin-top:10px;">Simpan Kontak</button>
        </form>
    </div>

    <div class="card">
        <h2>2) Verifikasi Email & HP</h2>
        <div class="row">
            <form method="POST" action="{{ route('auth.setup.email.send') }}">
                @csrf
                <button class="btn-ghost" type="submit">Kirim OTP Email</button>
            </form>
            <form method="POST" action="{{ route('auth.setup.phone.send') }}">
                @csrf
                <button class="btn-ghost" type="submit">Kirim OTP Nomor HP</button>
            </form>
        </div>
        <div class="row" style="margin-top:10px;">
            <form method="POST" action="{{ route('auth.setup.email.verify') }}">
                @csrf
                <label>OTP Email</label>
                <input name="email_otp" maxlength="6" required>
                <button class="btn" type="submit" style="margin-top:8px;">Verifikasi Email</button>
            </form>
            <form method="POST" action="{{ route('auth.setup.phone.verify') }}">
                @csrf
                <label>OTP Nomor HP</label>
                <input name="phone_otp" maxlength="6" required>
                <button class="btn" type="submit" style="margin-top:8px;">Verifikasi Nomor HP</button>
            </form>
        </div>
    </div>

    <div class="card">
        <h2>3) Metode Login Tambahan</h2>
        <div class="row">
            <form method="GET" action="{{ route('auth.google.link') }}">
                <button class="btn-ghost" type="submit">Hubungkan Google</button>
            </form>
            <form method="POST" action="{{ route('auth.google.unlink') }}">
                @csrf
                <button class="btn-ghost" type="submit">Lepas Google</button>
            </form>
        </div>
        <form method="POST" action="{{ route('auth.setup.otp.toggle') }}" style="margin-top:10px;">
            @csrf
            <input type="hidden" name="enable" value="{{ $otpActive ? 0 : 1 }}">
            <button class="btn" type="submit">{{ $otpActive ? 'Nonaktifkan Login OTP' : 'Aktifkan Login OTP' }}</button>
        </form>
    </div>

    @if($user->email_verified_at && $user->phone_verified_at)
        <div class="card">
            <form method="GET" action="{{ route('dashboard') }}">
                <button class="btn" type="submit">Lanjut ke Dashboard</button>
            </form>
        </div>
    @endif
</div>
</body>
</html>

