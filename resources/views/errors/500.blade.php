<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 | Terjadi Kesalahan</title>
    <style>
        body { margin:0; min-height:100vh; display:grid; place-items:center; background:linear-gradient(135deg,#fff7ed,#ffedd5); font-family:"Segoe UI",Tahoma,sans-serif; color:#7c2d12; }
        .box { text-align:center; background:#fff; border:1px solid #fdba74; border-radius:14px; padding:28px; width:min(560px,92vw); box-shadow:0 14px 30px rgba(154,52,18,.16); }
        h1 { margin:0; font-size:56px; line-height:1; }
        p { color:#9a3412; }
        a { display:inline-block; margin-top:12px; text-decoration:none; background:#ea580c; color:#fff; padding:10px 14px; border-radius:8px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>500</h1>
        <h2>Terjadi Kesalahan Sistem</h2>
        <p>Terjadi gangguan saat memproses permintaan Anda. Silakan coba lagi.</p>
        <a href="{{ route('dashboard') }}">Kembali ke Dashboard</a>
    </div>
</body>
</html>
