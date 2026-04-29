<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kehadiran PKL</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 18mm 14mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Times New Roman", Times, serif;
            color: #111;
            font-size: 12pt;
        }

        .doc-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .doc-header .school {
            font-size: 13pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .2px;
        }

        .doc-header .title {
            margin-top: 4px;
            font-size: 16pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .meta td {
            padding: 2px 0;
            vertical-align: top;
        }

        .meta .label {
            width: 135px;
        }

        .meta .sep {
            width: 14px;
            text-align: center;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #000;
            padding: 6px 7px;
            font-size: 11pt;
        }

        .report-table thead th {
            text-align: center;
            font-weight: 700;
        }

        .report-table tbody td {
            text-align: center;
        }

        .report-table tbody td:first-child {
            text-align: left;
        }

        .signature-wrap {
            margin-top: 28px;
            display: flex;
            justify-content: flex-end;
        }

        .signature-box {
            width: 240px;
            text-align: center;
            font-size: 11pt;
        }

        .signature-space {
            height: 74px;
        }

        .auto-print-note {
            margin-top: 10px;
            font-size: 10pt;
            color: #444;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $appName = trim((string) (($appProfile['name'] ?? config('app.name')) ?: 'Absensi PKL'));
    @endphp

    <div class="doc-header">
        <div class="school">{{ $appName }}</div>
        <div class="title">Laporan Rekap Kehadiran PKL</div>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Tanggal Cetak</td>
            <td class="sep">:</td>
            <td>{{ $generatedAt }}</td>
        </tr>
    </table>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 22%;">Label</th>
                <th>Hadir</th>
                <th>Izin</th>
                <th>Sakit</th>
                <th>Alpha</th>
                <th>Pending</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td>{{ $row['hadir'] }}</td>
                    <td>{{ $row['izin'] }}</td>
                    <td>{{ $row['sakit'] }}</td>
                    <td>{{ $row['alpha'] }}</td>
                    <td>{{ $row['pending'] }}</td>
                    <td>{{ $row['total'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-wrap">
        <div class="signature-box">
            <div>{{ now()->translatedFormat('d F Y') }}</div>
            <div>Mengetahui,</div>
            <div class="signature-space"></div>
            <div><strong>(_______________________)</strong></div>
            <div>Penanggung Jawab</div>
        </div>
    </div>

    <div class="auto-print-note">
        Dokumen ini dihasilkan otomatis oleh sistem.
    </div>

    @if (($mode ?? 'pdf') === 'print')
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
</body>
</html>
