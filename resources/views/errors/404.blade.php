<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 | Halaman Tidak Ditemukan</title>
    <style>
        body { margin:0; min-height:100vh; display:grid; place-items:center; background:linear-gradient(135deg,#fff7ed,#ffedd5); font-family:"Segoe UI",Tahoma,sans-serif; color:#7c2d12; }
        .box { text-align:center; background:#fff; border:1px solid #fdba74; border-radius:14px; padding:28px; width:min(520px,92vw); box-shadow:0 14px 30px rgba(154,52,18,.16); }
        h1 { margin:0; font-size:56px; line-height:1; }
        p { color:#9a3412; }
        a { display:inline-block; margin-top:12px; text-decoration:none; background:#ea580c; color:#fff; padding:10px 14px; border-radius:8px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>404</h1>
        <h2>Halaman Tidak Ditemukan</h2>
        <p>URL yang Anda akses tidak tersedia atau sudah dipindahkan.</p>
        <a href="{{ route('dashboard') }}">Kembali ke Dashboard</a>
    </div>
</body>
</html>
