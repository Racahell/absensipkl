@extends('layouts.app', ['title' => $title])

@section('content')
    <div class="card">
        <h3 style="margin-top:0; color:#9a3412;">Pengaturan Discord</h3>
        <p style="color:#7c2d12;">Notifikasi edit/hapus dikirim ke webhook ini. Pastikan channel Discord hanya untuk superadmin.</p>

        @if(session('success'))<div style="padding:10px; border:1px solid #86efac; background:#f0fdf4; color:#166534; border-radius:8px; margin-bottom:10px;">{{ session('success') }}</div>@endif

        <form method="POST" action="{{ route('discord.update') }}" style="display:grid; gap:8px; max-width:760px;">
            @csrf
            @method('PUT')
            <label>Discord Webhook URL</label>
            <input name="discord_webhook_url" value="{{ old('discord_webhook_url', $webhook) }}" placeholder="https://discord.com/api/webhooks/...">
            <button class="logout-btn" type="submit" style="width:max-content;">Simpan Webhook</button>
        </form>

        <form method="POST" action="{{ route('discord.test') }}" style="margin-top:10px;">
            @csrf
            <button type="submit">Tes Kirim Notifikasi</button>
        </form>
    </div>
@endsection
