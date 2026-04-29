<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login OTP | Absensi PKL</title>
    <style>
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f5ede4;font-family:Segoe UI,Tahoma,sans-serif;padding:16px}
        .card{width:min(420px,96vw);background:#fff;border:1px solid #fdba74;border-radius:14px;padding:18px}
        h1{margin:0 0 8px;font-size:22px;color:#9a3412}
        p{margin:0 0 10px;color:#6b7280}
        label{display:block;margin:10px 0 6px;font-weight:600}
        input{width:100%;border:1px solid #fdba74;border-radius:8px;padding:10px;box-sizing:border-box}
        .btn{width:100%;border:1px solid #ea580c;background:#ea580c;color:#fff;border-radius:8px;padding:10px;font-weight:700;cursor:pointer}
        .btn-ghost{width:100%;border:1px solid #fdba74;background:#fff;color:#9a3412;border-radius:8px;padding:10px;font-weight:700;cursor:pointer}
        .row{display:grid;gap:8px}
        .msg{margin:8px 0;padding:10px;border-radius:8px;font-size:14px}
        .ok{border:1px solid #bbf7d0;background:#f0fdf4;color:#166534}
        .err{border:1px solid #fecaca;background:#fff1f2;color:#b91c1c}
        .step{margin-top:12px;border:1px dashed #fdba74;border-radius:10px;padding:10px}
        .step-title{margin:0 0 8px;color:#9a3412;font-weight:700;font-size:14px}
        a{color:#9a3412;text-decoration:none}
    </style>
</head>
<body>
@php
    $showVerifyStep = request()->boolean('sent') || $errors->has('otp_code') || old('otp_code');
@endphp
<div class="card">
    <h1>Login OTP</h1>
    <p>Masukkan email terdaftar, kirim OTP, lalu verifikasi.</p>

    @if(session('success'))<div class="msg ok">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="msg err">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login.otp.send') }}" class="row step" id="send-step">
        @csrf
        <p class="step-title">Langkah 1: Kirim OTP Email</p>
        <input type="hidden" name="channel" id="channel-send" value="email">
        <label for="contact-send" id="label-send">Email</label>
        <input id="contact-send" name="contact" value="{{ old('contact', $contact ?? '') }}" required>
        <button class="btn-ghost" type="submit">Kirim OTP</button>
    </form>

    <form method="POST" action="{{ route('login.otp.verify') }}" class="row step" id="verify-step" style="{{ $showVerifyStep ? '' : 'display:none;' }}">
        @csrf
        <p class="step-title">Langkah 2: Verifikasi OTP Email</p>
        <input type="hidden" name="channel" id="channel-verify" value="email">
        <label for="contact-verify" id="label-verify">Email</label>
        <input id="contact-verify" name="contact" value="{{ old('contact', $contact ?? '') }}" required>
        <label for="otp_code">Kode OTP</label>
        <input id="otp_code" name="otp_code" inputmode="numeric" maxlength="6" required>
        <button class="btn" type="submit">Masuk Dengan OTP</button>
    </form>

    <p style="margin-top:12px;"><a href="{{ route('login') }}">Kembali ke login password</a></p>
</div>
</body>
</html>
