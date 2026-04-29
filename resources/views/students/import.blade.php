@extends('layouts.app', ['title' => 'Import Siswa CSV'])

@section('content')
    @if (session('success'))
        <div class="card" style="border-color:#86efac; margin-bottom: 16px; background:#f0fdf4;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="card" style="border-color:#fecaca; margin-bottom: 16px; background:#fff1f2;">
            <strong>Terjadi kesalahan:</strong>
            <ul style="margin:8px 0 0 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="margin-bottom:16px;">
        <h3 style="margin-top:0;">Import Data Siswa via CSV</h3>
        <p style="margin:0 0 10px; color:#7c2d12;">
            Password tidak perlu diisi di file. Sistem otomatis set password default <strong>{{ $defaultPassword }}</strong>
            dan siswa wajib reset saat login pertama.
        </p>
        <p style="margin:0 0 12px; color:#6b7280;">
            Header wajib: <code>nis,name</code> atau <code>nis,nama</code>
        </p>

        <form action="{{ route('master.siswa.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label for="csv_file">File CSV</label>
            <input id="csv_file" type="file" name="csv_file" accept=".csv,text/csv,text/plain"
                style="display:block; margin:6px 0 12px;" required>

            <button type="submit"
                style="padding:9px 12px; border:1px solid #ea580c; background:#ea580c; color:#fff; border-radius:8px;">
                Import CSV
            </button>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Contoh Isi CSV</h3>
        <pre style="margin:0; white-space:pre-wrap;">nis,nama
230001,Andi Saputra
230002,Siti Aisyah</pre>
    </div>
@endsection
