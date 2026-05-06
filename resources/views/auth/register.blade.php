<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | {{ config('app.name', 'Absensi PKL') }}</title>
    <style>body{font-family:Segoe UI,Tahoma,sans-serif;background:#fff7ed;padding:20px}.card{max-width:460px;margin:20px auto;background:#fff;border:1px solid #fdba74;border-radius:12px;padding:20px}label{display:block;margin-top:10px;font-weight:600}input{width:100%;padding:10px;border:1px solid #fdba74;border-radius:8px}.btn{margin-top:14px;background:#ea580c;color:#fff;border:0;padding:10px 12px;border-radius:8px;cursor:pointer}.err{color:#b91c1c;font-size:13px}.password-wrap{position:relative}.password-wrap input{padding-right:44px}.toggle-pass{position:absolute;right:8px;top:50%;transform:translateY(-50%);border:0;background:transparent;cursor:pointer;padding:4px;display:inline-flex;align-items:center;justify-content:center}.toggle-pass img{width:20px;height:20px;display:block}</style>
</head>
<body>
<div class="card">
    <h2>Registrasi Siswa</h2>
    <p>Setelah daftar, akun wajib aktivasi email sebelum login.</p>
    <form method="POST" action="{{ route('register.store') }}">
        @csrf
        <label>Nama</label><input name="name" value="{{ old('name') }}" required>
        <label>NIS</label><input name="nis" value="{{ old('nis') }}" inputmode="numeric" pattern="[0-9]*" required>
        <label>Email</label><input type="email" name="email" value="{{ old('email') }}" required>
        <label>No WhatsApp</label><input name="phone" value="{{ old('phone') }}" inputmode="numeric" pattern="[0-9]*" placeholder="62812xxxx">
        <label>Password</label>
        <div class="password-wrap">
            <input type="password" id="register_password" name="password" required>
            <button type="button" class="toggle-pass" data-target="register_password" aria-label="Tampilkan password" data-show="{{ asset('icons/eye.png') }}" data-hide="{{ asset('icons/invisible.png') }}">
                <img src="{{ asset('icons/eye.png') }}" alt="Toggle Password">
            </button>
        </div>
        <label>Konfirmasi Password</label>
        <div class="password-wrap">
            <input type="password" id="register_password_confirmation" name="password_confirmation" required>
            <button type="button" class="toggle-pass" data-target="register_password_confirmation" aria-label="Tampilkan password" data-show="{{ asset('icons/eye.png') }}" data-hide="{{ asset('icons/invisible.png') }}">
                <img src="{{ asset('icons/eye.png') }}" alt="Toggle Password">
            </button>
        </div>
        @if ($errors->any())<div class="err">{{ $errors->first() }}</div>@endif
        <button class="btn" type="submit">Daftar</button>
    </form>
    <p><a href="{{ route('login') }}">Kembali ke login</a></p>
</div>
<script>
    document.querySelectorAll('.toggle-pass').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.getElementById(button.dataset.target);
            if (!target) return;

            const isPassword = target.type === 'password';
            target.type = isPassword ? 'text' : 'password';
            const icon = button.querySelector('img');
            if (icon) {
                icon.src = isPassword ? button.dataset.hide : button.dataset.show;
            }
            button.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Tampilkan password');
        });
    });

    document.querySelectorAll('input[name="nis"], input[name="phone"]').forEach((input) => {
        const sanitize = () => {
            input.value = (input.value || '').replace(/\D+/g, '');
        };
        input.addEventListener('input', sanitize);
        input.addEventListener('blur', sanitize);
    });
</script>
</body>
</html>
