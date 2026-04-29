<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mandatory Password Reset | {{ config('app.name', 'Absensi PKL') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('image/download.png') }}">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #fff7ed;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            padding: 16px;
        }
        .card {
            width: min(420px, 96vw);
            background: #fff;
            border: 1px solid #fdba74;
            border-radius: 14px;
            padding: 22px;
        }
        h1 { margin: 0 0 8px; font-size: 22px; color: #9a3412; }
        p { margin: 0 0 12px; color: #6b7280; }
        label { display: block; margin: 10px 0 6px; font-weight: 600; }
        input {
            width: 100%;
            border: 1px solid #fdba74;
            border-radius: 8px;
            padding: 9px 10px;
            box-sizing: border-box;
        }
        .btn {
            margin-top: 14px;
            width: 100%;
            border: 1px solid #ea580c;
            background: #ea580c;
            color: #fff;
            border-radius: 8px;
            padding: 10px;
            font-weight: 600;
            cursor: pointer;
        }
        .error {
            margin-bottom: 10px;
            border: 1px solid #fecaca;
            background: #fff1f2;
            color: #b91c1c;
            border-radius: 8px;
            padding: 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="card">
    <h1>Reset Password</h1>
    <p>
        @if($mustReset)
            For security, the default password must be changed before continuing.
        @else
            Please change your account password.
        @endif
    </p>

    @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.reset.update') }}">
        @csrf

        <label for="current_password">Current Password</label>
        <input id="current_password" type="password" name="current_password" required>

        <label for="password">New Password</label>
        <input id="password" type="password" name="password" minlength="8" required>

        <label for="password_confirmation">Confirm New Password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" required>

        <button class="btn" type="submit">Save New Password</button>
    </form>
</div>
</body>
</html>
