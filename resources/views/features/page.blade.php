@extends('layouts.app', ['title' => $title ?? 'Fitur'])

@section('content')
    <div class="card">
        <h3 style="margin-top: 0; color: #9a3412;">{{ $featureTitle }}</h3>
        <p style="margin-bottom: 12px; color: #7c2d12;">{{ $featureDescription }}</p>

        <div style="padding: 12px; border: 1px dashed #fdba74; border-radius: 10px; background: #fff7ed; color: #7c2d12;">
            Slug fitur: <strong>{{ $featureSlug }}</strong><br>
            Status: <strong>Struktur menu siap, implementasi detail modul bisa dilanjutkan per fitur.</strong>
        </div>
    </div>
@endsection
