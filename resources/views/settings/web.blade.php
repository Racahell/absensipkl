@extends('layouts.app', ['title' => 'Setting Web'])

@section('content')
    @if (session('success'))
        <div class="card" style="border-color:#86efac; margin-bottom: 16px; background:#f0fdf4;">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="card" style="border-color:#fecaca; margin-bottom: 16px; background:#fff1f2;">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <h3 style="margin-top:0;">Pengaturan Website</h3>
        <form method="POST" action="{{ route('settings.web.update') }}" enctype="multipart/form-data">
            @csrf
            <label>Nama Website</label>
            <input type="text" name="site_name" value="{{ old('site_name', $setting->site_name) }}" style="width:100%; margin:6px 0 10px; padding:8px; border:1px solid #fdba74; border-radius:8px;" required>
            <label>Judul Website</label>
            <input type="text" name="site_title" value="{{ old('site_title', $setting->site_title) }}" style="width:100%; margin:6px 0 10px; padding:8px; border:1px solid #fdba74; border-radius:8px;" required>
            <label>Alamat</label>
            <input type="text" name="address" value="{{ old('address', $setting->address) }}" style="width:100%; margin:6px 0 10px; padding:8px; border:1px solid #fdba74; border-radius:8px;">
            <label>Nama Manager</label>
            <input type="text" name="manager_name" value="{{ old('manager_name', $setting->manager_name) }}" style="width:100%; margin:6px 0 10px; padding:8px; border:1px solid #fdba74; border-radius:8px;">
            <label>Kontak</label>
            <input type="text" name="contact" value="{{ old('contact', $setting->contact) }}" style="width:100%; margin:6px 0 10px; padding:8px; border:1px solid #fdba74; border-radius:8px;">
            <label>Logo</label>
            <input type="file" name="logo" accept="image/*" style="display:block; margin:6px 0 10px;">
            <label>Favicon</label>
            <input type="file" name="favicon" accept="image/*" style="display:block; margin:6px 0 12px;">
            <button type="submit" style="padding:9px 12px; border:1px solid #ea580c; background:#ea580c; color:#fff; border-radius:8px;">Simpan Setting</button>
        </form>
    </div>
@endsection
