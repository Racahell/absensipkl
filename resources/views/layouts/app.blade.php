<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $logoPath = $appProfile['logo'] ?? 'image/download.png';
        $faviconPath = $appProfile['favicon'] ?? $logoPath;
    @endphp
    <title>{{ $title ?? ($appProfile['name'] ?? 'Absensi PKL') }}</title>
    <link rel="icon" type="image/png" href="{{ asset($faviconPath) }}">
    <link rel="apple-touch-icon" href="{{ asset($faviconPath) }}">
    <style>
        :root {
            --bg: {{ $appProfile['theme_background'] ?? '#fffaf5' }};
            --card: {{ $appProfile['theme_card'] ?? '#ffffff' }};
            --accent: {{ $appProfile['theme_primary'] ?? '#ea580c' }};
            --line: color-mix(in srgb, var(--accent) 40%, white);
            --accent-soft: color-mix(in srgb, var(--accent) 12%, white);
            --text: #111827;
            --accent-text: color-mix(in srgb, var(--accent) 78%, #111827);
            --surface: #ffffff;
            --surface-soft: color-mix(in srgb, var(--accent) 8%, white);
            --muted: #78716c;
            --sidebar-bg: {{ $appProfile['theme_sidebar'] ?? '#ffffff' }};
            --button-bg: {{ $appProfile['theme_button'] ?? ($appProfile['theme_primary'] ?? '#ea580c') }};
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background: linear-gradient(160deg, var(--surface) 0%, var(--bg) 45%, var(--accent-soft) 100%);
        }

        .shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 260px 1fr;
        }

        .sidebar {
            border-right: 1px solid var(--line);
            background: var(--sidebar-bg);
            padding: 8px 16px 20px;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            align-self: start;
        }

        .brand {
            display: flex;
            justify-content: center;
            padding: 0;
            margin: -14px 0 -10px;
        }

        .brand img {
            width: 200px;
            height: 135px;
            object-fit: contain;
            border-radius: 0;
            border: 0;
            padding: 0;
            background: transparent;
            display: block;
        }

        .greeting {
            width: 200px;
            margin: -2px auto 8px;
            padding: 0;
            color: var(--accent-text);
            font-size: 16px;
            line-height: 1.2;
            font-weight: 700;
        }

        .greeting strong {
            display: block;
            font-size: 19px;
            line-height: 1.12;
            color: var(--accent-text);
        }

        .menu a {
            display: block;
            text-decoration: none;
            color: var(--text);
            padding: 10px 12px;
            border-radius: 10px;
            margin-bottom: 6px;
            border: 1px solid transparent;
            border-left: 3px solid transparent;
            font-size: 14px;
            transition: .15s ease;
        }

        .menu a:hover,
        .menu a.active {
            background: var(--accent-soft);
            border-color: var(--line);
            border-left-color: var(--accent);
            color: var(--accent-text);
        }

        .menu {
            flex: 1;
            overflow-y: auto;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .menu::-webkit-scrollbar {
            width: 0;
            height: 0;
            display: none;
        }

        .menu-section {
            margin-bottom: 10px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--accent-soft);
            overflow: hidden;
        }

        .menu-section-toggle {
            width: 100%;
            border: 0;
            background: transparent;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            color: var(--accent-text);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .menu-section-toggle:hover {
            background: #fff1e6;
        }

        .menu-section-caret {
            font-size: 11px;
            color: #c2410c;
            transition: transform .15s ease;
        }

        .menu-section.open .menu-section-caret {
            transform: rotate(180deg);
        }

        .menu-section-items {
            display: none;
            padding: 8px;
            border-top: 1px solid var(--line);
            background: var(--surface);
        }

        .menu-section.open .menu-section-items {
            display: block;
        }

        .menu-section-items a {
            margin-bottom: 8px;
        }

        .menu-section-items a:last-child {
            margin-bottom: 0;
        }

        .main {
            display: flex;
            flex-direction: column;
        }

        .topbar {
            height: 68px;
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 22px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(4px);
            position: sticky;
            top: 0;
            z-index: 1100;
            box-shadow: none;
            transition: box-shadow .18s ease;
        }

        .topbar.is-scrolled {
            box-shadow: 0 8px 20px rgba(17, 24, 39, 0.08);
        }

        .topbar h2 {
            font-size: 16px;
            margin: 0;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: 14px;
        }

        .topbar-avatar {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            border: 2px solid var(--line);
            background: var(--accent-soft);
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-text);
            font-size: 16px;
            font-weight: 700;
            flex: 0 0 44px;
        }

        .topbar-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .topbar-user-text {
            min-width: 0;
        }

        .topbar-greeting {
            font-size: 18px;
            line-height: 1.2;
            font-weight: 700;
            color: var(--accent-text);
            letter-spacing: 0.2px;
        }

        .topbar-greeting strong {
            font-weight: 800;
            color: var(--accent-text);
        }

        .topbar small {
            color: var(--muted);
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .sidebar-bottom {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .sidebar-bottom-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
        }

        .radio-inputs {
            position: relative;
            display: flex;
            flex-wrap: wrap;
            border-radius: 10px;
            background: var(--accent-soft);
            border: 1px solid var(--line);
            box-sizing: border-box;
            padding: 3px;
            width: 132px;
            font-size: 12px;
            height: 40px;
        }

        .radio-inputs .radio {
            flex: 1 1 auto;
            text-align: center;
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
            padding: 0.45rem 0;
            color: var(--accent-text);
            font-weight: 700;
            transition: background-color 0.5s ease, font-weight 0.5s ease, color 0.5s ease;
            letter-spacing: .04em;
        }

        .radio-inputs .radio input:checked + .radio-item {
            background-color: var(--accent);
            font-weight: 600;
            color: #fff;
        }

        .ui-toggle-btn {
            border: 1px solid var(--line);
            background: var(--accent-soft);
            color: var(--accent-text);
            padding: 8px 10px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
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
            transition: all 0.4s cubic-bezier(0.23, 1, 0.320, 1);
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
            box-shadow: 0 0px 20px rgba(0, 0, 0, 0.25);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .switch input:checked + .slider {
            background: #111827;
            border-color: #111827;
        }

        .switch input:focus + .slider {
            box-shadow: 0 0 1px #111827;
        }

        .switch input:checked + .slider:before {
            transform: translateX(1.35em);
            width: 1.7em;
            height: 1.7em;
            bottom: 0;
            background: #ffffff;
        }

        .logout-btn {
            border: 1px solid var(--button-bg);
            background: var(--button-bg);
            color: #fff;
            padding: 8px 12px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }

        .logout-btn:hover {
            filter: brightness(1.05);
        }

        .content {
            padding: 24px;
            flex: 1;
        }

        .footer {
            border-top: 1px solid var(--line);
            background: var(--surface);
            padding: 12px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            font-size: 13px;
            color: var(--accent-text);
        }

        .footer a {
            color: var(--accent-text);
            text-decoration: none;
            border-bottom: 1px dashed var(--line);
        }

        .footer-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 10px 24px rgba(234, 88, 12, 0.08);
        }

        label {
            font-size: 13px;
            font-weight: 600;
            color: var(--accent-text);
            margin-bottom: 4px;
            display: inline-block;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="date"],
        input[type="time"],
        input[type="file"],
        input[type="number"],
        select,
        textarea,
        input:not([type]) {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px 12px;
            color: #1f2937;
            background: #ffffff;
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
            font: inherit;
        }

        textarea {
            min-height: 88px;
            resize: vertical;
        }

        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 38px;
            background-image:
                linear-gradient(45deg, transparent 50%, var(--accent) 50%),
                linear-gradient(135deg, var(--accent) 50%, transparent 50%);
            background-position:
                calc(100% - 18px) calc(50% - 3px),
                calc(100% - 12px) calc(50% - 3px);
            background-size: 6px 6px, 6px 6px;
            background-repeat: no-repeat;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-soft);
            background: var(--accent-soft);
        }

        input::placeholder,
        textarea::placeholder {
            color: #a8a29e;
        }

        input[type="checkbox"],
        input[type="radio"] {
            accent-color: var(--accent);
            width: 16px;
            height: 16px;
        }

        button,
        .btn,
        input[type="submit"],
        input[type="button"] {
            border: 1px solid var(--button-bg);
            background: var(--button-bg);
            color: #fff;
            padding: 9px 12px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: transform .08s ease, filter .15s ease, box-shadow .15s ease;
        }

        button:hover,
        .btn:hover,
        input[type="submit"]:hover,
        input[type="button"]:hover {
            filter: brightness(1.06);
        }

        button:active,
        .btn:active,
        input[type="submit"]:active,
        input[type="button"]:active {
            transform: translateY(1px);
        }

        button:disabled,
        input:disabled,
        select:disabled,
        textarea:disabled {
            opacity: .65;
            cursor: not-allowed;
        }

        details > summary {
            cursor: pointer;
            color: var(--accent-text);
            font-weight: 600;
            list-style: none;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 7px 10px;
            background: var(--accent-soft);
            display: inline-block;
        }

        details > summary::-webkit-details-marker {
            display: none;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            background: var(--surface);
            color: var(--text);
        }

        th {
            background: var(--accent-soft);
            color: var(--accent-text);
            font-weight: 700;
        }

        tbody tr {
            background: color-mix(in srgb, var(--surface) 94%, var(--accent-soft));
        }

        tbody tr:nth-child(even) {
            background: color-mix(in srgb, var(--surface) 88%, var(--accent-soft));
        }

        tbody tr:hover {
            background: color-mix(in srgb, var(--surface) 80%, var(--accent-soft));
        }

        td, th {
            padding: 9px 8px;
            border: 1px solid var(--line);
            vertical-align: top;
        }

        .mb-10 { margin-bottom: 10px; }
        .mb-14 { margin-bottom: 14px; }
        .mb-16 { margin-bottom: 16px; }
        .mt-0 { margin-top: 0; }
        .mt-10 { margin-top: 10px; }
        .w-fit { width: max-content; }
        .w-full { width: 100%; }
        .grid { display: grid; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .gap-6 { gap: 6px; }
        .gap-8 { gap: 8px; }
        .gap-10 { gap: 10px; }
        .gap-12 { gap: 12px; }
        .flex { display: flex; }
        .wrap { flex-wrap: wrap; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .table-wrap { overflow-x: auto; }
        .text-muted { color: #6b7280; }
        .text-danger { color: #b91c1c; }
        .text-primary { color: var(--accent-text); }
        .panel {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 14px;
            background: var(--surface);
        }
        .alert {
            padding: 10px;
            border-radius: 10px;
            border: 1px solid var(--line);
        }
        .alert-success {
            border-color: #86efac;
            background: #f0fdf4;
            color: #166534;
        }
        .alert-error {
            border-color: #fecaca;
            background: #fff1f2;
            color: #991b1b;
        }
        .btn-success {
            border-color: #16a34a !important;
            background: #16a34a !important;
            color: #fff !important;
        }
        .btn-danger {
            border-color: #dc2626 !important;
            background: #dc2626 !important;
            color: #fff !important;
        }
        .btn-ghost {
            border-color: var(--line) !important;
            background: var(--accent-soft) !important;
            color: var(--accent-text) !important;
        }
        .pagination-wrap {
            display: flex;
            justify-content: center;
        }
        .pagination-list {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .pagination-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 38px;
            padding: 8px 12px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--surface);
            color: var(--accent-text);
            font-size: 13px;
            font-weight: 600;
            line-height: 1;
            text-decoration: none;
        }
        a.pagination-btn:hover {
            background: var(--accent-soft);
        }
        .pagination-btn.active {
            border-color: var(--accent);
            background: var(--accent);
            color: #fff;
        }
        .pagination-btn.disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
        .pagination-ellipsis {
            color: #9ca3af;
            padding: 0 2px;
            font-weight: 600;
        }

        .chatbot-toggle {
            position: fixed;
            right: 18px;
            bottom: 18px;
            z-index: 1300;
            border-radius: 999px;
            padding: 10px 14px;
            font-size: 13px;
            box-shadow: 0 10px 24px rgba(234, 88, 12, 0.25);
        }

        .chatbot-panel {
            position: fixed;
            right: 18px;
            bottom: 72px;
            width: min(390px, calc(100vw - 20px));
            height: min(500px, calc(100vh - 95px));
            max-height: min(500px, calc(100vh - 95px));
            z-index: 1301;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--surface);
            box-shadow: 0 20px 36px rgba(17, 24, 39, 0.22);
            display: none;
            overflow: hidden;
        }

        .chatbot-panel.is-open {
            display: grid;
            grid-template-rows: auto 1fr auto;
        }

        .chatbot-header {
            padding: 11px 12px;
            border-bottom: 1px solid var(--line);
            background: var(--accent-soft);
            font-weight: 700;
            color: var(--accent-text);
            font-size: 20px;
        }

        .chatbot-messages {
            padding: 12px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            background: var(--surface);
        }

        .chatbot-bubble {
            padding: 10px 12px;
            border-radius: 12px;
            max-width: 88%;
            width: fit-content;
            border: 1px solid var(--line);
            font-size: 13px;
            line-height: 1.45;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .chatbot-bubble.user {
            margin-left: auto;
            background: var(--accent-soft);
            color: var(--accent-text);
        }

        .chatbot-bubble.bot {
            margin-right: auto;
            background: #ffffff;
            color: #1f2937;
        }

        .chatbot-quick-options {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 2px;
            width: 100%;
        }

        .chatbot-quick-options button {
            border: 1px solid var(--line);
            background: var(--accent-soft);
            color: var(--accent-text);
            border-radius: 999px;
            padding: 6px 9px;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.1;
        }

        .chatbot-quick-options button:hover {
            background: var(--accent-soft);
        }

        .chatbot-input-wrap {
            border-top: 1px solid var(--line);
            padding: 10px;
            background: var(--accent-soft);
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            align-items: center;
        }

        .chatbot-input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 9px 10px;
            font-size: 13px;
        }

        .app-dialog-backdrop {
            position: fixed;
            inset: 0;
            z-index: 2500;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(17, 24, 39, 0.55);
            padding: 14px;
        }

        .app-dialog-backdrop.is-open {
            display: flex;
        }

        .app-dialog {
            width: min(460px, 100%);
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: 0 16px 36px rgba(124, 45, 18, 0.24);
            overflow: hidden;
        }

        .app-dialog-head {
            padding: 12px 14px;
            border-bottom: 1px solid var(--line);
            background: var(--accent-soft);
            color: var(--accent-text);
            font-size: 16px;
            font-weight: 800;
        }

        .app-dialog-body {
            padding: 14px;
            color: #1f2937;
            white-space: pre-wrap;
            word-break: break-word;
            max-height: 48vh;
            overflow-y: auto;
        }

        .app-dialog-input-wrap {
            padding: 0 14px 14px;
            display: none;
        }

        .app-dialog-input-wrap.is-visible {
            display: block;
        }

        .app-dialog-input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 9px 10px;
            font-size: 14px;
            outline: none;
        }

        .app-dialog-input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-soft);
        }

        .app-dialog-actions {
            padding: 10px 14px 14px;
            border-top: 1px solid var(--line);
            background: var(--accent-soft);
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .app-dialog-btn {
            border: 1px solid var(--line);
            background: var(--accent-soft);
            color: var(--accent-text);
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .app-dialog-btn-primary {
            border-color: var(--accent);
            background: var(--accent);
            color: #fff;
        }

        html[data-theme="dark"] .chatbot-panel {
            background: #111827;
            border-color: #374151;
        }

        html[data-theme="dark"] .chatbot-header {
            background: #1f2937;
            border-color: #374151;
            color: var(--line);
        }

        html[data-theme="dark"] .chatbot-messages {
            background: #111827;
        }

        html[data-theme="dark"] .chatbot-bubble.user {
            background: #1f2937;
            border-color: #374151;
            color: var(--line);
        }

        html[data-theme="dark"] .chatbot-bubble.bot {
            background: #111827;
            border-color: #374151;
            color: #e5e7eb;
        }

        html[data-theme="dark"] .chatbot-quick-options button {
            background: #1f2937;
            border-color: #374151;
            color: var(--line);
        }

        html[data-theme="dark"] .chatbot-quick-options button:hover {
            background: #2a3444;
        }

        html[data-theme="dark"] .chatbot-input-wrap {
            background: #111827;
            border-color: #374151;
        }

        html[data-theme="dark"] .chatbot-input {
            background: #111827;
            color: #e5e7eb;
            border-color: #374151;
        }

        html[data-theme="dark"] .app-dialog {
            background: #111827;
            border-color: #374151;
        }

        html[data-theme="dark"] .app-dialog-head {
            background: #1f2937;
            border-color: #374151;
            color: var(--line);
        }

        html[data-theme="dark"] .app-dialog-body {
            color: #e5e7eb;
        }

        html[data-theme="dark"] .app-dialog-input {
            background: #111827;
            border-color: #374151;
            color: #e5e7eb;
        }

        html[data-theme="dark"] .app-dialog-actions {
            background: #111827;
            border-color: #374151;
        }

        html[data-theme="dark"] .app-dialog-btn {
            background: #1f2937;
            border-color: #374151;
            color: var(--line);
        }

        html[data-theme="dark"] {
            --bg: #0f172a;
            --card: #111827;
            --line: #374151;
            --accent: #f97316;
            --accent-soft: #1f2937;
            --text: #e5e7eb;
            --muted: #9ca3af;
        }

        html[data-theme="dark"] body {
            background: linear-gradient(160deg, #0b1220 0%, #0f172a 45%, #111827 100%);
        }

        html[data-theme="dark"] .sidebar,
        html[data-theme="dark"] .topbar,
        html[data-theme="dark"] .footer,
        html[data-theme="dark"] .panel,
        html[data-theme="dark"] .card,
        html[data-theme="dark"] input,
        html[data-theme="dark"] select,
        html[data-theme="dark"] textarea,
        html[data-theme="dark"] .radio-inputs {
            background: #111827 !important;
            color: #e5e7eb !important;
            border-color: #374151 !important;
        }

        html[data-theme="dark"] th {
            background: #1f2937;
            color: #f3f4f6;
        }

        html[data-theme="dark"] tbody tr {
            background: #111827;
            color: #e5e7eb;
        }

        html[data-theme="dark"] tbody tr:nth-child(even) {
            background: #0f172a;
        }

        html[data-theme="dark"] tbody tr:hover {
            background: #1f2937;
        }

        html[data-theme="dark"] td,
        html[data-theme="dark"] th,
        html[data-theme="dark"] .alert {
            border-color: #374151 !important;
        }

        html[data-theme="dark"] .menu a:hover,
        html[data-theme="dark"] .menu a.active {
            color: var(--line);
            border-left-color: #fb923c;
        }

        html[data-theme="dark"] .ui-toggle-btn {
            background: #1f2937;
            color: var(--line);
            border-color: #374151;
        }

        html[data-theme="dark"] .sidebar-bottom-label {
            color: #d1d5db;
        }

        html[data-theme="dark"] .slider {
            background: #111827;
            border-color: #4b5563;
        }

        html[data-theme="dark"] .radio-inputs {
            background: #1f2937 !important;
            border: 1px solid #374151;
        }

        html[data-theme="dark"] .radio-inputs .radio input:checked + .radio-item {
            background-color: #f97316;
            color: #111827;
        }
        html[data-theme="dark"] .pagination-btn {
            background: #111827;
            border-color: #374151;
            color: var(--line);
        }
        html[data-theme="dark"] a.pagination-btn:hover {
            background: #1f2937;
        }
        html[data-theme="dark"] .pagination-btn.active {
            background: #f97316;
            border-color: #f97316;
            color: #111827;
        }

        @media (max-width: 880px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 880px) {
            .shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                border-right: 0;
                border-bottom: 1px solid var(--line);
                position: static;
                height: auto;
            }
        }
    </style>
</head>
<body>
@php
    $nav = [
        'superadmin' => [
            [
                'section' => 'Beranda',
                'items' => [
                    ['name' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'DB'],
                    ['name' => 'Profil Saya', 'url' => '/profil', 'icon' => 'PR'],
                    ['name' => 'Riwayat Catatan', 'url' => '/riwayat-catatan', 'icon' => 'RC'],
                ],
            ],
            [
                'section' => 'Master Data',
                'items' => [
                    ['name' => 'Manajemen Pengguna', 'url' => '/fitur/manajemen-pengguna', 'icon' => 'US'],
                    ['name' => 'Monitoring Siswa', 'url' => '/kajur/siswa', 'icon' => 'MS'],

                    ['name' => 'Hak Akses Menu', 'url' => '/fitur/hak-akses-menu', 'icon' => 'HA'],
                    ['name' => 'Setting Website', 'url' => '/fitur/setting-web', 'icon' => 'SW'],
                ],
            ],
            [
                'section' => 'Validasi',
                'items' => [
                    ['name' => 'Validasi Absensi', 'url' => '/validasi', 'icon' => 'VA'],
                    ['name' => 'Validasi Pengajuan', 'url' => '/validasi-pengajuan', 'icon' => 'VP'],
                ],
            ],
            [
                'section' => 'Keamanan & Audit',
                'items' => [
                    ['name' => 'Log Activity', 'url' => '/fitur/audit-log', 'icon' => 'LG'],
                ],
            ],
            [
                'section' => 'Laporan',
                'items' => [
                    ['name' => 'Validasi Mingguan', 'url' => '/summary-report', 'icon' => 'VM'],
                    ['name' => 'Rekap Mingguan', 'url' => '/summary-report/rekap', 'icon' => 'RK'],
                    ['name' => 'Analisis Mingguan', 'url' => '/summary-report/analisis', 'icon' => 'AN'],
                    ['name' => 'Laporan', 'url' => '/fitur-shared/laporan-grafik', 'icon' => 'GR'],
                ],
            ],
            [
                'section' => 'Sistem',
                'items' => [
                    ['name' => 'Backup & Restore', 'url' => '/fitur/backup-restore', 'icon' => 'BR'],
                    ['name' => 'Import & Export User', 'url' => '/fitur/import-export', 'icon' => 'IE'],
                ],
            ],
        ],
        'admin_sekolah' => [
            [
                'section' => 'Beranda',
                'items' => [
                    ['name' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'DB'],
                    ['name' => 'Profil Saya', 'url' => '/profil', 'icon' => 'PR'],
                ],
            ],
            [
                'section' => 'Data & Konfigurasi',
                'items' => [
                    ['name' => 'Manajemen Pengguna', 'url' => '/fitur-admin/manajemen-pengguna', 'icon' => 'US'],
                    ['name' => 'Setting Website', 'url' => '/fitur-admin/setting-web', 'icon' => 'SW'],
                    ['name' => 'Log Activity', 'url' => '/fitur-admin/audit-log', 'icon' => 'LG'],
                ],
            ],
            [
                'section' => 'Laporan',
                'items' => [
                    ['name' => 'Laporan', 'url' => '/fitur-admin/laporan-grafik', 'icon' => 'GR'],
                    ['name' => 'Backup & Restore', 'url' => '/fitur-admin/backup-restore', 'icon' => 'BR'],
                    ['name' => 'Import & Export User', 'url' => '/fitur-admin/import-export', 'icon' => 'IE'],
                ],
            ],
        ],
        'siswa' => [
            [
                'section' => 'Beranda',
                'items' => [
                    ['name' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'DB'],
                    ['name' => 'Profil Saya', 'url' => '/profil', 'icon' => 'PR'],
                ],
            ],
            [
                'section' => 'Operasional',
                'items' => [
                    ['name' => 'Absensi Harian', 'url' => '/absensi', 'icon' => 'AB'],
                    ['name' => 'Pengajuan Izin/Sakit', 'url' => '/pengajuan', 'icon' => 'IZ'],
                    ['name' => 'Riwayat Catatan', 'url' => '/riwayat-catatan', 'icon' => 'RC'],
                ],
            ],
        ],
        'pembimbing_pkl' => [
            [
                'section' => 'Beranda',
                'items' => [
                    ['name' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'DB'],
                    ['name' => 'Profil Saya', 'url' => '/profil', 'icon' => 'PR'],
                ],
            ],
            [
                'section' => 'Validasi',
                'items' => [
                    ['name' => 'Validasi Absensi', 'url' => '/validasi', 'icon' => 'VA'],
                    ['name' => 'Validasi Pengajuan', 'url' => '/validasi-pengajuan', 'icon' => 'VP'],
                ],
            ],
        ],
        'instruktur' => [
            [
                'section' => 'Beranda',
                'items' => [
                    ['name' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'DB'],
                    ['name' => 'Profil Saya', 'url' => '/profil', 'icon' => 'PR'],
                ],
            ],
            [
                'section' => 'Laporan',
                'items' => [
                    ['name' => 'Validasi Mingguan', 'url' => '/summary-report', 'icon' => 'VM'],
                ],
            ],
        ],
        'kajur' => [
            [
                'section' => 'Beranda',
                'items' => [
                    ['name' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'DB'],
                    ['name' => 'Profil Saya', 'url' => '/profil', 'icon' => 'PR'],
                ],
            ],
            [
                'section' => 'Master Data',
                'items' => [
                    ['name' => 'Manajemen Pengguna', 'url' => '/fitur/manajemen-pengguna', 'icon' => 'US'],
                    ['name' => 'Monitoring Siswa', 'url' => '/kajur/siswa', 'icon' => 'MS'],
                ],
            ],
            [
                'section' => 'Laporan',
                'items' => [
                    ['name' => 'Validasi Mingguan', 'url' => '/summary-report', 'icon' => 'VM'],
                    ['name' => 'Rekap Mingguan', 'url' => '/summary-report/rekap', 'icon' => 'RK'],
                    ['name' => 'Analisis Mingguan', 'url' => '/summary-report/analisis', 'icon' => 'AN'],

                    ['name' => 'Laporan', 'url' => '/fitur-shared/laporan-grafik', 'icon' => 'GR'],
                ],
            ],
        ],
        'wali_kelas' => [
            [
                'section' => 'Beranda',
                'items' => [
                    ['name' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'DB'],
                    ['name' => 'Profil Saya', 'url' => '/profil', 'icon' => 'PR'],
                ],
            ],
            [
                'section' => 'Laporan',
                'items' => [
                    ['name' => 'Rekap Mingguan', 'url' => '/summary-report/rekap', 'icon' => 'RK'],
                    ['name' => 'Analisis Mingguan', 'url' => '/summary-report/analisis', 'icon' => 'AN'],
                ],
            ],
        ],
        'kesiswaan' => [
            [
                'section' => 'Beranda',
                'items' => [
                    ['name' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'DB'],
                    ['name' => 'Profil Saya', 'url' => '/profil', 'icon' => 'PR'],
                ],
            ],
            [
                'section' => 'Laporan',
                'items' => [
                    ['name' => 'Summary Report', 'url' => '/summary-report', 'icon' => 'SR'],
                    ['name' => 'Rekap Mingguan', 'url' => '/summary-report/rekap', 'icon' => 'RK'],
                    ['name' => 'Analisis Mingguan', 'url' => '/summary-report/analisis', 'icon' => 'AN'],
                ],
            ],
        ],
        'kepsek' => [
            [
                'section' => 'Beranda',
                'items' => [
                    ['name' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'DB'],
                    ['name' => 'Profil Saya', 'url' => '/profil', 'icon' => 'PR'],
                ],
            ],
            [
                'section' => 'Laporan',
                'items' => [
                    ['name' => 'Summary Report', 'url' => '/summary-report', 'icon' => 'SR'],
                    ['name' => 'Rekap Mingguan', 'url' => '/summary-report/rekap', 'icon' => 'RK'],
                    ['name' => 'Analisis Mingguan', 'url' => '/summary-report/analisis', 'icon' => 'AN'],
                    ['name' => 'Laporan', 'url' => '/fitur-shared/laporan-grafik', 'icon' => 'GR'],
                ],
            ],
        ],
    ];

    $rawUserRole = auth()->user()->role ?? 'siswa';
    $userRole = match ($rawUserRole) {
        'owner' => 'kepsek',
        'operator' => 'admin_sekolah',
        'pembimbing' => 'pembimbing_pkl',
        default => $rawUserRole,
    };
    $sidebarTimezone = $appProfile['timezone'] ?? 'Asia/Jakarta';
    $hourNow = now($sidebarTimezone)->hour;
    $greeting = match (true) {
        $hourNow < 11 => 'Selamat pagi',
        $hourNow < 15 => 'Selamat siang',
        $hourNow < 19 => 'Selamat sore',
        default => 'Selamat malam',
    };
    $sections = $nav[$userRole] ?? [];

    $permissionDrivenRoles = ['superadmin', 'admin_sekolah', 'siswa', 'pembimbing_pkl', 'instruktur', 'kajur', 'wali_kelas', 'kesiswaan', 'kepsek', 'wakil_kepsek'];
    if (in_array($userRole, $permissionDrivenRoles, true)) {
        $canonicalKey = static function (string $key): string {
            return match (true) {
                $key === 'dashboard' || str_starts_with($key, 'dashboard/') => 'dashboard',
                $key === 'summary-report' => 'summary-report',
                $key === 'summary-report/rekap' => 'summary-report/rekap',
                $key === 'summary-report/analisis' => 'summary-report/analisis',
                in_array($key, ['fitur/audit-log', 'fitur-shared/audit-log', 'fitur-admin/audit-log'], true) => 'fitur/audit-log',
                in_array($key, ['fitur/backup-restore', 'fitur-admin/backup-restore'], true) => 'fitur/backup-restore',
                in_array($key, ['fitur/hak-akses-menu', 'fitur-admin/hak-akses-menu'], true) => 'fitur/hak-akses-menu',
                str_starts_with($key, 'fitur/import-export') || str_starts_with($key, 'fitur-admin/import-export') => 'fitur/import-export',
                in_array($key, ['fitur/laporan-grafik', 'fitur-shared/laporan-grafik', 'fitur-admin/laporan-grafik'], true) => 'fitur-shared/laporan-grafik',
                in_array($key, ['fitur/manajemen-pengguna', 'fitur-admin/manajemen-pengguna'], true) => 'fitur/manajemen-pengguna',
                in_array($key, ['fitur/setting-web', 'fitur-admin/setting-web'], true) => 'fitur/setting-web',
                $key === 'kajur/siswa' || str_starts_with($key, 'kajur/siswa/') => 'kajur/siswa',
                default => $key,
            };
        };

        $menuCatalog = [
            ['key' => 'dashboard', 'name' => 'Dashboard', 'url' => '/dashboard', 'section' => 'Beranda'],
            ['key' => 'profil', 'name' => 'Profil Saya', 'url' => '/profil', 'section' => 'Beranda'],
            ['key' => 'absensi', 'name' => 'Absensi Harian', 'url' => '/absensi', 'section' => 'Operasional'],
            ['key' => 'pengajuan', 'name' => 'Pengajuan Izin/Sakit', 'url' => '/pengajuan', 'section' => 'Operasional'],
            ['key' => 'riwayat-catatan', 'name' => 'Riwayat Catatan', 'url' => '/riwayat-catatan', 'section' => 'Operasional', 'allowed_roles' => ['siswa']],
            ['key' => 'catatan-bimbingan', 'name' => 'Catatan Bimbingan', 'url' => '/catatan-bimbingan', 'section' => 'Operasional', 'allowed_roles' => ['siswa']],
            ['key' => 'validasi', 'name' => 'Validasi Absensi', 'url' => '/validasi', 'section' => 'Validasi', 'hidden_roles' => ['instruktur']],
            ['key' => 'validasi/catatan-bimbingan', 'name' => 'Validasi Catatan Bimbingan', 'url' => '/validasi/catatan-bimbingan', 'section' => 'Validasi', 'allowed_roles' => ['pembimbing_pkl']],
            ['key' => 'validasi-pengajuan', 'name' => 'Validasi Pengajuan', 'url' => '/validasi-pengajuan', 'section' => 'Validasi', 'hidden_roles' => ['instruktur']],
            ['key' => 'wakil-kepsek/validasi-kehadiran', 'name' => 'Validasi Kehadiran', 'url' => '/wakil-kepsek/validasi-kehadiran', 'section' => 'Validasi', 'allowed_roles' => ['wakil_kepsek']],
            ['key' => 'summary-report', 'name' => 'Validasi Mingguan', 'url' => '/summary-report', 'section' => 'Laporan'],
            ['key' => 'summary-report/rekap', 'name' => 'Rekap Mingguan', 'url' => '/summary-report/rekap', 'section' => 'Laporan'],
            [
                'key' => 'summary-report/analisis',
                'name' => 'Analisis Mingguan',
                'url' => '/summary-report/analisis',
                'section' => 'Laporan',
                'name_by_role' => ['instruktur' => 'Monitoring Progres'],
                'section_by_role' => ['instruktur' => 'Akademik'],
            ],
            ['key' => 'fitur-shared/laporan-grafik', 'name' => 'Laporan', 'url' => '/fitur-shared/laporan-grafik', 'section' => 'Laporan'],
            ['key' => 'fitur/manajemen-pengguna', 'name' => 'Manajemen Pengguna', 'url' => '/fitur/manajemen-pengguna', 'section' => 'Master Data'],
            ['key' => 'fitur/master-akademik', 'name' => 'Tambah Akademik', 'url' => '/fitur/master-akademik', 'section' => 'Master Data'],
            ['key' => 'kajur/siswa', 'name' => 'Monitoring Siswa', 'url' => '/kajur/siswa', 'section' => 'Master Data', 'allowed_roles' => ['kajur', 'admin_sekolah', 'superadmin']],
            ['key' => 'fitur/hak-akses-menu', 'name' => 'Hak Akses Menu', 'url' => '/fitur/hak-akses-menu', 'section' => 'Master Data'],
            ['key' => 'fitur/setting-web', 'name' => 'Setting Website', 'url' => '/fitur/setting-web', 'section' => 'Master Data'],
            ['key' => 'fitur/audit-log', 'name' => 'Log Activity', 'url' => '/fitur/audit-log', 'section' => 'Keamanan & Audit'],
            ['key' => 'fitur/backup-restore', 'name' => 'Backup & Restore', 'url' => '/fitur/backup-restore', 'section' => 'Sistem'],
            ['key' => 'fitur/import-export', 'name' => 'Import & Export User', 'url' => '/fitur/import-export', 'section' => 'Sistem'],
        ];

        $rawAllowedKeys = \App\Models\MenuPermission::query()
            ->select('menus.key')
            ->join('menus', 'menus.id', '=', 'menu_permissions.menu_id')
            ->where('menu_permissions.role', $userRole)
            ->where('menu_permissions.is_allowed', true)
            ->pluck('menus.key')
            ->map(fn ($key) => (string) $key)
            ->all();
        $allowedCanonicalKeys = collect($rawAllowedKeys)
            ->map(fn (string $key) => $canonicalKey($key))
            ->unique()
            ->values()
            ->all();
        foreach (['dashboard', 'profil'] as $alwaysVisibleKey) {
            if (! in_array($alwaysVisibleKey, $allowedCanonicalKeys, true)) {
                $allowedCanonicalKeys[] = $alwaysVisibleKey;
            }
        }

        $bySection = [];
        foreach ($menuCatalog as $item) {
            if ($userRole !== 'superadmin') {
                if (isset($item['allowed_roles']) && is_array($item['allowed_roles']) && ! in_array($userRole, $item['allowed_roles'], true)) {
                    continue;
                }
                if (isset($item['hidden_roles']) && is_array($item['hidden_roles']) && in_array($userRole, $item['hidden_roles'], true)) {
                    continue;
                }
            }
            if (! in_array($item['key'], $allowedCanonicalKeys, true)) {
                if (! \App\Support\MenuAccess::canAccess($userRole, $item['key'])) {
                    continue;
                }
            }
            $sectionName = $item['section_by_role'][$userRole] ?? $item['section'];
            $displayName = $item['name_by_role'][$userRole] ?? $item['name'];
            if (! isset($bySection[$sectionName])) {
                $bySection[$sectionName] = [
                    'section' => $sectionName,
                    'items' => [],
                ];
            }
            $bySection[$sectionName]['items'][] = [
                'name' => $displayName,
                'url' => $item['url'],
                'icon' => 'MN',
            ];
        }

        $sections = array_values(array_filter(array_values($bySection), fn ($section) => ! empty($section['items'])));
    }
    $ensureMenuItem = static function (array &$sections, string $sectionName, string $name, string $url): void {
        $sectionIndex = null;
        foreach ($sections as $idx => $section) {
            if (($section['section'] ?? '') === $sectionName) {
                $sectionIndex = $idx;
                break;
            }
        }
        if ($sectionIndex === null) {
            $sections[] = ['section' => $sectionName, 'items' => []];
            $sectionIndex = array_key_last($sections);
        }
        $items = $sections[$sectionIndex]['items'] ?? [];
        foreach ($items as $item) {
            if (($item['url'] ?? '') === $url) {
                return;
            }
        }
        array_unshift($items, ['name' => $name, 'url' => $url, 'icon' => 'MN']);
        $sections[$sectionIndex]['items'] = $items;
    };
    $ensureMenuItem($sections, 'Beranda', 'Dashboard', '/dashboard');
    $ensureMenuItem($sections, 'Beranda', 'Profil Saya', '/profil');

    $identityLabel = $rawUserRole === 'siswa' ? 'NIS' : 'NUPTK';
    $identityValue = $rawUserRole === 'siswa'
        ? (auth()->user()->nis ?? '-')
        : (auth()->user()->nuptk ?? auth()->user()->nis ?? '-');
    $authUser = auth()->user();
    $profilePhotoPath = trim((string) ($authUser->profile_photo_path ?? ''));
    $hasProfilePhoto = $profilePhotoPath !== '';
    $isExternalProfilePhoto = $hasProfilePhoto && (str_starts_with($profilePhotoPath, 'http://') || str_starts_with($profilePhotoPath, 'https://'));
    if ($hasProfilePhoto && ! $isExternalProfilePhoto) {
        $normalizedProfilePhotoPath = ltrim($profilePhotoPath, '/');
        if (is_file(public_path($normalizedProfilePhotoPath))) {
            $profilePhotoUrl = asset($normalizedProfilePhotoPath);
        } elseif (is_file(public_path('storage/'.$normalizedProfilePhotoPath))) {
            $profilePhotoUrl = asset('storage/'.$normalizedProfilePhotoPath);
        } else {
            $profilePhotoUrl = asset($normalizedProfilePhotoPath);
        }
    } else {
        $profilePhotoUrl = $profilePhotoPath;
    }
    $profileInitial = strtoupper(substr((string) ($authUser->name ?? 'U'), 0, 1));
    $canUseChatbot = \App\Support\MenuAccess::canAccess($userRole, 'chatbot');
@endphp
<div class="shell">
    <aside class="sidebar">
        <div class="brand">
            <img src="{{ asset($logoPath) }}" alt="Logo">
        </div>
        <nav class="menu">
            @foreach ($sections as $section)
                <div class="menu-section" data-menu-section>
                    <button type="button" class="menu-section-toggle" data-menu-toggle aria-expanded="false">
                        <span>{{ $section['section'] }}</span>
                        <span class="menu-section-caret">▼</span>
                    </button>
                    <div class="menu-section-items" data-menu-items>
                    @foreach ($section['items'] as $item)
                        @php
                            $menuKey = trim($item['url'], '/');
                            $permissionKey = $menuKey;
                            if (str_starts_with($permissionKey, 'fitur-admin/')) {
                                $permissionKey = 'fitur/'.substr($permissionKey, strlen('fitur-admin/'));
                            }
                            if ($permissionKey === 'fitur/laporan-grafik') {
                                $permissionKey = 'fitur-shared/laporan-grafik';
                            }
                            $isAllowed = \App\Support\MenuAccess::canAccess($userRole, $permissionKey);
                            $menuActiveCandidates = [$menuKey];
                            if (str_starts_with($menuKey, 'fitur-admin/')) {
                                $menuActiveCandidates[] = 'fitur/'.substr($menuKey, strlen('fitur-admin/'));
                            }
                            if ($menuKey === 'fitur-admin/laporan-grafik') {
                                $menuActiveCandidates[] = 'fitur-shared/laporan-grafik';
                            }
                            $isCoreMenu = in_array($menuKey, ['dashboard', 'profil'], true);
                            $isActiveMenu = false;
                            foreach ($menuActiveCandidates as $candidate) {
                                $allowWildcard = ! in_array($candidate, ['summary-report', 'validasi'], true);
                                if (request()->is($candidate) || ($allowWildcard && request()->is($candidate.'/*'))) {
                                    $isActiveMenu = true;
                                    break;
                                }
                            }
                        @endphp
                        @continue(! $isAllowed && ! $isCoreMenu)
                        <a class="{{ $isActiveMenu ? 'active' : '' }}" href="{{ $item['url'] }}">
                            {{ $item['name'] }}
                        </a>
                    @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
        <div class="sidebar-bottom">
            <span class="sidebar-bottom-label" id="sidebar-theme-label">Mode Gelap</span>
            <label class="switch" for="ui-theme-switch">
                <input type="checkbox" id="ui-theme-switch">
                <span class="slider"></span>
            </label>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="topbar-user">
                <div class="topbar-avatar">
                    @if ($hasProfilePhoto)
                        <img src="{{ $profilePhotoUrl }}" alt="Profile">
                    @else
                        {{ $profileInitial }}
                    @endif
                </div>
                <div class="topbar-user-text">
                    <div class="topbar-greeting">
                        <span>{{ $greeting }},</span>
                        <strong>{{ auth()->user()->name }}</strong>
                    </div>
                    <small>{{ $identityLabel }}: {{ $identityValue }}</small>
                </div>
            </div>
            <div class="topbar-actions">
                <div class="radio-inputs" aria-label="Language switch">
                    <label class="radio">
                        <input id="ui-lang-id" type="radio" name="ui-lang-switch" value="id" checked>
                        <span class="radio-item" id="ui-lang-id-label">IND</span>
                    </label>
                    <label class="radio">
                        <input id="ui-lang-en" type="radio" name="ui-lang-switch" value="en">
                        <span class="radio-item" id="ui-lang-en-label">ENG</span>
                    </label>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="logout-btn" type="submit">Logout</button>
                </form>
            </div>
        </header>
        <section class="content">
            @yield('content')
        </section>
    </main>
</div>
<div id="app-dialog-backdrop" class="app-dialog-backdrop" aria-hidden="true">
    <section class="app-dialog" role="dialog" aria-modal="true" aria-labelledby="app-dialog-title">
        <header class="app-dialog-head" id="app-dialog-title">Pemberitahuan</header>
        <div id="app-dialog-message" class="app-dialog-body"></div>
        <div id="app-dialog-input-wrap" class="app-dialog-input-wrap">
            <input id="app-dialog-input" class="app-dialog-input" type="text">
        </div>
        <div class="app-dialog-actions">
            <button id="app-dialog-cancel" type="button" class="app-dialog-btn">Batal</button>
            <button id="app-dialog-ok" type="button" class="app-dialog-btn app-dialog-btn-primary">OK</button>
        </div>
    </section>
</div>
@if ($canUseChatbot)
    <button id="chatbot-toggle" class="chatbot-toggle" type="button">Chatbot Asisten</button>
    <section id="chatbot-panel" class="chatbot-panel" aria-live="polite">
        <div class="chatbot-header">
            <span id="chatbot-title">Chatbot Asisten</span>
        </div>
        <div id="chatbot-messages" class="chatbot-messages"></div>
        <div class="chatbot-input-wrap">
            <input id="chatbot-input" class="chatbot-input" type="text" placeholder="Tanya cara pakai menu..." maxlength="2000">
            <button id="chatbot-send" type="button">Kirim</button>
        </div>
    </section>
@endif
<script>
    (function () {
        const STORAGE_LANG_KEY = 'ui_lang';
        const STORAGE_THEME_KEY = 'ui_theme';
        const STORAGE_GEO_LAT_KEY = 'ui_geo_lat';
        const STORAGE_GEO_LNG_KEY = 'ui_geo_lng';
        const languageSwitches = document.querySelectorAll('input[name="ui-lang-switch"]');
        const languageIdLabel = document.getElementById('ui-lang-id-label');
        const languageEnLabel = document.getElementById('ui-lang-en-label');
        const themeSwitch = document.getElementById('ui-theme-switch');
        const sidebarThemeLabel = document.getElementById('sidebar-theme-label');
        const menuSections = Array.from(document.querySelectorAll('[data-menu-section]'));
        const MENU_SECTION_STORAGE_KEY = 'ui_sidebar_open_sections';
        const originalTextMap = new WeakMap();
        const originalTitle = document.title;
        const chatbotUserRole = @json($userRole ?? 'siswa');
        const chatbotEnabled = @json($canUseChatbot ?? false);
        const chatbotHistoryUrl = @json(route('chatbot.history'));
        const chatbotMessageUrl = @json(route('chatbot.message'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const dialogBackdrop = document.getElementById('app-dialog-backdrop');
        const dialogTitle = document.getElementById('app-dialog-title');
        const dialogMessage = document.getElementById('app-dialog-message');
        const dialogOkBtn = document.getElementById('app-dialog-ok');
        const dialogCancelBtn = document.getElementById('app-dialog-cancel');
        const dialogInputWrap = document.getElementById('app-dialog-input-wrap');
        const dialogInput = document.getElementById('app-dialog-input');
        let activeDialogResolver = null;
        let activeDialogType = null;
        let activeDialogReturnFocus = null;

        const localText = (id, en) => ((localStorage.getItem(STORAGE_LANG_KEY) || 'id') === 'en' ? en : id);
        const showAppAlert = (message, options = {}) => window.AppDialog.alert(message, options);
        const showAppConfirm = (message, options = {}) => window.AppDialog.confirm(message, options);
        const showAppPrompt = (message, options = {}) => window.AppDialog.prompt(message, options);

        function readOpenSections() {
            try {
                const raw = localStorage.getItem(MENU_SECTION_STORAGE_KEY) || '[]';
                const parsed = JSON.parse(raw);
                return Array.isArray(parsed) ? parsed : [];
            } catch (_) {
                return [];
            }
        }

        function saveOpenSections(names) {
            try {
                localStorage.setItem(MENU_SECTION_STORAGE_KEY, JSON.stringify(names));
            } catch (_) {}
        }

        function setupSidebarAccordion() {
            if (menuSections.length === 0) return;

            const savedOpen = readOpenSections();
            const defaultOpen = new Set(savedOpen);

            menuSections.forEach((section) => {
                const toggle = section.querySelector('[data-menu-toggle]');
                const title = toggle?.querySelector('span')?.textContent?.trim() || '';
                const hasActiveLink = !!section.querySelector('a.active');
                const shouldOpen = hasActiveLink || defaultOpen.has(title);
                section.classList.toggle('open', shouldOpen);
                if (toggle) {
                    toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
                }
            });

            menuSections.forEach((section) => {
                const toggle = section.querySelector('[data-menu-toggle]');
                if (!toggle) return;

                toggle.addEventListener('click', function () {
                    const currentlyOpen = section.classList.contains('open');
                    section.classList.toggle('open', !currentlyOpen);
                    toggle.setAttribute('aria-expanded', currentlyOpen ? 'false' : 'true');

                    const openNames = menuSections
                        .filter((item) => item.classList.contains('open'))
                        .map((item) => item.querySelector('[data-menu-toggle] span')?.textContent?.trim() || '')
                        .filter((name) => name !== '');
                    saveOpenSections(openNames);
                });
            });
        }

        setupSidebarAccordion();

        function closeDialog(result) {
            if (!dialogBackdrop?.classList.contains('is-open')) return;

            dialogBackdrop.classList.remove('is-open');
            dialogBackdrop.setAttribute('aria-hidden', 'true');

            if (activeDialogResolver) {
                const resolve = activeDialogResolver;
                activeDialogResolver = null;
                const type = activeDialogType;
                activeDialogType = null;
                resolve(type === 'prompt' ? result : Boolean(result));
            }

            if (activeDialogReturnFocus && typeof activeDialogReturnFocus.focus === 'function') {
                activeDialogReturnFocus.focus();
            }
            activeDialogReturnFocus = null;
        }

        function showDialog(type, message, options = {}) {
            if (!dialogBackdrop || !dialogTitle || !dialogMessage || !dialogOkBtn || !dialogCancelBtn || !dialogInputWrap || !dialogInput) {
                if (type === 'confirm') return Promise.resolve(window.confirm(String(message ?? '')));
                if (type === 'prompt') return Promise.resolve(window.prompt(String(message ?? ''), String(options.defaultValue ?? '')));
                window.alert(String(message ?? ''));
                return Promise.resolve(true);
            }

            if (activeDialogResolver) {
                closeDialog(type === 'prompt' ? null : false);
            }

            activeDialogType = type;
            activeDialogReturnFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            dialogTitle.textContent = options.title || (
                type === 'confirm'
                    ? localText('Konfirmasi', 'Confirmation')
                    : (type === 'prompt' ? localText('Input Diperlukan', 'Input Required') : localText('Pemberitahuan', 'Notice'))
            );
            dialogMessage.textContent = String(message ?? '');

            const showCancel = type !== 'alert';
            const showInput = type === 'prompt';
            dialogCancelBtn.style.display = showCancel ? '' : 'none';
            dialogInputWrap.classList.toggle('is-visible', showInput);
            dialogInput.type = options.inputType || 'text';
            dialogInput.placeholder = options.placeholder || '';
            dialogInput.value = String(options.defaultValue ?? '');
            dialogOkBtn.textContent = options.okText || localText('OK', 'OK');
            dialogCancelBtn.textContent = options.cancelText || localText('Batal', 'Cancel');

            dialogBackdrop.classList.add('is-open');
            dialogBackdrop.setAttribute('aria-hidden', 'false');
            if (showInput) {
                window.setTimeout(() => dialogInput.focus(), 0);
            } else {
                window.setTimeout(() => dialogOkBtn.focus(), 0);
            }

            return new Promise((resolve) => {
                activeDialogResolver = resolve;
            });
        }

        if (dialogOkBtn) {
            dialogOkBtn.addEventListener('click', () => {
                if (activeDialogType === 'prompt') {
                    closeDialog(dialogInput.value);
                    return;
                }
                closeDialog(true);
            });
        }

        if (dialogCancelBtn) {
            dialogCancelBtn.addEventListener('click', () => {
                closeDialog(activeDialogType === 'prompt' ? null : false);
            });
        }

        if (dialogBackdrop) {
            dialogBackdrop.addEventListener('click', (event) => {
                if (event.target !== dialogBackdrop) return;
                closeDialog(activeDialogType === 'prompt' ? null : false);
            });
        }

        if (dialogInput) {
            dialogInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    closeDialog(dialogInput.value);
                }
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            if (!dialogBackdrop?.classList.contains('is-open')) return;
            closeDialog(activeDialogType === 'prompt' ? null : false);
        });

        window.AppDialog = {
            alert(message, options = {}) {
                return showDialog('alert', message, options).then(() => true);
            },
            confirm(message, options = {}) {
                return showDialog('confirm', message, options).then((ok) => Boolean(ok));
            },
            prompt(message, options = {}) {
                return showDialog('prompt', message, options).then((value) => value === null ? null : String(value));
            },
        };

        @if (session('success'))
            window.setTimeout(() => {
                window.AppDialog.alert(@json(session('success')), {
                    title: localText('Berhasil', 'Success'),
                });
            }, 120);
        @endif

        @if (session('error'))
            window.setTimeout(() => {
                window.AppDialog.alert(@json(session('error')), {
                    title: localText('Gagal', 'Error'),
                });
            }, 120);
        @endif

        const phrasePairs = [
            ['Dashboard', 'Dashboard'],
            ['Dasbor', 'Dashboard'],
            ['Profil Saya', 'My Profile'],
            ['Hak Akses Menu', 'Menu Permissions'],
            ['Setting Website', 'Website Settings'],
            ['Lokasi PKL', 'Internship Locations'],
            ['Peta menggunakan Leaflet + OpenStreetMap.', 'Map uses Leaflet + OpenStreetMap.'],
            ['Cari Lokasi PT', 'Search Company Location'],
            ['Ketik minimal 3 huruf.', 'Type at least 3 characters.'],
            ['Peta Lokasi (klik peta untuk pilih titik)', 'Location Map (click map to pick coordinate)'],
            ['Cari lokasi otomatis memakai OpenStreetMap.', 'Location search uses OpenStreetMap.'],
            ['Setting Web', 'Website Settings'],
            ['Pengaturan Website', 'Website Configuration'],
            ['Identitas', 'Identity'],
            ['Kontak & Footer', 'Contact & Footer'],
            ['Absensi', 'Attendance'],
            ['Logo & Favicon', 'Logo & Favicon'],
            ['Identitas Website', 'Website Identity'],
            ['Nama Website', 'Website Name'],
            ['Alamat', 'Address'],
            ['Nama Manager', 'Manager Name'],
            ['Manager/Penanggung Jawab', 'Manager/Person in Charge'],
            ['Footer', 'Footer'],
            ['Teks Footer', 'Footer Text'],
            ['Footer Link 1 (Nama)', 'Footer Link 1 (Label)'],
            ['Footer Link 1 (URL)', 'Footer Link 1 (URL)'],
            ['Footer Link 2 (Nama)', 'Footer Link 2 (Label)'],
            ['Footer Link 2 (URL)', 'Footer Link 2 (URL)'],
            ['Footer Link 3 (Nama)', 'Footer Link 3 (Label)'],
            ['Footer Link 3 (URL)', 'Footer Link 3 (URL)'],
            ['Konfigurasi Absensi', 'Attendance Configuration'],
            ['Timezone Absensi', 'Attendance Timezone'],
            ['Jam Check-in Mulai', 'Check-in Start Time'],
            ['Jam Check-in Selesai', 'Check-in End Time'],
            ['Jam Check-out Mulai', 'Check-out Start Time'],
            ['Jam Check-out Selesai', 'Check-out End Time'],
            ['Branding', 'Branding'],
            ['Logo Baru (opsional)', 'New Logo (optional)'],
            ['Favicon Baru (opsional)', 'New Favicon (optional)'],
            ['Jika kosong, favicon otomatis mengikuti logo terbaru.', 'If empty, favicon will automatically follow the latest logo.'],
            ['Logo', 'Logo'],
            ['Favicon', 'Favicon'],
            ['Simpan Setting', 'Save Settings'],
            ['Setting website berhasil diperbarui.', 'Website settings updated successfully.'],
            ['Setting web berhasil disimpan.', 'Website settings saved successfully.'],
            ['Log Activity', 'Activity Log'],
            ['Log Activity User', 'User Activity Log'],
            ['Monitoring Exception', 'Exception Monitoring'],
            ['Notif Discord', 'Discord Notifications'],
            ['Laporan', 'Reports'],
            ['Backup & Restore', 'Backup & Restore'],
            ['Backup & Restore Database', 'Database Backup & Restore'],
            ['Backup SQL', 'SQL Backup'],
            ['Restore Database', 'Database Restore'],
            ['Delete Isi Table', 'Delete Table Data'],
            ['Buat Backup Database (.sql)', 'Create Database Backup (.sql)'],
            ['Mode backup', 'Backup mode'],
            ['Mode restore', 'Restore mode'],
            ['Mode hapus', 'Delete mode'],
            ['1 Table', '1 Table'],
            ['Semua Table', 'All Tables'],
            ['Pilih tabel untuk backup', 'Select table for backup'],
            ['Pilih tabel tujuan restore', 'Select target table for restore'],
            ['Pilih tabel yang akan dihapus isinya', 'Select table to clear data'],
            ['-- Pilih Tabel --', '-- Select Table --'],
            ['Jika mode "Semua Table", pilihan tabel boleh diabaikan.', 'If mode is "All Tables", table selection can be ignored.'],
            ['Buat Backup (.sql)', 'Create Backup (.sql)'],
            ['Upload file SQL untuk restore', 'Upload SQL file for restore'],
            ['Upload file SQL untuk restore tabel', 'Upload SQL file to restore table'],
            ['Restore Dari File SQL', 'Restore From SQL File'],
            ['Aksi ini akan menghapus isi tabel database. Ketik', 'This action will delete all database table contents. Type'],
            ['Mode 1 Table: ketik', '1 Table mode: type'],
            ['Mode Semua Table: ketik', 'All Tables mode: type'],
            ['Mode All Tables: ketik', 'All Tables mode: type'],
            ['Jika mode "All Tables", pilihan tabel boleh diabaikan.', 'If mode is "All Tables", table selection can be ignored.'],
            ['HAPUS DATA TABEL', 'DELETE TABLE DATA'],
            ['HAPUS SEMUA DATA', 'DELETE ALL DATA'],
            ['HAPUS DATA TABEL atau HAPUS SEMUA DATA', 'DELETE TABLE DATA or DELETE ALL DATA'],
            ['Hapus Data', 'Delete Data'],
            ['untuk konfirmasi.', 'to confirm.'],
            ['Delete Isi Semua Table', 'Delete All Table Data'],
            ['Delete Isi 1 Table', 'Delete 1 Table Data'],
            ['Tabel yang dipilih tidak valid.', 'Selected table is invalid.'],
            ['Konfirmasi tidak cocok. Ketik HAPUS DATA TABEL.', 'Confirmation does not match. Type HAPUS DATA TABEL.'],
            ['Konfirmasi tidak cocok. Ketik HAPUS SEMUA DATA.', 'Confirmation does not match. Type HAPUS SEMUA DATA.'],
            ['Konfirmasi tidak cocok. Ketik HAPUS DATA TABEL atau DELETE TABLE DATA.', 'Confirmation does not match. Type HAPUS DATA TABEL or DELETE TABLE DATA.'],
            ['Konfirmasi tidak cocok. Ketik HAPUS SEMUA DATA atau DELETE ALL DATA.', 'Confirmation does not match. Type HAPUS SEMUA DATA or DELETE ALL DATA.'],
            ['Backup seluruh tabel berhasil dibuat.', 'Backup for all tables was created successfully.'],
            ['Restore seluruh tabel dari file SQL berhasil.', 'Restore for all tables from SQL file was successful.'],
            ['Isi seluruh tabel berhasil dihapus.', 'All table data was deleted successfully.'],
            ['Backup tabel ', 'Backup table '],
            ['Restore tabel ', 'Restore table '],
            ['Isi tabel ', 'Table data '],
            [' dari file SQL berhasil.', ' restored successfully from SQL file.'],
            [' berhasil dihapus.', ' deleted successfully.'],
            [' berhasil dibuat.', ' created successfully.'],
            ['Riwayat Backup', 'Backup History'],
            ['Nama File', 'File Name'],
            ['Tipe', 'Type'],
            ['Dibuat Oleh', 'Created By'],
            ['Restore Terakhir', 'Last Restore'],
            ['Aksi', 'Action'],
            ['Belum ada backup.', 'No backups yet.'],
            [' oleh ', ' by '],
            ['Import & Export User', 'User Import'],
            ['Nama PT', 'Company Name'],
            ['Cari Lokasi PT (Google Maps)', 'Search Company Location (Google Maps)'],
            ['Contoh: PT Telkom Indonesia Jakarta', 'Example: PT Telkom Indonesia Jakarta'],
            ['Nama PT / Tempat PKL', 'Company / Internship Place Name'],
            ['Alamat', 'Address'],
            ['Latitude', 'Latitude'],
            ['Longitude', 'Longitude'],
            ['Radius Absensi (meter)', 'Attendance Radius (meters)'],
            ['IP Referensi (opsional)', 'Reference IP (optional)'],
            ['Simpan Lokasi', 'Save Location'],
            ['Update Lokasi', 'Update Location'],
            ['Batal Edit', 'Cancel Edit'],
            ['Tambah Lokasi PKL', 'Add Internship Location'],
            ['Daftar Lokasi PKL', 'Internship Location List'],
            ['Detail Lokasi PKL', 'Internship Location Details'],
            ['Search Location', 'Cari Lokasi'],
            ['Type company, address, coordinate, IP...', 'Ketik perusahaan, alamat, koordinat, IP...'],
            ['0 selected', '0 dipilih'],
            ['Titik', 'Coordinate'],
            ['Radius', 'Radius'],
            ['IP Referensi', 'Reference IP'],
            ['Simpan Perubahan', 'Save Changes'],
            ['Belum ada data lokasi PKL.', 'No internship location data yet.'],
            ['Lokasi PKL berhasil ditambahkan.', 'Internship location created successfully.'],
            ['Lokasi PKL berhasil diperbarui.', 'Internship location updated successfully.'],
            ['Lokasi PKL berhasil dihapus.', 'Internship location deleted successfully.'],
            ['Delete selected locations?', 'Hapus lokasi yang dipilih?'],
            ['Restore selected locations?', 'Restore lokasi yang dipilih?'],
            ['Permanently delete selected locations?', 'Hapus permanen lokasi yang dipilih?'],
            ['Select at least one location first.', 'Pilih minimal satu lokasi terlebih dahulu.'],
            ['Restore Selected', 'Restore Dipilih'],
            ['Delete Permanent Selected', 'Delete Permanent Dipilih'],
            ['Google Maps API Key belum diatur. Isi', 'Google Maps API Key is not configured. Set'],
            ['pada file', 'in file'],
            ['Hapus lokasi ini?', 'Delete this location?'],
            ['Total Siswa:', 'Total Students:'],
            ['Total Guru:', 'Total Teachers:'],
            ['Total Kepsek:', 'Total Principals:'],
            ['Data User (Siswa/Guru/Kepsek)', 'User Data (Student/Teacher/Principal)'],
            ['Data Siswa', 'Student Data'],
            ['Download CSV Siswa', 'Download Students CSV'],
            ['Import CSV Siswa', 'Import Students CSV'],
            ['Impor CSV User', 'Import CSV User'],
            ['Impor CSV', 'Import CSV'],
            ['Processing row 0/0', 'Memproses baris 0/0'],
            ['Optional column order only: username;password;firstname;lastname;email;role', 'Opsional urutan kolom saja: username;password;firstname;lastname;email;role'],
            ['Optional filter role/class: guru,RPL XI,AKL XI,BDP XI', 'Opsional filter role/kelas: guru,RPL XI,AKL XI,BDP XI'],
            ['role otomatis', 'role auto'],
            ['Opsional urutan kolom: username;password;firstname;lastname;email;role', 'Optional column order: username;password;firstname;lastname;email;role'],
            ['Opsional filter kelas siswa: 11,12', 'Optional student class filter: 11,12'],
            ['Opsional filter role/kelas: guru,rpl xi,akl xi,bdp xi', 'Optional role/class filter: teacher,rpl xi,akl xi,bdp xi'],
            ['Jika urutan kolom file tidak standar, isi kolom "urutan kolom" sesuai file.', 'If your file column order is non-standard, fill the "column order" field accordingly.'],
            ['Jika ingin hanya siswa kelas tertentu, isi "filter kelas" (contoh: `11,12`).', 'If you only want specific student classes, fill "class filter" (example: `11,12`).'],
            ['Jika ingin filter role/kelas tertentu, isi "filter kelas" (contoh: `guru,rpl xi,akl xi,bdp xi` atau `11,12`).', 'If you want specific role/class filter, fill "class filter" (example: `teacher,rpl xi,akl xi,bdp xi` or `11,12`).'],
            ['File CSV users gagal dibaca.', 'Failed to read users CSV file.'],
            ['Import siswa selesai:', 'Student import completed:'],
            ['baris diproses,', 'rows processed,'],
            ['baris dilewati.', 'rows skipped.'],
            ['Catatan:', 'Note:'],
            ['wajib membuat data master jurusan (department) dan kelas terlebih dahulu sebelum import user.', 'you must create department and class master data before importing users.'],
            ['Laporan Kehadiran', 'Attendance Report'],
            ['Laporan Cetak', 'Print Report'],
            ['Jurusan', 'Department'],
            ['Pilih Jurusan', 'Select Department'],
            ['Mingguan', 'Weekly'],
            ['Bulanan', 'Monthly'],
            ['Tahunan', 'Yearly'],
            ['Tipe Diagram', 'Chart Type'],
            ['Batang', 'Bar'],
            ['Tampilkan', 'Show'],
            ['Pilih jurusan terlebih dahulu untuk menampilkan data.', 'Select a department first to display data.'],
            ['Pilih jurusan terlebih dahulu untuk melihat data.', 'Select a department first to view data.'],
            ['Pilih jurusan terlebih dahulu untuk menampilkan analisis.', 'Select a department first to display analysis.'],
            ['Daftar Laporan Harian Menunggu Review Anda', 'List of Daily Reports Awaiting Your Review'],
            ['Belum ada laporan harian yang menunggu review Anda.', 'No daily reports are waiting for your review.'],
            ['Detail Laporan', 'Report Details'],
            ['Status review:', 'Review status:'],
            ['No data in this category.', 'Tidak ada data pada kategori ini.'],
            ['Attendance Validation', 'Validasi Absensi'],
            ['Pending Check-in', 'Menunggu Check-in'],
            ['Pending Check-out', 'Menunggu Check-out'],
            ['Date From', 'Tanggal Dari'],
            ['Date To', 'Tanggal Sampai'],
            ['Attendance Details', 'Detail Absensi'],
            ['Detail Absensi', 'Attendance Details'],
            ['Photo Preview', 'Preview Foto'],
            ['Preview Foto', 'Photo Preview'],
            ['View Check-in Location', 'Lihat Lokasi Check-in'],
            ['View Check-out Location', 'Lihat Lokasi Check-out'],
            ['Check-in Validation', 'Validasi Check-in'],
            ['Check-out Validation', 'Validasi Check-out'],
            ['Check-in Photo', 'Foto Check-in'],
            ['Check-out Evidence Photo', 'Bukti Foto Check-out'],
            ['Approve Check-in', 'Setujui Check-in'],
            ['Reject Check-in', 'Tolak Check-in'],
            ['Approve Check-out', 'Setujui Check-out'],
            ['Reject Check-out', 'Tolak Check-out'],
            ['Check-out summary:', 'Ringkasan checkout:'],
            ['Apply', 'Terapkan'],
            ['Reset', 'Reset'],
            ['Pembimbing Sekolah', 'School Mentor'],
            ['Pembimbing', 'Field Mentor'],
            ['Kajur', 'Head of Department'],
            ['Filter Pengecualian', 'Exception Filter'],
            ['Jenis Pengecualian', 'Exception Type'],
            ['Tingkat Keparahan', 'Severity Level'],
            ['Semua Tingkat', 'All Levels'],
            ['Tanggal Mulai', 'Start Date'],
            ['Tanggal Akhir', 'End Date'],
            ['Jumlah Data', 'Rows'],
            ['Daftar Pengecualian', 'Exception List'],
            ['Tidak ada data pengecualian.', 'No exception data found.'],
            ['Filter Exception', 'Exception Filter'],
            ['Semua Jenis', 'All Types'],
            ['Semua Severity', 'All Severities'],
            ['Semua Role', 'All Roles'],
            ['Daftar Exception', 'Exception List'],
            ['Keparahan', 'Severity'],
            ['rendah', 'low'],
            ['sedang', 'medium'],
            ['tinggi', 'high'],
            ['Terbuka', 'Open'],
            ['Selesai', 'Resolved'],
            ['Semua', 'All'],
            ['Detail', 'Details'],
            ['Tidak ada exception.', 'No exceptions found.'],
            ['Terapkan', 'Apply'],
            ['Hak Akses Menu (Checklist)', 'Menu Permissions (Checklist)'],
            ['Cari Menu', 'Search Menu'],
            ['Ketik nama menu...', 'Type menu name...'],
            ['Filter Role', 'Filter Role'],
            ['Pilih Semua', 'Select All'],
            ['Simpan Hak Akses', 'Save Permissions'],
            ['Hak akses menu berhasil disimpan.', 'Menu permissions saved successfully.'],
            ['Admin Sekolah', 'School Admin'],
            ['Siswa', 'Student'],
            ['Pembimbing Sekolah', 'School Mentor'],
            ['Pembimbing', 'Field Mentor'],
            ['Wali Kelas', 'Homeroom Teacher'],
            ['Kesiswaan', 'Student Affairs'],
            ['Kepsek', 'Principal'],
            ['Jam', 'Time'],
            ['Jarak (m)', 'Distance (m)'],
            ['Radius (m)', 'Radius (m)'],
            ['IP Referensi', 'Reference IP'],
            ['IP Aktual', 'Actual IP'],
            ['Check in di luar jam PKL', 'Check-in outside internship hours'],
            ['Check out di luar jam PKL', 'Check-out outside internship hours'],
            ['Check out tanpa check in', 'Check-out without check-in'],
            ['Belum check out', 'No check-out yet'],
            ['Di luar radius PKL', 'Outside internship radius'],
            ['Lokasi PKL belum ditentukan', 'Internship location is not set'],
            ['IP tidak sesuai referensi', 'IP does not match reference'],
            ['Laporan harian kosong', 'Daily report is empty'],
            ['Tanggal Absensi', 'Attendance Date'],
            ['Tanggal:', 'Date:'],
            ['Status review:', 'Review status:'],
            ['Catatan approve (opsional)', 'Approval note (optional)'],
            ['Catatan revisi (wajib)', 'Revision note (required)'],
            ['Minta Revisi', 'Request Revision'],
            ['Generate:', 'Generated:'],
            ['Gunakan menu print browser lalu pilih "Save as PDF".', 'Use browser print menu, then select "Save as PDF".'],
            ['Kembali', 'Back'],
            ['Hadir', 'Present'],
            ['Alpha', 'Absent'],
            ['Menunggu', 'Pending'],
            ['Diagram Kehadiran', 'Attendance Chart'],
            ['Data diagram khusus jurusan:', 'Chart data scoped to department:'],
            ['Hadir Hari Ini', 'Present Today'],
            ['Menunggu Validasi', 'Pending Validation'],
            ['Menunggu Tinjau Laporan', 'Pending Report Review'],
            ['Pengecualian Hari Ini', 'Today Exceptions'],
            ['SLA Terlewati (Menunggu)', 'SLA Overdue (Pending)'],
            ['SLA Terlewati (Pending)', 'SLA Overdue (Pending)'],
            ['Validasi Mingguan Disetujui', 'Weekly Validation Approved'],
            ['Validasi Mingguan Revisi', 'Weekly Validation Revision'],
            ['Validation Weekly Disetujui', 'Weekly Validation Approved'],
            ['Validation Weekly Revisi', 'Weekly Validation Revision'],
            ['Total Siswa PKL Aktif', 'Total Active PKL Students'],
            ['Total Student PKL Active', 'Total Active PKL Students'],
            ['Alpha Hari Ini', 'Absent Today'],
            ['Absent Hari Ini', 'Absent Today'],
            ['Present Hari Ini', 'Present Today'],
            ['Siswa Binaan Aktif', 'Active Supervised Students'],
            ['Pending Tinjau Reports', 'Pending Report Review'],
            ['Mode', 'Mode'],
            ['HARIAN', 'DAILY'],
            ['BULANAN', 'MONTHLY'],
            ['BULAN INI', 'THIS MONTH'],
            ['BULAN LALU', 'LAST MONTH'],
            ['HARI INI', 'TODAY'],
            ['KEMARIN', 'YESTERDAY'],
            ['Garis', 'Line'],
            ['Pai', 'Pie'],
            ['Donat', 'Doughnut'],
            ['Pembimbing Sekolah', 'School Mentor'],
            ['Validasi laporan, monitoring progres, dan isi catatan untuk siswa bimbingan sekolah.', 'Validate reports, monitor progress, and write notes for assigned school-mentored students.'],
            ['Siswa yang ditentukan kajur (atau semua siswa jurusan bila mode semua siswa aktif).', 'Students assigned by Head of Department (or all department students when all-students mode is active).'],
            ['Validasi mingguan dan rekap siswa bimbingan sekolah.', 'Weekly validation and recap for school-mentored students.'],
            ['Seluruh siswa di jurusannya.', 'All students in their department.'],
            ['Monitoring kehadiran dan catatan siswa.', 'Monitor attendance and student notes.'],
            ['Hanya siswa di kelasnya.', 'Only students in their class.'],
            ['Monitoring dan rekap.', 'Monitoring and recap.'],
            ['Pilih jurusan dulu, lalu akses data jurusan tersebut.', 'Select department first, then access its data.'],
            ['Cakupan data:', 'Data scope:'],
            ['Akses Tambahan', 'Additional Access'],
            ['Tidak ada pending absensi.', 'No pending attendance.'],
            ['Tidak ada pending pengajuan.', 'No pending requests.'],
            ['Tidak ada pending laporan.', 'No pending reports.'],
            ['Tidak ada data pending validasi kehadiran.', 'No pending attendance validation data.'],
            ['Select jurusan untuk menampilkan data siswa.', 'Select a department to display student data.'],
            ['Data Kelas Wali', 'Homeroom Class Data'],
            ['Total Siswa Kelas', 'Total Students in Class'],
            ['Kelas wali belum diatur. Silakan isi `class_name` pada akun wali kelas.', 'Homeroom class is not set. Please fill `class_name` on the homeroom account.'],
            ['Kelas:', 'Class:'],
            ['Kelas Wali:', 'Homeroom Class:'],
            ['Total Siswa:', 'Total Students:'],
            ['Pengajuan Hari Ini', 'Requests Today'],
            ['Belum Check-in', 'Not Yet Check-in'],
            ['Belum ada siswa pada kelas ini.', 'No students in this class yet.'],
            ['Monitoring Harian', 'Daily Monitoring'],
            ['Sudah', 'Done'],
            ['Belum', 'Not Yet'],
            ['Belum ada data monitoring harian.', 'No daily monitoring data yet.'],
            ['Siswa Alpha Terbanyak', 'Students with Highest Absence'],
            ['Siswa Pending Validasi', 'Students Pending Validation'],
            ['Hari Pending', 'Pending Days'],
            ['Top 10 Siswa Risiko Tertinggi', 'Top 10 Highest Risk Students'],
            ['Smart Alert Kelas', 'Class Smart Alerts'],
            ['Alpha > 2 hari', 'Absence > 2 days'],
            ['Pending > 2 hari', 'Pending > 2 days'],
            ['Report kosong > 2 hari', 'Empty report > 2 days'],
            ['Keluar radius > 2 kali', 'Outside radius > 2 times'],
            ['hari', 'days'],
            ['kali', 'times'],
            ['Manajemen Pengguna', 'User Management'],
            ['Tambah Akademik', 'Academic Additions'],
            ['Tambah Jurusan & Kelas', 'Add Department & Class'],
            ['Tambah Jurusan', 'Add Department'],
            ['Tambah Kelas', 'Add Class'],
            ['Aksi', 'Action'],
            ['Detail', 'Details'],
            ['Edit', 'Edit'],
            ['Hapus', 'Delete'],
            ['Nama Jurusan', 'Department Name'],
            ['Nama Kelas', 'Class Name'],
            ['Tambah Jurusan', 'Add Department'],
            ['Tambah Kelas', 'Add Class'],
            ['Belum ada jurusan.', 'No departments yet.'],
            ['Belum ada kelas.', 'No classes yet.'],
            ['Jurusan berhasil ditambahkan.', 'Department added successfully.'],
            ['Kelas berhasil ditambahkan.', 'Class added successfully.'],
            ['Jurusan berhasil diperbarui.', 'Department updated successfully.'],
            ['Jurusan berhasil dihapus.', 'Department deleted successfully.'],
            ['Kelas berhasil diperbarui.', 'Class updated successfully.'],
            ['Kelas berhasil dihapus.', 'Class deleted successfully.'],
            ['Jurusan tidak bisa dihapus karena masih dipakai pada data kelas.', 'Department cannot be deleted because it is still used by class data.'],
            ['Hapus jurusan ini?', 'Delete this department?'],
            ['Hapus kelas ini?', 'Delete this class?'],
            ['Tambah User', 'Add User'],
            ['Tambah Siswa', 'Add Student'],
            ['Tambah Student', 'Add Student'],
            ['Tambah Guru/Staff', 'Add Teacher/Staff'],
            ['Daftar User', 'User List'],
            ['Aktif', 'Active'],
            ['Cari User', 'Search User'],
            ['Ketik nama, NIS, NUPTK, email...', 'Type name, NIS, NUPTK, email...'],
            ['Filter Role', 'Filter Role'],
            ['Semua Role', 'All Roles'],
            ['Tampilkan', 'Show'],
            ['Pilih Semua', 'Select All'],
            ['dipilih', 'selected'],
            ['Delete Dipilih', 'Delete Selected'],
            ['Restore Dipilih', 'Restore Selected'],
            ['Delete Permanent Dipilih', 'Delete Permanent Selected'],
            ['Pilih', 'Select'],
            ['Terhapus', 'Deleted'],
            ['Pilih Tempat PKL', 'Select Internship Location'],
            ['Pilih Kelas', 'Select Class'],
            ['Pilih Jurusan', 'Select Department'],
            ['Set Tempat PKL', 'Set Internship Location'],
            ['Tempat PKL', 'Internship Location'],
            ['Telepon', 'Phone'],
            ['Nama', 'Name'],
            ['Kelas', 'Class'],
            ['Jurusan', 'Department'],
            ['Semua Murid Jurusan', 'All Students in Department'],
            ['Aksi', 'Action'],
            ['Detail User', 'User Details'],
            ['Tutup', 'Close'],
            ['NIS (wajib untuk siswa)', 'NIS (required for student)'],
            ['NUPTK (wajib selain siswa)', 'NUPTK (required for non-student)'],
            ['Kelas (opsional untuk semua role)', 'Class (optional for all roles)'],
            ['Jurusan (contoh: RPL)', 'Department (example: RPL)'],
            ['No WA (opsional)', 'WhatsApp Number (optional)'],
            ['Password baru (opsional)', 'New password (optional)'],
            ['Reset Password', 'Reset Password'],
            ['Konfirmasi Delete', 'Delete Confirmation'],
            ['Yakin ingin menghapus user ini?', 'Are you sure you want to delete this user?'],
            ['Nama Siswa', 'Student Name'],
            ['Kelas (contoh: XII RPL 1)', 'Class (example: XII RPL 1)'],
            ['Role: siswa (otomatis)', 'Role: student (automatic)'],
            ['Save Siswa', 'Save Student'],
            ['Nama Guru/Staff', 'Teacher/Staff Name'],
            ['Kelas Binaan (opsional untuk semua role)', 'Managed Class (optional for all roles)'],
            ['Jurusan (opsional)', 'Department (optional)'],
            ['Save Guru/Staff', 'Save Teacher/Staff'],
            ['Tidak ada data user.', 'No user data found.'],
            ['Tidak ada data yang cocok dengan pencarian.', 'No data matches the search.'],
            ['Daftar Catatan Bimbingan Siswa', 'Student Guidance Notes List'],
            ['Daftar Student Guidance Notes', 'Student Guidance Notes List'],
            ['Nama', 'Name'],
            ['Kelas', 'Class'],
            ['Catatan Siswa', 'Student Notes'],
            ['Catatan Pembimbing', 'Instructor Notes'],
            ['Belum ada siswa yang membuat catatan.', 'No student notes yet.'],
            ['No data yet siswa yang membuat catatan.', 'No student notes yet.'],
            ['Setujui', 'Approve'],
            ['Tolak', 'Reject'],
            ['Terbaru Disetujui', 'Latest Approved'],
            ['Terbaru Ditolak', 'Latest Rejected'],
            ['Terbaru Approved', 'Latest Approved'],
            ['Terbaru Rejected', 'Latest Rejected'],
            ['Disetujui', 'Approved'],
            ['Ditolak', 'Rejected'],
            ['Pulihkan', 'Restore'],
            ['Hapus Permanen', 'Delete Permanent'],
            ['Cetak', 'Print'],
            ['Ekspor Excel', 'Export Excel'],
            ['Ekspor PDF', 'Export PDF'],
            ['Kesalahan', 'Error'],
            ['Selesaikan', 'Resolve'],
            ['selesai', 'resolved'],
            ['terbuka', 'open'],
            ['Rencana:', 'Plan:'],
            ['Realisasi:', 'Actual:'],
            ['Ringkasan Data', 'Data Summary'],
            ['Type: SAKIT', 'Type: SICK'],
            ['Personality & Work Ethic Assessment', 'Penilaian Kepribadian & Etos Kerja'],
            ['Indikator', 'Indicator'],
            ['Poor', 'Kurang'],
            ['Keramahan', 'Friendliness'],
            ['Senyum', 'Smile'],
            ['Penampilan', 'Appearance'],
            ['Komunikasi', 'Communication'],
            ['Realisasi Kerja', 'Work Realization'],
            ['Navigasi Pagination', 'Pagination Navigation'],
            ['Lihat saja', 'View only'],
            ['User berhasil dibuat.', 'User created successfully.'],
            ['User berhasil diperbarui.', 'User updated successfully.'],
            ['Password berhasil direset ke 12345678 dan user wajib ganti password saat login berikutnya.', 'Password has been reset to 12345678 and the user must change it on next login.'],
            ['User tidak ditemukan.', 'User not found.'],
            ['User sudah berada di tab Deleted.', 'User is already in Deleted tab.'],
            ['User berhasil di delete.', 'User soft deleted.'],
            ['Gagal menghapus user. Silakan coba lagi.', 'Failed to delete user. Please try again.'],
            ['User berhasil direstore.', 'User restored successfully.'],
            ['User harus ada di tab Deleted untuk dihapus permanen.', 'User must be in Deleted tab to be permanently deleted.'],
            ['User berhasil dihapus permanen.', 'User permanently deleted successfully.'],
            ['Gagal hapus permanen user. Silakan coba lagi.', 'Failed to permanently delete user. Please try again.'],
            ['Aksi massal selesai:', 'Bulk action completed:'],
            ['Delete massal lokasi selesai:', 'Bulk location delete completed:'],
            ['berhasil,', 'succeeded,'],
            ['dilewati.', 'skipped.'],
            ['NIS wajib diisi untuk role siswa.', 'NIS is required for student role.'],
            ['NUPTK wajib diisi untuk role selain siswa.', 'NUPTK is required for non-student role.'],
            ['NIS/NUPTK ini sudah digunakan.', 'This NIS/NUPTK is already used.'],
            ['Jurusan tidak terdaftar di Master Akademik.', 'Department is not registered in Academic Master.'],
            ['Kelas tidak terdaftar di Master Akademik.', 'Class is not registered in Academic Master.'],
            ['Kelas tidak sesuai dengan jurusan yang dipilih.', 'Class does not match the selected department.'],
            ['Foto profil bisa diambil dari kamera langsung atau upload file, lalu crop sesuai kebutuhan.', 'Profile photo can be captured directly from camera or uploaded, then cropped as needed.'],
            ['Preview Foto Profil', 'Profile Photo Preview'],
            ['Foto Profil', 'Profile Photo'],
            ['Pilih File Foto', 'Choose Photo File'],
            ['Reset password dilakukan melalui email akun Anda.', 'Password reset is done through your account email.'],
            ['Jika email diganti, verifikasi email akan diminta ulang.', 'If the email is changed, email verification will be required again.'],
            ['Kirim Link Reset Password Email', 'Send Password Reset Email Link'],
            ['Simpan Profil', 'Save Profile'],
            ['Ambil Dari Kamera', 'Capture From Camera'],
            ['Ambil Foto Dari Kamera', 'Capture Photo From Camera'],
            ['Ambil Foto', 'Capture Photo'],
            ['Crop Foto Profil', 'Crop Profile Photo'],
            ['Foto Ulang', 'Retake Photo'],
            ['Gunakan Foto', 'Use Photo'],
            ['Profil berhasil diperbarui. Email diganti, silakan verifikasi email baru dari inbox.', 'Profile updated. Email changed, please verify the new email from inbox.'],
            ['Profil berhasil diperbarui, tetapi email verifikasi gagal dikirim. Cek konfigurasi mail server.', 'Profile updated, but verification email failed to send. Check mail server configuration.'],
            ['Profil berhasil diperbarui.', 'Profile updated successfully.'],
            ['Absensi Harian', 'Daily Attendance'],
            ['Pengajuan', 'Requests'],
            ['Pengajuan Izin/Sakit', 'Leave/Sick Request'],
            ['Riwayat Catatan', 'Notes History'],
            ['Riwayat Catatan Pembimbing Sekolah', 'School Mentor Notes History'],
            ['Catatan Bimbingan', 'Guidance Notes'],
            ['Validasi Catatan Bimbingan', 'Guidance Notes Validation'],
            ['Validasi Kehadiran', 'Attendance Final Validation'],
            ['Wakil Kepsek', 'Vice Principal'],
            ['Absensi', 'Attendance'],
            ['Kategori', 'Category'],
            ['Jumlah Catatan', 'Notes Count'],
            ['Detail Catatan', 'Note Details'],
            ['Belum ada catatan validasi.', 'No validation notes yet.'],
            ['Catatan Validasi', 'Validation Notes'],
            ['Absensi / Laporan Harian', 'Attendance / Daily Report'],
            ['Validasi', 'Validation'],
            ['Validasi Absensi', 'Attendance Validation'],
            ['Validasi Pengajuan', 'Leave Validation'],
            ['Monitoring Progres', 'Progress Monitoring'],
            ['Chatbot Asisten', 'Assistant Chatbot'],
            ['Tanya cara pakai menu...', 'Ask how to use a menu...'],
            ['Kirim', 'Send'],
            ['Tutup', 'Close'],
            ['Monitoring Pengajuan', 'Request Monitoring'],
            ['Rekap Jurusan', 'Department Recap'],
            ['Validasi Mingguan', 'Weekly Validation'],
            ['Rekap Mingguan', 'Weekly Recap'],
            ['Analisis Mingguan', 'Weekly Analysis'],
            ['Catatan Mingguan', 'Weekly Notes'],
            ['Catatan Mingguan Instruktur', 'Instructor Weekly Notes'],
            ['Tambah Catatan', 'Add Note'],
            ['Tambah Catatan Mingguan Instruktur', 'Add Instructor Weekly Note'],
            ['Simpan Catatan', 'Save Note'],
            ['Simpan Catatan Instruktur', 'Save Instructor Note'],
            ['Ringkasan Instruktur', 'Instructor Summary'],
            ['Ringkasan Instruktur -', 'Instructor Summary -'],
            ['Ringkasan Kajur', 'Head of Department Summary'],
            ['Ringkasan Kajur -', 'Head of Department Summary -'],
            ['Total Siswa Jurusan', 'Total Department Students'],
            ['Total Siswa Aktif', 'Total Active Students'],
            ['Catatan Mingguan Minggu Ini', 'Weekly Notes This Week'],
            ['Pending Validasi Absensi', 'Pending Attendance Validation'],
            ['Belum ada pending validasi absensi.', 'No pending attendance validation data yet.'],
            ['Catatan Mingguan Terakhir', 'Latest Weekly Notes'],
            ['Belum ada catatan mingguan minggu ini.', 'No weekly notes for this week yet.'],
            ['Validasi Mingguan Menunggu', 'Weekly Validation Pending'],
            ['Belum ada riwayat validasi mingguan.', 'No weekly validation history yet.'],
            ['Siswa Alpha Hari Ini', 'Students Absent Today'],
            ['Tidak ada siswa alpha hari ini.', 'No absent students today.'],
            ['Catatan terakhir:', 'Latest note:'],
            ['Buka Analisis Mingguan', 'Open Weekly Analysis'],
            ['Summary Report', 'Summary Report'],
            ['Summary Report Mingguan', 'Weekly Summary Report'],
            ['Riwayat Validasi Mingguan', 'Weekly Validation History'],
            ['Validasi Mingguan Disetujui', 'Weekly Validation Approved'],
            ['Validasi Mingguan Revisi', 'Weekly Validation Revision'],
            ['Validator', 'Validator'],
            ['Minggu', 'Week'],
            ['Minggu Mulai', 'Week Start'],
            ['Semua Jurusan', 'All Departments'],
            ['Semua Kelas', 'All Classes'],
            ['Semua Siswa', 'All Students'],
            ['Status Validasi', 'Validation Status'],
            ['Catatan Pembimbing', 'Instructor Notes'],
            ['Catatan Kajur', 'Head of Department Notes'],
            ['Ketua Department', 'Head of Department'],
            ['Setujui Mingguan', 'Approve Weekly'],
            ['Kembalikan untuk Perbaikan', 'Return for Revision'],
            ['Revisi Mingguan', 'Weekly Revision'],
            ['Alasan revisi mingguan (wajib)', 'Weekly revision reason (required)'],
            ['Catatan validasi mingguan (opsional)', 'Weekly validation note (optional)'],
            ['Minta Perbaikan', 'Request Revision'],
            ['Kirim Revisi', 'Submit Revision'],
            ['Rekap Mingguan Dipindahkan', 'Weekly Recap Moved'],
            ['Buka Menu Rekap Mingguan', 'Open Weekly Recap Menu'],
            ['Kembali ke Validasi Mingguan', 'Back to Weekly Validation'],
            ['Rekap Kehadiran A/B/C/D/E', 'A/B/C/D/E Attendance Recap'],
            ['Sakit/Izin tanpa keterangan', 'Sick/Leave without explanation'],
            ['Meninggalkan tempat PKL tanpa izin', 'Leaving internship place without permission'],
            ['Sakit/Izin dengan keterangan', 'Sick/Leave with explanation'],
            ['Keterlambatan masuk kerja', 'Late check-in'],
            ['Rekap Absensi per Tanggal', 'Attendance Recap by Date'],
            ['Penilaian Kepribadian & Etos Kerja', 'Personality & Work Ethic Assessment'],
            ['Kurang', 'Poor'],
            ['Belum ada data.', 'No data yet.'],
            ['Simpan Review', 'Save Review'],
            ['Kembalikan ke Siswa', 'Return to Student'],
            ['Catatan review instruktur (opsional)', 'Instructor review note (optional)'],
            ['Catatan validasi pembimbing (opsional)', 'Supervisor validation note (optional)'],
            ['Catatan koreksi instruktur (wajib)', 'Instructor correction note (required)'],
            ['Validasi Sekolah', 'School Validation'],
            ['Validasi Kajur', 'Department Validation'],
            ['Pembimbing Sekolah', 'School Mentor'],
            ['pembimbing sekolah', 'school mentor'],
            ['Instruktur Sekolah', 'Internship Instructor'],
            ['instruktur sekolah', 'internship instructor'],
            ['Pembimbing', 'Mentor'],
            ['pembimbing', 'mentor'],
            ['Beranda', 'Home'],
            ['Master Data', 'Master Data'],
            ['Keamanan & Audit', 'Security & Audit'],
            ['Sistem', 'System'],
            ['Data & Konfigurasi', 'Data & Configuration'],
            ['Operasional', 'Operational'],
            ['Logout', 'Sign Out'],
            ['Kontak:', 'Contact:'],
            ['Selamat pagi', 'Good morning'],
            ['Selamat siang', 'Good afternoon'],
            ['Selamat sore', 'Good evening'],
            ['Selamat malam', 'Good night'],
            ['Buka Menu Validasi', 'Open Validation Menu'],
            ['Pending Pengajuan', 'Pending Requests'],
            ['Pending Laporan', 'Pending Reports'],
            ['Pending Absensi', 'Pending Attendance'],
            ['Belum ada data.', 'No data yet.'],
            ['Simpan', 'Save'],
            ['Batal', 'Cancel'],
            ['Hapus', 'Delete'],
            ['Cari', 'Search'],
            ['Filter', 'Filter'],
            ['Waktu', 'Time'],
            ['Koordinat', 'Coordinates'],
            ['Lihat Lokasi', 'View Location'],
            ['Tanggal', 'Date'],
            ['Jenis', 'Type'],
            ['Izin', 'Leave'],
            ['Sakit', 'Sick'],
            ['Status', 'Status'],
            ['Alasan', 'Reason'],
            ['Riwayat Pengajuan', 'Request History'],
            ['Form Pengajuan', 'Request Form'],
            ['Pengajuan Izin / Sakit', 'Leave / Sick Request'],
            ['Kembali ke Dashboard', 'Back to Dashboard'],
            ['Lupa Password', 'Forgot Password'],
            ['Masuk', 'Sign In'],
            ['Password', 'Password'],
            ['Bukti (wajib, foto)', 'Evidence (required, photo)'],
            ['Bukti wajib diambil langsung dari kamera perangkat.', 'Evidence must be captured directly from your device camera.'],
            ['Belum ada bukti foto.', 'No evidence photo yet.'],
            ['Ambil Bukti Foto (Kamera)', 'Capture Evidence (Camera)'],
            ['Ambil Ulang', 'Retake'],
            ['Kirim Pengajuan', 'Submit Request'],
            ['Belum ada pengajuan.', 'No requests yet.'],
            ['Kirim', 'Submit'],
            ['Belum ada', 'No data yet'],
            ['Terjadi kesalahan:', 'An error occurred:'],
            ['403 | Akses Ditolak', '403 | Access Denied'],
            ['Akses Ditolak', 'Access Denied'],
            ['Anda tidak memiliki izin untuk membuka halaman ini.', 'You do not have permission to open this page.'],
            ['404 | Halaman Tidak Ditemukan', '404 | Page Not Found'],
            ['Halaman Tidak Ditemukan', 'Page Not Found'],
            ['URL yang Anda akses tidak tersedia atau sudah dipindahkan.', 'The URL you accessed is unavailable or has been moved.'],
            ['419 | Sesi Berakhir', '419 | Session Expired'],
            ['Sesi Berakhir', 'Session Expired'],
            ['Token halaman sudah tidak valid. Silakan ulangi aksi dari dashboard.', 'The page token is no longer valid. Please retry the action from dashboard.'],
            ['500 | Terjadi Kesalahan', '500 | Error Occurred'],
            ['Terjadi Kesalahan Sistem', 'System Error Occurred'],
            ['Terjadi gangguan saat memproses permintaan Anda. Silakan coba lagi.', 'There was a disruption while processing your request. Please try again.'],
            ['Halaman tidak ditemukan atau terjadi kesalahan pada sistem.', 'Page not found or a system error occurred.'],
            ['Status Hari Ini', 'Today Status'],
            ['BELUM CHECK-IN', 'NOT CHECKED-IN'],
            ['Lokasi PKL Anda', 'Your Internship Location'],
            ['Lokasi Anda', 'Your Location'],
            ['Batas check-in:', 'Check-in window:'],
            ['Batas check-out:', 'Check-out window:'],
            ['Ringkasan Kegiatan Hari Ini', 'Today Activity Summary'],
            ['Rencana Pekerjaan', 'Planned Work'],
            ['Realisasi Pekerjaan', 'Actual Work'],
            ['Masalah di Lapangan (opsional)', 'Field Issues (optional)'],
            ['Bukti Foto (wajib)', 'Photo Evidence (required)'],
            ['Foto Selfie', 'Selfie Photo'],
            ['Daftar Absensi Menunggu Validasi', 'List of Attendances Awaiting Validation'],
            ['Belum ada data menunggu validasi.', 'No data awaiting validation.'],
            ['Daftar Pengajuan Menunggu Validasi', 'List of Requests Awaiting Validation'],
            ['Belum ada pengajuan menunggu validasi.', 'No requests awaiting validation.'],
            ['Cari siswa / NIS', 'Search student / NIS'],
            ['Ketik nama atau NIS', 'Type student name or NIS'],
            ['Reset Filter', 'Reset Filters'],
            ['Data tidak ditemukan untuk filter yang dipilih.', 'No data found for the selected filters.'],
            ['Catatan validasi', 'Validation note'],
            ['Pengajuan ini sudah diproses.', 'This request has already been processed.'],
            ['Lokasi:', 'Location:'],
            ['Check-in', 'Check-in'],
            ['Check-out', 'Check-out'],
            ['Kirim Check-in', 'Submit Check-in'],
            ['Kirim Check-out + Report', 'Submit Check-out + Report'],
            ['Check-out + Daily Report', 'Check-out + Daily Report'],
            ['Penugasan dari Atasan (opsional)', 'Supervisor Assignment (optional)'],
            ['Ambil Foto Check-in', 'Capture Check-in Photo'],
            ['Ambil Bukti Foto Check-out', 'Capture Check-out Evidence Photo'],
            ['Mengambil lokasi otomatis...', 'Fetching location automatically...'],
            ['Lokasi terdeteksi', 'Detected location'],
            ['Berhasil check-in', 'Check-in successful'],
            ['Berhasil check-out', 'Check-out successful'],
            ['Mengaktifkan kamera...', 'Enabling camera...'],
            ['Jepret', 'Capture'],
            ['Gunakan Foto Ini', 'Use This Photo'],
            ['Buka di Google Maps', 'Open in Google Maps'],
            ['Pilih reason code reject', 'Select reject reason code'],
            ['Koordinat:', 'Coordinate:'],
            ['Menu Check-in', 'Check-in Menu'],
            ['Menu Check-out', 'Check-out Menu'],
            ['Alasan reject (wajib)', 'Reject reason (required)'],
            ['Senyum baik', 'Good smile'],
            ['Keramahan baik', 'Good friendliness'],
            ['Penampilan baik', 'Good appearance'],
            ['Komunikasi baik', 'Good communication'],
            ['Realisasi kerja baik', 'Good work realization'],
            ['Tren Kehadiran & Distribusi Status', 'Attendance Trend & Status Distribution'],
            ['Tren Kehadiran Mingguan', 'Weekly Attendance Trend'],
            ['Distribusi Status', 'Status Distribution'],
            ['Analisis Siswa Bermasalah', 'Problematic Student Analysis'],
            ['Siswa dengan Alpha Terbanyak', 'Students with Highest Absences'],
            ['Perlu Tindak Lanjut', 'Needs Follow-up'],
            ['Monitor', 'Monitor'],
            ['Siswa Belum Validasi (Pending)', 'Students Not Yet Validated (Pending)'],
            ['Hari', 'Days'],
            ['Siswa Pelanggaran (C & E)', 'Violation Students (C & E)'],
            ['Pelanggaran', 'Violations'],
            ['Top 10 Siswa Terburuk (Risiko Tertinggi)', 'Top 10 Worst Students (Highest Risk)'],
            ['Skor Risiko', 'Risk Score'],
            ['Top Ranking & Rekap Lanjutan', 'Top Ranking & Advanced Recap'],
            ['Top 10 Siswa Terbaik', 'Top 10 Best Students'],
            ['Skor', 'Score'],
            ['Rekap per Jurusan', 'Recap by Department'],
            ['Ranking', 'Ranking'],
            ['Rekap per Tempat PKL (DU/DI)', 'Recap by Internship Place (DU/DI)'],
            ['Jumlah Siswa', 'Total Students'],
            ['Masalah', 'Issues'],
            ['Smart Alert (Deteksi Otomatis)', 'Smart Alert (Automatic Detection)'],
            ['Insight Mingguan', 'Weekly Insights'],
            ['Masalah Siswa', 'Student Issues'],
            ['Ranking & Rekap', 'Ranking & Recap'],
            ['Daftar Siswa (Semua Siswa)', 'Student List (All Students)'],
            ['Daftar Siswa Terpilih', 'Selected Student List'],
            ['Menunggu', 'Pending'],
            ['Prev', 'Prev'],
            ['Next', 'Next'],
            ['Alpha lebih dari 3 hari', 'Absence more than 3 days'],
            ['Tidak isi report lebih dari 2 hari', 'No report filled for more than 2 days'],
            ['Pending lebih dari 2 hari', 'Pending for more than 2 days'],
            ['Sering keluar radius (>2)', 'Frequent outside radius (>2)'],
            ['Tidak ada alert.', 'No alerts.'],
            ['Tidak ada data tren.', 'No trend data yet.'],
            ['Tidak ada data alpha.', 'No absence data.'],
            ['Tidak ada data pending.', 'No pending data.'],
            ['Tidak ada data pelanggaran.', 'No violation data.'],
            ['Belum ada riwayat absensi.', 'No attendance history yet.'],
            ['Tidak ada data risiko.', 'No risk data.'],
            ['Belum ada data ranking.', 'No ranking data yet.'],
            ['Belum ada data per jurusan.', 'No department data yet.'],
            ['Belum ada data per tempat PKL.', 'No internship place data yet.'],
            ['User', 'User'],
            ['Path/URL', 'Path/URL'],
            ['IP', 'IP'],
            ['Maps', 'Maps'],
            ['Konsistensi Alamat', 'Address Consistency'],
            ['Alamat Sama', 'Same Address'],
            ['Alamat Berbeda', 'Different Address'],
            ['Belum Bisa Dibandingkan', 'Cannot Be Compared Yet'],
            ['Alamat Tidak Tersedia', 'Address Not Available'],
            ['Pembimbing Sekolah Semua Siswa Jurusan', 'School Mentor for All Department Students'],
            ['Terapkan ke Semua Siswa Jurusan', 'Apply to All Department Students'],
            ['Pembimbing sekolah untuk siswa berhasil diperbarui.', 'School mentor assignment for students updated successfully.'],
            ['Assignment pembimbing sekolah untuk seluruh jurusan berhasil', 'School mentor assignment for entire department succeeded'],
            ['Drag & drop logo di sini', 'Drag & drop logo here'],
            ['Drag & drop favicon di sini', 'Drag & drop favicon here'],
            ['atau klik untuk pilih file', 'or click to choose file'],
            ['Dark Mode', 'Dark Mode'],
            ['Light Mode', 'Light Mode']
        ];

        function updateLanguageSwitcherLabels(lang) {
            if (!languageIdLabel || !languageEnLabel) return;
            languageIdLabel.textContent = 'IND';
            languageEnLabel.textContent = 'ENG';
        }

        function escapeRegex(input) {
            return input.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        function buildPhraseRegex(phrase) {
            const escaped = escapeRegex(phrase);
            // Avoid replacing short words inside larger tokens (e.g. "Back" in "Backup").
            if (/^[A-Za-z0-9_]+$/.test(phrase)) {
                return new RegExp(`\\b${escaped}\\b`, 'g');
            }
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

            document.querySelectorAll('input[type="button"], input[type="submit"]').forEach((el) => {
                if (!el.dataset.i18nValueSource) {
                    el.dataset.i18nValueSource = el.value || '';
                }
                if (el.dataset.i18nValueSource) {
                    el.value = replacePhrases(el.dataset.i18nValueSource, lang);
                }
            });

            document.querySelectorAll('[title]').forEach((el) => {
                if (!el.dataset.i18nTitleSource) {
                    el.dataset.i18nTitleSource = el.getAttribute('title') || '';
                }
                el.setAttribute('title', replacePhrases(el.dataset.i18nTitleSource, lang));
            });

            document.querySelectorAll('[aria-label]').forEach((el) => {
                if (!el.dataset.i18nAriaLabelSource) {
                    el.dataset.i18nAriaLabelSource = el.getAttribute('aria-label') || '';
                }
                el.setAttribute('aria-label', replacePhrases(el.dataset.i18nAriaLabelSource, lang));
            });

            document.querySelectorAll('option').forEach((opt) => {
                if (!opt.dataset.i18nTextSource) {
                    opt.dataset.i18nTextSource = opt.textContent || '';
                }
                opt.textContent = replacePhrases(opt.dataset.i18nTextSource, lang);
            });

            document.title = replacePhrases(originalTitle, lang);
        }

        function normalizeCommonTypos() {
            const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
            while (walker.nextNode()) {
                const node = walker.currentNode;
                if (!node || !node.nodeValue) continue;
            }
        }

        function setupChatbot() {
            if (!chatbotEnabled) return;

            const langKey = () => localStorage.getItem(STORAGE_LANG_KEY) || 'id';
            const textByLang = (id, en) => (langKey() === 'en' ? en : id);
            const panel = document.getElementById('chatbot-panel');
            const toggle = document.getElementById('chatbot-toggle');
            const title = document.getElementById('chatbot-title');
            const messagesEl = document.getElementById('chatbot-messages');
            const input = document.getElementById('chatbot-input');
            const sendBtn = document.getElementById('chatbot-send');
            let sessionToken = '';
            let isSending = false;

            if (!panel || !toggle || !title || !messagesEl || !input || !sendBtn) {
                return;
            }

            const scrollBottom = () => {
                messagesEl.scrollTop = messagesEl.scrollHeight;
            };

            const appendBubble = (isBot, message) => {
                const bubble = document.createElement('div');
                bubble.className = `chatbot-bubble ${isBot ? 'bot' : 'user'}`;
                bubble.textContent = message;
                messagesEl.appendChild(bubble);
                scrollBottom();
            };

            const removeQuickOptions = () => {
                messagesEl.querySelectorAll('.chatbot-quick-options').forEach((node) => node.remove());
            };

            const appendQuickOptions = () => {
                removeQuickOptions();

                const isEn = langKey() === 'en';
                const pagePath = (window.location.pathname || '/').replace(/^\/+/, '');
                const role = String(chatbotUserRole || '').toLowerCase();

                const optionsByRole = {
                    siswa: isEn
                        ? [
                            'How to check-in?',
                            'How to check-out + report?',
                            'How to submit leave/sick request?',
                            'What does pending status mean?',
                        ]
                        : [
                            'Cara check-in?',
                            'Cara check-out + laporan?',
                            'Cara pengajuan izin/sakit?',
                            'Status pending artinya apa?',
                        ],
                    pembimbing_pkl: isEn
                        ? [
                            'How to validate pending check-in/check-out?',
                            'How to validate leave/sick requests?',
                            'How to open attendance details?',
                            'What does pending status mean for supervisor?',
                        ]
                        : [
                            'Cara validasi pending check-in/check-out?',
                            'Cara validasi pengajuan izin/sakit?',
                            'Cara buka detail absensi?',
                            'Status pending untuk pembimbing artinya apa?',
                        ],
                    instruktur: isEn
                        ? [
                            'How to add weekly mentor notes?',
                            'How to monitor student progress?',
                            'Where to see Weekly Recap?',
                            'What does pending mean in weekly notes?',
                        ]
                        : [
                            'Cara tambah catatan mingguan instruktur?',
                            'Cara monitoring progres siswa?',
                            'Lihat Rekap Mingguan di mana?',
                            'Status pending di catatan mingguan artinya apa?',
                        ],
                    kajur: isEn
                        ? [
                            'How to add weekly department notes?',
                            'How to view mentor weekly notes?',
                            'How to assign student internship placement?',
                            'Where to view Weekly Analysis?',
                        ]
                        : [
                            'Cara tambah catatan mingguan kajur?',
                            'Cara lihat catatan mingguan instruktur?',
                            'Cara lihat rekap mingguan jurusan?',
                            'Lihat Analisis Mingguan di mana?',
                        ],
                    wali_kelas: isEn
                        ? [
                            'How to monitor daily class attendance?',
                            'How to read weekly class recap?',
                            'How to filter students by class?',
                            'What does pending status mean in class monitoring?',
                        ]
                        : [
                            'Cara monitoring harian kelas?',
                            'Cara baca rekap mingguan kelas?',
                            'Cara filter siswa per kelas?',
                            'Status pending di monitoring kelas artinya apa?',
                        ],
                    kesiswaan: isEn
                        ? [
                            'How to filter recap by department and class?',
                            'How to read attendance statistics?',
                            'How to export recap report?',
                            'Where can I view student analytics?',
                        ]
                        : [
                            'Cara filter rekap per jurusan dan kelas?',
                            'Cara membaca statistik kehadiran?',
                            'Cara export laporan rekap?',
                            'Lihat analisis siswa di mana?',
                        ],
                    kepsek: isEn
                        ? [
                            'How to read executive dashboard summary?',
                            'Where to view global recap report?',
                            'How to open weekly analysis?',
                            'How to export formal report?',
                        ]
                        : [
                            'Cara membaca ringkasan dashboard eksekutif?',
                            'Lihat rekap global di mana?',
                            'Cara buka analisis mingguan?',
                            'Cara export laporan formal?',
                        ],
                    admin_sekolah: isEn
                        ? [
                            'How to manage users?',
                            'How to set internship locations?',
                            'How to configure website settings?',
                            'How to import/export users?',
                        ]
                        : [
                            'Cara kelola manajemen pengguna?',
                            'Cara atur lokasi PKL?',
                            'Cara konfigurasi setting website?',
                            'Cara import/export user?',
                        ],
                    superadmin: isEn
                        ? [
                            'How to manage menu permissions?',
                            'How to configure website settings?',
                            'How to monitor audit log?',
                            'How to configure user access by role?',
                        ]
                        : [
                            'Cara mengatur hak akses menu?',
                            'Cara konfigurasi setting website?',
                            'Cara monitoring log activity?',
                            'Cara atur akses user per role?',
                        ],
                };

                let options = optionsByRole[role] || (isEn
                    ? [
                        'How to use this page?',
                        'What does this menu do?',
                        'Where is the related menu located?',
                        'What does pending status mean?',
                    ]
                    : [
                        'Cara pakai halaman ini?',
                        'Menu ini untuk apa?',
                        'Letak menu terkait di mana?',
                        'Status pending artinya apa?',
                    ]);

                // Contextual override by active page to keep quick options relevant.
                if (pagePath.startsWith('summary-report')) {
                    options = isEn
                        ? [
                            'How to add weekly notes from this page?',
                            'How to filter students by week?',
                            'How to read weekly recap data?',
                            'How to export this weekly report?',
                        ]
                        : [
                            'Cara tambah catatan mingguan dari halaman ini?',
                            'Cara filter siswa per minggu?',
                            'Cara membaca data rekap mingguan?',
                            'Cara export laporan mingguan ini?',
                        ];
                } else if (pagePath.startsWith('validasi')) {
                    options = isEn
                        ? [
                            'How to validate pending attendance?',
                            'How to open attendance details?',
                            'How to approve/reject correctly?',
                            'How to check validated history?',
                        ]
                        : [
                            'Cara validasi absensi pending?',
                            'Cara buka detail absensi?',
                            'Cara approve/reject yang benar?',
                            'Cara cek riwayat yang sudah divalidasi?',
                        ];
                } else if (pagePath.startsWith('fitur/manajemen-pengguna')) {
                    options = isEn
                        ? [
                            'How to add a new user?',
                            'How to edit user role and class?',
                            'How to search users across all data?',
                            'How to restore deleted users?',
                        ]
                        : [
                            'Cara tambah user baru?',
                            'Cara edit role dan kelas user?',
                            'Cara search user di semua data?',
                            'Cara restore user yang terhapus?',
                        ];
                }

                const wrap = document.createElement('div');
                wrap.className = 'chatbot-quick-options';

                options.forEach((label) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.textContent = label;
                    btn.addEventListener('click', () => {
                        input.value = label;
                        sendMessage();
                    });
                    wrap.appendChild(btn);
                });

                messagesEl.appendChild(wrap);
                scrollBottom();
            };

            const appendWelcomeIfNeeded = () => {
                if (messagesEl.childElementCount > 0) return;
                appendBubble(
                    true,
                    textByLang(
                        'Halo, apa yang bisa saya bantu?',
                        'Hello, what can I help you with?'
                    )
                );
                appendQuickOptions();
            };

            const refreshLabel = () => {
                const isEn = langKey() === 'en';
                toggle.textContent = isEn ? 'Assistant Chatbot' : 'Chatbot Asisten';
                title.textContent = toggle.textContent;
                sendBtn.textContent = isEn ? 'Send' : 'Kirim';
                input.placeholder = isEn ? 'Ask how to use a menu...' : 'Tanya cara pakai menu...';
            };

            const loadHistory = async () => {
                const query = new URLSearchParams();
                if (sessionToken) {
                    query.set('session_token', sessionToken);
                }
                query.set('lang', langKey());

                const response = await fetch(`${chatbotHistoryUrl}?${query.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('history_request_failed');
                }

                const payload = await response.json();
                sessionToken = payload.session_token || sessionToken;
                messagesEl.innerHTML = '';
                const items = Array.isArray(payload.items) ? payload.items : [];
                items.forEach((item) => appendBubble(Boolean(item.is_bot), String(item.message || '')));
                if (items.length === 0) {
                    appendWelcomeIfNeeded();
                }
            };

            const sendMessage = async () => {
                if (isSending) return;

                const message = (input.value || '').trim();
                if (!message) return;
                removeQuickOptions();

                isSending = true;
                appendBubble(false, message);
                input.value = '';
                appendBubble(true, textByLang('Mengetik...', 'Typing...'));
                const typingNode = messagesEl.lastElementChild;

                try {
                    const response = await fetch(chatbotMessageUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            message,
                            lang: langKey(),
                            session_token: sessionToken || null,
                        }),
                    });

                    if (!response.ok) {
                        throw new Error('message_request_failed');
                    }

                    const payload = await response.json();
                    sessionToken = payload.session_token || sessionToken;
                    if (typingNode && typingNode.parentNode === messagesEl) {
                        messagesEl.removeChild(typingNode);
                    }
                    appendBubble(true, String(payload.reply || ''));
                } catch (_error) {
                    if (typingNode && typingNode.parentNode === messagesEl) {
                        messagesEl.removeChild(typingNode);
                    }
                    appendBubble(true, textByLang(
                        'Gagal menghubungi chatbot. Coba lagi sebentar.',
                        'Failed to reach chatbot. Please try again shortly.'
                    ));
                } finally {
                    isSending = false;
                    input.focus();
                }
            };

            toggle.addEventListener('click', async () => {
                const willOpen = !panel.classList.contains('is-open');
                panel.classList.toggle('is-open');
                if (!willOpen) return;
                refreshLabel();
                if (!messagesEl.childElementCount) {
                    try {
                        await loadHistory();
                    } catch (_error) {
                        appendBubble(true, textByLang(
                            'Riwayat tidak dapat dimuat sekarang.',
                            'History cannot be loaded right now.'
                        ));
                    }
                }
                appendWelcomeIfNeeded();
                input.focus();
            });

            document.addEventListener('mousedown', (event) => {
                if (!panel.classList.contains('is-open')) return;
                const target = event.target;
                if (panel.contains(target) || toggle.contains(target)) return;
                panel.classList.remove('is-open');
            });

            sendBtn.addEventListener('click', sendMessage);
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    sendMessage();
                }
            });

            window.addEventListener('ui-language-changed', () => {
                refreshLabel();
                if (!panel.classList.contains('is-open')) return;
                if (!messagesEl.querySelector('.chatbot-quick-options')) return;
                appendQuickOptions();
            });
            refreshLabel();
        }

        function applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            const lang = localStorage.getItem(STORAGE_LANG_KEY) || 'id';
            if (themeSwitch) {
                themeSwitch.checked = theme === 'dark';
            }
            if (sidebarThemeLabel) {
                sidebarThemeLabel.textContent = lang === 'en' ? 'Dark Mode' : 'Mode Gelap';
            }
        }

        function applyLanguage(lang) {
            localStorage.setItem(STORAGE_LANG_KEY, lang);
            updateLanguageSwitcherLabels(lang);
            languageSwitches.forEach((radio) => {
                radio.checked = radio.value === lang;
            });
            translateDom(lang);
            normalizeCommonTypos();
            window.dispatchEvent(new CustomEvent('ui-language-changed', { detail: { lang } }));
            const theme = localStorage.getItem(STORAGE_THEME_KEY) || 'light';
            applyTheme(theme);
        }

        const savedTheme = localStorage.getItem(STORAGE_THEME_KEY) || 'light';
        const savedLang = localStorage.getItem(STORAGE_LANG_KEY) || 'id';

        function setStoredCoordinate(latitude, longitude) {
            const lat = Number(latitude);
            const lng = Number(longitude);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
            localStorage.setItem(STORAGE_GEO_LAT_KEY, lat.toFixed(7));
            localStorage.setItem(STORAGE_GEO_LNG_KEY, lng.toFixed(7));
        }

        function requestCoordinate(onSuccess, onError) {
            if (!navigator.geolocation) {
                onError?.();
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    setStoredCoordinate(position.coords.latitude, position.coords.longitude);
                    onSuccess?.();
                },
                () => onError?.(),
                { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
            );
        }

        function resolveEffectiveMethod(form) {
            const method = (form.getAttribute('method') || 'GET').toUpperCase();
            if (method !== 'POST') return method;

            const methodOverride = form.querySelector('input[name="_method"]');
            return (methodOverride?.value || method).toUpperCase();
        }

        function ensureCoordinateField(form, name, value) {
            let field = form.querySelector(`input[name="${name}"]`);
            if (!field) {
                field = document.createElement('input');
                field.type = 'hidden';
                field.name = name;
                form.appendChild(field);
            }
            field.value = value;
        }

        function injectStoredCoordinate(form) {
            const latitude = localStorage.getItem(STORAGE_GEO_LAT_KEY);
            const longitude = localStorage.getItem(STORAGE_GEO_LNG_KEY);
            if (!latitude || !longitude) return false;

            ensureCoordinateField(form, 'latitude', latitude);
            ensureCoordinateField(form, 'longitude', longitude);
            return true;
        }

        function bindCoordinateToForms() {
            const locationUnavailableMessage = (localStorage.getItem(STORAGE_LANG_KEY) || 'id') === 'en'
                ? 'Location is not available yet. Enable location to continue this action.'
                : 'Lokasi belum tersedia. Aktifkan lokasi untuk melanjutkan aksi.';
            document.querySelectorAll('form').forEach((form) => {
                const method = resolveEffectiveMethod(form);
                if (!['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) return;

                form.addEventListener('submit', (event) => {
                    if (injectStoredCoordinate(form)) return;

                    event.preventDefault();
                    requestCoordinate(
                        () => {
                            if (injectStoredCoordinate(form)) {
                                form.submit();
                                return;
                            }
                            showAppAlert(locationUnavailableMessage);
                        },
                        () => showAppAlert(locationUnavailableMessage)
                    );
                });
            });
        }

        function bindNumericIdentityFields() {
            const selectors = [
                'input[name="nis"]',
                'input[name="nuptk"]',
                'input[name="phone"]',
            ];
            const fields = document.querySelectorAll(selectors.join(','));
            fields.forEach((field) => {
                field.setAttribute('inputmode', 'numeric');
                field.setAttribute('pattern', '[0-9]*');
                field.setAttribute('autocomplete', 'off');

                const sanitize = () => {
                    const cleaned = (field.value || '').replace(/\D+/g, '');
                    if (field.value !== cleaned) {
                        field.value = cleaned;
                    }
                };

                field.addEventListener('input', sanitize);
                field.addEventListener('blur', sanitize);
                sanitize();
            });
        }

        requestCoordinate();
        bindCoordinateToForms();
        bindNumericIdentityFields();

        languageSwitches.forEach((radio) => {
            radio.checked = radio.value === savedLang;
            radio.addEventListener('change', function () {
                if (this.checked) applyLanguage(this.value);
            });
        });

        if (themeSwitch) {
            themeSwitch.addEventListener('change', function () {
                const next = this.checked ? 'dark' : 'light';
                localStorage.setItem(STORAGE_THEME_KEY, next);
                applyTheme(next);
            });
        }

        applyTheme(savedTheme);
        applyLanguage(savedLang);
        setupChatbot();

        const topbarEl = document.querySelector('.topbar');
        function syncTopbarShadow() {
            if (!topbarEl) return;
            topbarEl.classList.toggle('is-scrolled', window.scrollY > 2);
        }
        window.addEventListener('scroll', syncTopbarShadow, { passive: true });
        syncTopbarShadow();
    })();
</script>
</body>
</html>




