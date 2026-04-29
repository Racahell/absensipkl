<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $defaultLogoPath = 'image/download.png';
        $configuredLogoPath = trim((string) ($appProfile['logo'] ?? ''));
        $configuredLogoPath = $configuredLogoPath !== '' ? ltrim($configuredLogoPath, '/') : $defaultLogoPath;
        $isExternalLogo = str_starts_with($configuredLogoPath, 'http://') || str_starts_with($configuredLogoPath, 'https://');
        $logoPath = $configuredLogoPath;
        $logoExists = $isExternalLogo || is_file(public_path($configuredLogoPath));
        if (! $logoExists && is_file(public_path($defaultLogoPath))) {
            $logoPath = $defaultLogoPath;
            $logoExists = true;
        }

        $faviconPath = trim((string) ($appProfile['favicon'] ?? ''));
        $faviconPath = $faviconPath !== '' ? ltrim($faviconPath, '/') : $logoPath;
        $isExternalFavicon = str_starts_with($faviconPath, 'http://') || str_starts_with($faviconPath, 'https://');
        if (! $isExternalFavicon && ! is_file(public_path($faviconPath))) {
            $faviconPath = 'favicon.ico';
        }

        $appName = $appProfile['name'] ?? config('app.name', 'Absensi PKL');
    @endphp
    <title>Login | {{ $appName }}</title>
    <link rel="icon" type="image/png" href="{{ asset($faviconPath) }}">
    <style>
        :root {
            --bg: #f5ede4;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --primary: #ea580c;
            --primary-strong: #c2410c;
            --border: #fed7aa;
            --input: #fffaf5;
            --danger: #b91c1c;
            --success: #166534;
            --ring: rgba(234, 88, 12, 0.2);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 24px 22px 26px;
            background:
                radial-gradient(circle at top right, #ffe9cf 0%, transparent 45%),
                radial-gradient(circle at left bottom, #fdd9b5 0%, transparent 38%),
                var(--bg);
            color: var(--text);
            font-family: "Segoe UI", Tahoma, sans-serif;
        }

        .login-page {
            max-width: 1100px;
            margin: 0 auto;
            min-height: calc(100vh - 110px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .login-page-tools {
            width: min(520px, 100%);
            display: block;
            margin: 0;
        }

        .login-tools-card {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: var(--card);
            padding: 10px 12px;
            box-shadow: 0 8px 20px rgba(124, 45, 18, 0.1);
        }

        .login-tools-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .wrap {
            width: min(520px, 100%);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 30px 30px 26px;
            box-shadow: 0 18px 40px rgba(124, 45, 18, 0.16);
        }

        .login-tools {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 14px;
            margin-bottom: 0;
        }

        .radio-inputs {
            display: flex;
            border-radius: 10px;
            background: #fff7ed;
            border: 1px solid #fdba74;
            padding: 3px;
            width: 132px;
            height: 40px;
        }

        .radio-inputs .radio,
        .radio-inputs label.radio {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            flex: 1 1 auto;
        }

        .radio-inputs .radio input {
            display: none;
        }

        .radio-inputs .radio .radio-item {
            display: flex;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: none;
            padding: 0.45rem 0.35rem;
            color: #9a3412;
            font-weight: 700;
            transition: background-color .25s ease, color .25s ease;
            letter-spacing: .04em;
        }

        .radio-inputs .radio input:checked + .radio-item {
            background-color: #ea580c;
            color: #fff;
        }

        .theme-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #9a3412;
            font-size: 13px;
            font-weight: 700;
        }

        .switch {
            font-size: 14px;
            position: relative;
            display: inline-block;
            width: 3em;
            height: 1.7em;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 1.15em;
            width: 1.15em;
            left: 0.25em;
            bottom: 0.23em;
            background-color: #ffffff;
            border-radius: 50px;
            box-shadow: 0 0px 12px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .switch input:checked + .slider {
            background: #111827;
            border-color: #111827;
        }

        .switch input:checked + .slider:before {
            transform: translateX(1.35em);
        }

        .brand { text-align: center; margin-bottom: 18px; }
        .brand img { width: 270px; height: 106px; object-fit: contain; }
        .brand-name {
            margin: 0;
            font-size: 34px;
            line-height: 1.05;
            font-weight: 800;
            color: #7c2d12;
            letter-spacing: 0.2px;
            text-transform: uppercase;
        }
        .subtitle { margin: 0; color: var(--muted); font-size: 18px; }

        .msg {
            margin: 10px 0;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 13px;
        }

        .err { color: var(--danger); background: #fef2f2; border: 1px solid #fecaca; }
        .ok { color: var(--success); background: #f0fdf4; border: 1px solid #bbf7d0; }

        form { margin-top: 14px; }

        label {
            display: block;
            margin: 14px 0 7px;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0;
            color: #111827;
        }

        input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px;
            font-size: 16px;
            background: var(--input);
            color: var(--text);
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--ring);
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap input {
            padding-right: 46px;
        }

        .toggle-pass {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            cursor: pointer;
            padding: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-pass img {
            width: 20px;
            height: 20px;
            display: block;
        }

        .verify-box {
            margin-top: 14px;
            border: 1px dashed var(--border);
            background: #fffaf5;
            border-radius: 12px;
            padding: 12px;
        }

        .verify-note {
            margin: 0 0 10px;
            color: #9a3412;
            font-weight: 600;
            font-size: 15px;
        }

        .btn {
            width: 100%;
            margin-top: 16px;
            border: 0;
            border-radius: 10px;
            padding: 12px;
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(180deg, var(--primary), var(--primary-strong));
            cursor: pointer;
        }

        .btn:hover { filter: brightness(1.03); }

        .links {
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            font-size: 13px;
        }

        .links a { color: #9a3412; text-decoration: none; }
        .links a:hover { text-decoration: underline; }

        .google-btn {
            margin-top: 10px;
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 11px 12px;
            background: #fff;
            color: #111827;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            transition: box-shadow .15s ease, border-color .15s ease, background .15s ease;
        }

        .google-btn:hover {
            border-color: #9ca3af;
            background: #f9fafb;
            box-shadow: 0 4px 14px rgba(17, 24, 39, 0.08);
        }

        .google-icon {
            width: 18px;
            height: 18px;
            display: block;
        }

        .login-dialog-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1200;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(17, 24, 39, 0.55);
            padding: 16px;
        }

        .login-dialog-backdrop.is-open {
            display: flex;
        }

        .login-dialog {
            width: min(420px, 100%);
            background: #fff;
            border: 1px solid #fdba74;
            border-radius: 14px;
            box-shadow: 0 16px 36px rgba(124, 45, 18, 0.22);
            overflow: hidden;
        }

        .login-dialog-head {
            padding: 12px 14px;
            background: #fff7ed;
            border-bottom: 1px solid #fed7aa;
            color: #9a3412;
            font-size: 16px;
            font-weight: 800;
        }

        .login-dialog-body {
            padding: 14px;
            color: #1f2937;
            white-space: pre-wrap;
        }

        .login-dialog-actions {
            display: flex;
            justify-content: flex-end;
            padding: 10px 14px 14px;
            border-top: 1px solid #fed7aa;
            background: #fffaf5;
        }

        .login-dialog-btn {
            border: 1px solid #ea580c;
            background: #ea580c;
            color: #fff;
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        html[data-theme="dark"] body {
            background:
                radial-gradient(circle at top right, #2a1f16 0%, transparent 45%),
                radial-gradient(circle at left bottom, #2a1a10 0%, transparent 38%),
                #111827;
            color: #e5e7eb;
        }

        html[data-theme="dark"] .wrap {
            background: #111827;
            border-color: #374151;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.42);
        }

        html[data-theme="dark"] .login-tools-card {
            background: #111827;
            border-color: #374151;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.35);
        }

        html[data-theme="dark"] .brand-name,
        html[data-theme="dark"] label {
            color: #f59e0b;
        }

        html[data-theme="dark"] .subtitle,
        html[data-theme="dark"] .links a,
        html[data-theme="dark"] .theme-toggle {
            color: #d1d5db;
        }

        html[data-theme="dark"] input,
        html[data-theme="dark"] .verify-box,
        html[data-theme="dark"] .google-btn,
        html[data-theme="dark"] .radio-inputs,
        html[data-theme="dark"] .login-dialog,
        html[data-theme="dark"] .login-dialog-actions {
            background: #1f2937;
            border-color: #374151;
            color: #e5e7eb;
        }

        html[data-theme="dark"] .google-btn:hover {
            background: #111827;
            border-color: #4b5563;
        }

        html[data-theme="dark"] .verify-note,
        html[data-theme="dark"] .login-dialog-head {
            color: #fbbf24;
            background: #1f2937;
            border-color: #374151;
        }

        html[data-theme="dark"] .login-dialog-body {
            color: #e5e7eb;
            background: #111827;
        }

        @media (max-width: 520px) {
            body {
                padding: 18px 14px 16px;
            }
            .radio-inputs {
                width: 106px;
                height: 34px;
            }
            .theme-toggle {
                font-size: 11px;
                gap: 6px;
            }
            .switch {
                font-size: 12px;
            }
            .wrap { padding: 22px 18px; }
            .subtitle { font-size: 16px; }
            label { font-size: 15px; }
            .brand img { width: 220px; height: 88px; }
        }
    </style>
    @if (($captchaMode ?? 'offline') === 'online' && !empty($recaptchaSiteKey))
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
</head>
<body>
<main class="login-page">
<div class="login-page-tools">
    <div class="login-tools-card">
        <div class="login-tools-row">
            <div class="radio-inputs" aria-label="Pilihan Bahasa">
                <label class="radio">
                    <input type="radio" name="ui-lang-switch" value="id" checked>
                    <span class="radio-item">IND</span>
                </label>
                <label class="radio">
                    <input type="radio" name="ui-lang-switch" value="en">
                    <span class="radio-item">ENG</span>
                </label>
            </div>
            <div class="theme-toggle">
                <span id="login-theme-label">Mode Gelap</span>
                <label class="switch" for="ui-theme-switch">
                    <input type="checkbox" id="ui-theme-switch">
                    <span class="slider"></span>
                </label>
            </div>
        </div>
    </div>
</div>
<div class="wrap">
    <div class="brand">
        @if ($logoExists)
            <img src="{{ asset($logoPath) }}" alt="Logo">
        @else
            <h1 class="brand-name">{{ $appName }}</h1>
        @endif
        <p class="subtitle">{{ $appProfile['tagline'] ?? 'Absensi & Monitoring PKL' }}</p>
    </div>

    @if (session('success'))<div class="msg ok">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="msg err">{{ session('error') }}</div>@endif

    <form method="POST" action="{{ route('login.store', absolute: false) }}">
        @csrf
        <input type="hidden" id="login-latitude" name="latitude">
        <input type="hidden" id="login-longitude" name="longitude">

        <label for="identifier">Email / NIS</label>
        <input type="text" id="identifier" name="identifier" value="{{ old('identifier') }}" required autofocus>
        @error('identifier')<div class="msg err">{{ $message }}</div>@enderror

        <label for="password">Password</label>
        <div class="password-wrap">
            <input type="password" id="password" name="password" required>
            <button type="button" class="toggle-pass" data-target="password" aria-label="Tampilkan password" data-show="{{ asset('icons/eye.png') }}" data-hide="{{ asset('icons/invisible.png') }}">
                <img src="{{ asset('icons/eye.png') }}" alt="Toggle Password">
            </button>
        </div>

        @if (($captchaMode ?? 'offline') === 'online' && !empty($recaptchaSiteKey))
            <div class="verify-box">
                <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}"></div>
            </div>
        @else
            <div class="verify-box">
                <p class="verify-note">Jawab soal berikut: {{ $offlineQuestion ?? '-' }}</p>
                <input
                    type="number"
                    name="offline_captcha_answer"
                    id="offline_captcha_answer"
                    placeholder="Masukkan hasil"
                    required
                >
            </div>
        @endif

        @error('captcha')<div class="msg err">{{ $message }}</div>@enderror

        <button class="btn" type="submit">Masuk</button>
    </form>

    <div class="links" style="justify-content: flex-end;">
        <a href="{{ route('password.request') }}">Lupa Password</a>
    </div>
    @if ($googleOauthReady ?? false)
        <a href="{{ route('auth.google.redirect') }}" class="google-btn">
            <svg class="google-icon" viewBox="0 0 48 48" aria-hidden="true">
                <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12S17.4 12 24 12c3 0 5.7 1.1 7.8 3l5.7-5.7C34.1 6.2 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/>
                <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15 18.9 12 24 12c3 0 5.7 1.1 7.8 3l5.7-5.7C34.1 6.2 29.3 4 24 4c-7.7 0-14.3 4.3-17.7 10.7z"/>
                <path fill="#4CAF50" d="M24 44c5.2 0 10-2 13.5-5.2l-6.2-5.2C29.3 35.1 26.8 36 24 36c-5.2 0-9.6-3.3-11.2-8l-6.6 5.1C9.5 39.5 16.2 44 24 44z"/>
                <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-1 2.9-3 5.3-5.9 6.7l6.2 5.2C39.3 36.5 44 30.8 44 24c0-1.3-.1-2.4-.4-3.5z"/>
            </svg>
            Masuk dengan Google
        </a>
    @else
        <button type="button" class="google-btn" style="opacity:.65;cursor:not-allowed;" disabled>
            <svg class="google-icon" viewBox="0 0 48 48" aria-hidden="true">
                <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12S17.4 12 24 12c3 0 5.7 1.1 7.8 3l5.7-5.7C34.1 6.2 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/>
                <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15 18.9 12 24 12c3 0 5.7 1.1 7.8 3l5.7-5.7C34.1 6.2 29.3 4 24 4c-7.7 0-14.3 4.3-17.7 10.7z"/>
                <path fill="#4CAF50" d="M24 44c5.2 0 10-2 13.5-5.2l-6.2-5.2C29.3 35.1 26.8 36 24 36c-5.2 0-9.6-3.3-11.2-8l-6.6 5.1C9.5 39.5 16.2 44 24 44z"/>
                <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-1 2.9-3 5.3-5.9 6.7l6.2 5.2C39.3 36.5 44 30.8 44 24c0-1.3-.1-2.4-.4-3.5z"/>
            </svg>
            Login Google Belum Aktif
        </button>
        <div class="msg err" style="margin-top:8px;">
            Login Google belum dikonfigurasi. Isi <code>GOOGLE_CLIENT_ID</code>, <code>GOOGLE_CLIENT_SECRET</code>, dan <code>GOOGLE_REDIRECT_URI</code> di file <code>.env</code>.
        </div>
    @endif
    <div class="links" style="justify-content: center; margin-top:10px;">
        <a href="{{ route('login.otp.email') }}">Login OTP Email</a>
    </div>
</div>
</main>
<div id="login-dialog-backdrop" class="login-dialog-backdrop" aria-hidden="true">
    <section class="login-dialog" role="dialog" aria-modal="true" aria-labelledby="login-dialog-title">
        <header id="login-dialog-title" class="login-dialog-head">Pemberitahuan</header>
        <div id="login-dialog-message" class="login-dialog-body"></div>
        <div class="login-dialog-actions">
            <button id="login-dialog-ok" class="login-dialog-btn" type="button">OK</button>
        </div>
    </section>
</div>
<script>
    const STORAGE_LANG_KEY = 'ui_lang';
    const STORAGE_THEME_KEY = 'ui_theme';
    const LOGIN_GEO_LAT_KEY = 'ui_geo_lat';
    const LOGIN_GEO_LNG_KEY = 'ui_geo_lng';
    const languageSwitches = document.querySelectorAll('input[name="ui-lang-switch"]');
    const themeSwitch = document.getElementById('ui-theme-switch');
    const loginThemeLabel = document.getElementById('login-theme-label');
    const loginForm = document.querySelector('form[action="{{ route('login.store', absolute: false) }}"]');
    const loginLatitude = document.getElementById('login-latitude');
    const loginLongitude = document.getElementById('login-longitude');
    const loginDialogBackdrop = document.getElementById('login-dialog-backdrop');
    const loginDialogTitle = document.getElementById('login-dialog-title');
    const loginDialogMessage = document.getElementById('login-dialog-message');
    const loginDialogOk = document.getElementById('login-dialog-ok');

    const phrasePairs = [
        ['Mode Gelap', 'Dark Mode'],
        ['Pemberitahuan', 'Notice'],
        ['Portal Absensi PKL', 'PKL Attendance Portal'],
        ['Absensi & Monitoring PKL', 'PKL Attendance & Monitoring'],
        ['Jawab soal berikut:', 'Answer the following question:'],
        ['Masukkan hasil', 'Enter result'],
        ['Masuk', 'Sign In'],
        ['Lupa Password', 'Forgot Password'],
        ['Masuk dengan Google', 'Sign in with Google'],
        ['Login Google Belum Aktif', 'Google Login Not Active'],
        ['Login OTP Email', 'OTP Email Login'],
        ['Lokasi wajib diaktifkan agar login bisa diproses dan terekam di log activity.', 'Location must be enabled so login can be processed and recorded in activity log.'],
        ['Tampilkan password', 'Show password'],
        ['Sembunyikan password', 'Hide password'],
        ['Password', 'Password'],
        ['Jika email diganti, verifikasi email akan diminta ulang.', 'If the email is changed, email verification will be required again.'],
        ['Login Google belum dikonfigurasi. Isi', 'Google Login is not configured yet. Fill'],
        ['di file', 'in file']
    ];

    const originalTextMap = new WeakMap();

    function buildPhraseRegex(phrase) {
        const escaped = String(phrase).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return new RegExp(escaped, 'g');
    }

    function replacePhrases(text, lang) {
        let output = text;
        const orderedPairs = [...phrasePairs].sort((a, b) => {
            const [aId, aEn] = a;
            const [bId, bEn] = b;
            const aFrom = lang === 'en' ? aId : aEn;
            const bFrom = lang === 'en' ? bId : bEn;
            return (bFrom?.length || 0) - (aFrom?.length || 0);
        });
        orderedPairs.forEach(([idText, enText]) => {
            const from = lang === 'en' ? idText : enText;
            const to = lang === 'en' ? enText : idText;
            if (!from || from === to) return;
            output = output.replace(buildPhraseRegex(from), to);
        });
        return output;
    }

    function translateDom(lang) {
        document.documentElement.lang = lang;
        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        const textNodes = [];
        while (walker.nextNode()) {
            const node = walker.currentNode;
            if (!node || !node.nodeValue || !node.nodeValue.trim()) continue;
            const parentTag = node.parentElement ? node.parentElement.tagName : '';
            if (parentTag === 'SCRIPT' || parentTag === 'STYLE') continue;
            textNodes.push(node);
        }
        textNodes.forEach((node) => {
            if (!originalTextMap.has(node)) {
                originalTextMap.set(node, node.nodeValue);
            }
            node.nodeValue = replacePhrases(originalTextMap.get(node) || node.nodeValue, lang);
        });

        document.querySelectorAll('[placeholder]').forEach((el) => {
            if (!el.dataset.i18nPlaceholderSource) {
                el.dataset.i18nPlaceholderSource = el.placeholder || '';
            }
            el.placeholder = replacePhrases(el.dataset.i18nPlaceholderSource, lang);
        });
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        if (themeSwitch) {
            themeSwitch.checked = theme === 'dark';
        }
        if (loginThemeLabel) {
            const lang = localStorage.getItem(STORAGE_LANG_KEY) || 'id';
            loginThemeLabel.textContent = lang === 'en' ? 'Dark Mode' : 'Mode Gelap';
        }
    }

    function applyLanguage(lang) {
        localStorage.setItem(STORAGE_LANG_KEY, lang);
        languageSwitches.forEach((radio) => {
            radio.checked = radio.value === lang;
        });
        translateDom(lang);
        applyTheme(localStorage.getItem(STORAGE_THEME_KEY) || 'light');
    }

    function showLoginAlert(message, title = 'Pemberitahuan') {
        const lang = localStorage.getItem(STORAGE_LANG_KEY) || 'id';
        const finalTitle = lang === 'en' ? replacePhrases(title, 'en') : title;
        const finalMessage = lang === 'en' ? replacePhrases(String(message || ''), 'en') : String(message || '');

        if (!loginDialogBackdrop || !loginDialogTitle || !loginDialogMessage || !loginDialogOk) {
            window.alert(finalMessage);
            return Promise.resolve();
        }

        loginDialogTitle.textContent = finalTitle;
        loginDialogMessage.textContent = finalMessage;
        loginDialogBackdrop.classList.add('is-open');
        loginDialogBackdrop.setAttribute('aria-hidden', 'false');
        loginDialogOk.focus();

        return new Promise((resolve) => {
            const close = () => {
                loginDialogBackdrop.classList.remove('is-open');
                loginDialogBackdrop.setAttribute('aria-hidden', 'true');
                loginDialogOk.removeEventListener('click', onClick);
                document.removeEventListener('keydown', onKeydown);
                resolve();
            };
            const onClick = () => close();
            const onKeydown = (event) => {
                if (event.key === 'Escape' || event.key === 'Enter') {
                    event.preventDefault();
                    close();
                }
            };
            loginDialogOk.addEventListener('click', onClick);
            document.addEventListener('keydown', onKeydown);
        });
    }

    function setLoginCoordinate(latitude, longitude) {
        if (!loginLatitude || !loginLongitude) return;

        const lat = Number(latitude);
        const lng = Number(longitude);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

        loginLatitude.value = lat.toFixed(7);
        loginLongitude.value = lng.toFixed(7);
        localStorage.setItem(LOGIN_GEO_LAT_KEY, loginLatitude.value);
        localStorage.setItem(LOGIN_GEO_LNG_KEY, loginLongitude.value);
    }

    function restoreLoginCoordinate() {
        const savedLat = localStorage.getItem(LOGIN_GEO_LAT_KEY);
        const savedLng = localStorage.getItem(LOGIN_GEO_LNG_KEY);
        if (!savedLat || !savedLng) return;
        setLoginCoordinate(savedLat, savedLng);
    }

    function requestLoginCoordinate(onSuccess, onError) {
        if (!navigator.geolocation) {
            onError?.();
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                setLoginCoordinate(position.coords.latitude, position.coords.longitude);
                onSuccess?.();
            },
            () => onError?.(),
            { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
        );
    }

    restoreLoginCoordinate();
    requestLoginCoordinate();

    const savedTheme = localStorage.getItem(STORAGE_THEME_KEY) || 'light';
    const savedLang = localStorage.getItem(STORAGE_LANG_KEY) || 'id';
    applyTheme(savedTheme);
    applyLanguage(savedLang);

    languageSwitches.forEach((radio) => {
        radio.addEventListener('change', function () {
            if (this.checked) {
                applyLanguage(this.value);
            }
        });
    });

    if (themeSwitch) {
        themeSwitch.addEventListener('change', function () {
            const next = this.checked ? 'dark' : 'light';
            localStorage.setItem(STORAGE_THEME_KEY, next);
            applyTheme(next);
        });
    }

    if (loginForm) {
        loginForm.addEventListener('submit', (event) => {
            if (loginLatitude?.value && loginLongitude?.value) {
                return;
            }

            event.preventDefault();
            requestLoginCoordinate(
                () => loginForm.submit(),
                () => showLoginAlert('Lokasi wajib diaktifkan agar login bisa diproses dan terekam di log activity.')
            );
        });
    }

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
            const lang = localStorage.getItem(STORAGE_LANG_KEY) || 'id';
            const nextLabel = isPassword ? 'Sembunyikan password' : 'Tampilkan password';
            button.setAttribute('aria-label', lang === 'en' ? replacePhrases(nextLabel, 'en') : nextLabel);
        });
    });
</script>
</body>
</html>
