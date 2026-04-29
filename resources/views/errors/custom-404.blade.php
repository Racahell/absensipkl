<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error {{ $status ?? 404 }}</title>
    <style>
        body { margin:0; font-family:"Segoe UI",Tahoma,Geneva,Verdana,sans-serif; background:#fff7ed; color:#7c2d12; }
        .wrap { min-height:100vh; display:grid; place-items:center; padding:16px; }
        .card { width:min(520px,95vw); background:#fff; border:1px solid #fdba74; border-radius:16px; padding:24px; text-align:center; }
        h1 { margin:0; font-size:42px; color:#ea580c; }
        p { margin:8px 0 0; color:#9a3412; }
        a { display:inline-block; margin-top:14px; text-decoration:none; color:#fff; background:#ea580c; border:1px solid #ea580c; border-radius:8px; padding:9px 12px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>{{ $status ?? 404 }}</h1>
        <p>Halaman tidak ditemukan atau terjadi kesalahan pada sistem.</p>
        <a href="{{ url('/dashboard') }}">Kembali ke Dashboard</a>
    </div>
</div>
</body>
</html>
