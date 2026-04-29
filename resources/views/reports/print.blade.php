@extends('layouts.app', ['title' => 'Laporan Cetak'])

@section('content')
    <style>
        .print-wrap { background:#fff; border:1px solid #fdba74; border-radius:12px; padding:18px; }
        .print-header { margin-bottom:12px; }
        .print-header h2 { margin:0 0 4px; color:#9a3412; }
        .print-header p { margin:0; color:#7c2d12; }
        .print-table { width:100%; border-collapse:collapse; }
        .print-table th, .print-table td { border:1px solid #fdba74; padding:8px; font-size:13px; }
        .print-table th { background:#fff7ed; color:#7c2d12; }
        .print-tools { display:flex; gap:8px; margin-top:12px; }
        @media print {
            .topbar, .sidebar, .footer, .print-tools { display:none !important; }
            .shell { grid-template-columns: 1fr !important; }
            .content { padding: 0 !important; }
            .print-wrap { border:0; border-radius:0; padding:0; }
        }
    </style>

    <div class="print-wrap">
        <div class="print-header">
            <h2>Laporan Kehadiran</h2>
            <p>Generate: {{ $generatedAt }}</p>
            @if ($mode === 'pdf')
                <p>Gunakan menu print browser lalu pilih "Save as PDF".</p>
            @endif
        </div>

        <table class="print-table">
            <thead>
                <tr>
                    <th>Label</th>
                    <th>Hadir</th>
                    <th>Izin</th>
                    <th>Sakit</th>
                    <th>Alpha</th>
                    <th>Pending</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['hadir'] }}</td>
                        <td>{{ $row['izin'] }}</td>
                        <td>{{ $row['sakit'] }}</td>
                        <td>{{ $row['alpha'] }}</td>
                        <td>{{ $row['pending'] }}</td>
                        <td>{{ $row['total'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="print-tools">
            <button type="button" onclick="window.print()">Print</button>
            <a class="btn btn-ghost" href="{{ url()->previous() }}">Kembali</a>
        </div>
    </div>

    @if ($mode === 'pdf')
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
@endsection
