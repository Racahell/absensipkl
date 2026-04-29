@extends('layouts.app', ['title' => $title])

@section('content')
    @php
        $formatStatus = static function (?string $status): string {
            $raw = strtolower(trim((string) $status));
            return match (true) {
                $raw === '', $raw === '-' => '-',
                $raw === 'pending_pembimbing' => 'pending pembimbing sekolah',
                $raw === 'pending_instruktur' => 'approved pembimbing sekolah',
                $raw === 'pending_kajur' => 'approved instruktur',
                $raw === 'hadir',
                $raw === 'approved_final',
                str_starts_with($raw, 'approved'),
                str_starts_with($raw, 'reviewed_') => 'approved',
                default => str_replace('_', ' ', $raw),
            };
        };
    @endphp
    <style>
        .weekly-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            align-items: flex-end;
            margin-bottom: 14px;
            justify-content: space-between;
        }

        .weekly-toolbar button[type="submit"] {
            display: none !important;
        }

        .weekly-filter-group {
            display: grid;
            grid-template-columns: 170px 220px 160px 240px;
            gap: 10px;
            align-items: end;
            min-width: 0;
        }

        .weekly-filter-group .field {
            flex: 0 0 auto;
            min-width: 0;
        }

        .weekly-filter-group .field label {
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
            color: #9a3412;
        }

        .weekly-actions {
            display: flex;
            gap: 10px;
            flex-wrap: nowrap;
            align-items: flex-end;
        }

        .weekly-actions .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 120px;
            height: 40px;
            padding: 0 14px;
            border-radius: 10px;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .kpi-card {
            border: 1px solid #fdba74;
            border-radius: 10px;
            padding: 10px;
            background: #fff7ed;
        }

        .kpi-card small {
            color: #9a3412;
        }

        .kpi-card strong {
            display: block;
            font-size: 22px;
            color: #7c2d12;
            margin-top: 4px;
        }

        @media (max-width: 980px) {
            .weekly-filter-group {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                width: 100%;
            }
        }

        @media (max-width: 640px) {
            .weekly-filter-group {
                grid-template-columns: 1fr;
            }

            .weekly-actions {
                justify-content: stretch;
                width: 100%;
            }

            .weekly-actions .btn {
                width: 100%;
                min-width: 0;
            }
        }
    </style>

    @if (session('success'))
        <div class="card alert mb-16">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="card alert alert-error mb-16">
            <strong>Terjadi kesalahan:</strong>
            <ul class="mt-10">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-14">
        <h3 class="mt-0">Rekap Mingguan</h3>
        @if (in_array($role, ['kajur', 'wali_kelas'], true))
            <p class="text-primary" style="margin-top:-4px; margin-bottom:10px;">
                Jurusan: <strong>{{ $selectedDepartment !== '' ? $selectedDepartment : '-' }}</strong>
            </p>
            @if ($role === 'wali_kelas')
                <p class="text-primary" style="margin-top:-4px; margin-bottom:10px;">
                    Kelas: <strong>{{ $selectedClass !== '' ? $selectedClass : '-' }}</strong>
                </p>
            @endif
        @endif
        <form method="GET" action="{{ route('reports.weekly.recap') }}" class="weekly-toolbar">
            <div class="weekly-filter-group">
                <div class="field week-start">
                    <label for="week_start">Minggu Mulai</label>
                    <input id="week_start" type="date" name="week_start" value="{{ $weekStart->toDateString() }}">
                </div>
                @if (! in_array($role, ['kajur', 'wali_kelas'], true))
                    <div class="field department">
                        <label for="jurusan">Jurusan</label>
                        <select id="jurusan" name="jurusan">
                            <option value="">Semua Jurusan</option>
                            @foreach ($departmentOptions as $department)
                                <option value="{{ $department }}" {{ $selectedDepartment === $department ? 'selected' : '' }}>{{ $department }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="jurusan" value="{{ $selectedDepartment }}">
                @endif
                @if (! in_array($role, ['kajur', 'wali_kelas'], true))
                    <div class="field class">
                        <label for="kelas">Kelas</label>
                        <select id="kelas" name="kelas">
                            <option value="">Semua Kelas</option>
                            @foreach ($classOptions as $className)
                                <option value="{{ $className }}" {{ $selectedClass === $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="kelas" value="{{ $selectedClass }}">
                @endif
                <div class="field student">
                    <label for="siswa">Siswa</label>
                    <select id="siswa" name="siswa">
                        <option value="">Semua Siswa</option>
                        @foreach (($studentOptions ?? []) as $student)
                            <option value="{{ $student['id'] }}" {{ (string) ($selectedStudent ?? '') === (string) $student['id'] ? 'selected' : '' }}>
                                {{ $student['name'] }} ({{ $student['nis'] }}){{ ! empty($student['class_name']) ? ' - '.$student['class_name'] : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="weekly-actions">
                <a class="btn btn-ghost" href="{{ route('reports.export.excel', ['period' => 'weekly', 'week_start' => $weekStart->toDateString(), 'jurusan' => $selectedDepartment ?: null, 'kelas' => $selectedClass ?: null, 'siswa' => $selectedStudent ?: null]) }}">
                    Export Excel
                </a>
                <a class="btn btn-ghost" href="{{ route('reports.export.pdf', ['period' => 'weekly', 'week_start' => $weekStart->toDateString(), 'jurusan' => $selectedDepartment ?: null, 'kelas' => $selectedClass ?: null, 'siswa' => $selectedStudent ?: null]) }}">
                    Export PDF
                </a>
                <a class="btn btn-ghost" href="{{ route('reports.print', ['period' => 'weekly', 'week_start' => $weekStart->toDateString(), 'jurusan' => $selectedDepartment ?: null, 'kelas' => $selectedClass ?: null, 'siswa' => $selectedStudent ?: null]) }}" target="_blank">
                    Print
                </a>
                <a class="btn btn-ghost" href="{{ route('reports.weekly.recap') }}">Reset</a>
            </div>
        </form>

        <div class="kpi-grid">
            <div class="kpi-card"><small>Hadir</small><strong>{{ $summary['hadir'] }}</strong></div>
            <div class="kpi-card"><small>Izin</small><strong>{{ $summary['izin'] }}</strong></div>
            <div class="kpi-card"><small>Sakit</small><strong>{{ $summary['sakit'] }}</strong></div>
            <div class="kpi-card"><small>Alpha</small><strong>{{ $summary['alpha'] }}</strong></div>
            <div class="kpi-card"><small>Pending</small><strong>{{ $summary['pending'] }}</strong></div>
            <div class="kpi-card"><small>Total</small><strong>{{ $summary['total'] }}</strong></div>
        </div>
    </div>

    <div class="card mb-14">
        <h3 class="mt-0">Rekap Kehadiran A/B/C/D/E</h3>
        <div class="table-wrap">
            <table class="w-full">
                <thead><tr><th>Kode</th><th>Keterangan</th><th>Jumlah</th></tr></thead>
                <tbody>
                    <tr><td>A</td><td>Alpha</td><td>{{ $violation['A'] }}</td></tr>
                    <tr><td>B</td><td>Sakit/Izin tanpa keterangan</td><td>{{ $violation['B'] }}</td></tr>
                    <tr><td>C</td><td>Meninggalkan tempat PKL tanpa izin</td><td>{{ $violation['C'] }}</td></tr>
                    <tr><td>D</td><td>Sakit/Izin dengan keterangan</td><td>{{ $violation['D'] }}</td></tr>
                    <tr><td>E</td><td>Keterlambatan masuk kerja</td><td>{{ $violation['E'] }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-14">
        <h3 class="mt-0">Rekap Absensi per Tanggal</h3>
        <div class="table-wrap">
            <table class="w-full">
                <thead><tr><th>Tanggal</th><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Alpha</th><th>Pending</th></tr></thead>
                <tbody>
                    @forelse ($dailyRecap as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ $row['hadir'] }}</td>
                            <td>{{ $row['izin'] }}</td>
                            <td>{{ $row['sakit'] }}</td>
                            <td>{{ $row['alpha'] }}</td>
                            <td>{{ $row['pending'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-14">
        <h3 class="mt-0">Penilaian Kepribadian & Etos Kerja</h3>
        <div class="table-wrap">
            <table class="w-full">
                <thead><tr><th>Indikator</th><th>Baik</th><th>Kurang</th></tr></thead>
                <tbody>
                    <tr><td>Keramahan</td><td>{{ $ethos['keramahan_baik'] }}</td><td>{{ max($ethos['count'] - $ethos['keramahan_baik'], 0) }}</td></tr>
                    <tr><td>Senyum</td><td>{{ $ethos['senyum_baik'] }}</td><td>{{ max($ethos['count'] - $ethos['senyum_baik'], 0) }}</td></tr>
                    <tr><td>Penampilan</td><td>{{ $ethos['penampilan_baik'] }}</td><td>{{ max($ethos['count'] - $ethos['penampilan_baik'], 0) }}</td></tr>
                    <tr><td>Komunikasi</td><td>{{ $ethos['komunikasi_baik'] }}</td><td>{{ max($ethos['count'] - $ethos['komunikasi_baik'], 0) }}</td></tr>
                    <tr><td>Realisasi Kerja</td><td>{{ $ethos['realisasi_kerja_baik'] }}</td><td>{{ max($ethos['count'] - $ethos['realisasi_kerja_baik'], 0) }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-14" style="margin-top:24px;">
        <div class="flex items-center justify-between wrap gap-10 mb-10">
            <h3 class="mt-0" style="margin-bottom:0;">Riwayat Validasi Mingguan</h3>
            <form method="GET" action="{{ route('reports.weekly.recap') }}" class="flex items-center gap-8">
                <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
                <input type="hidden" name="jurusan" value="{{ $selectedDepartment }}">
                <input type="hidden" name="kelas" value="{{ $selectedClass }}">
                <input type="hidden" name="siswa" value="{{ $selectedStudent }}">
                <label for="history_per_page" style="margin:0;">Tampilkan</label>
                <select id="history_per_page" name="history_per_page">
                    @foreach (($historyPerPageOptions ?? [10, 20, 50, 100]) as $opt)
                        <option value="{{ $opt }}" {{ (int) ($historyPerPage ?? 10) === (int) $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="table-wrap">
            <table class="w-full">
                <thead>
                    <tr>
                        <th>Minggu</th>
                        <th>Jurusan</th>
                        <th>Kelas</th>
                        <th>Status</th>
                        <th>Validator</th>
                        <th>Waktu</th>
                        <th>Catatan Pembimbing</th>
                        <th>Catatan Kajur</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($validationHistory ?? collect()) as $row)
                        <tr>
                            <td>{{ optional($row->week_start)->format('Y-m-d') }} s/d {{ optional($row->week_end)->format('Y-m-d') }}</td>
                            <td>{{ $row->department_name ?: '-' }}</td>
                            <td>{{ $row->class_name ?: '-' }}</td>
                            <td>{{ $formatStatus($row->status) }}</td>
                            <td>{{ $row->approverKajur?->name ?? $row->validator?->name ?? '-' }}</td>
                            <td>{{ optional($row->validated_at)->format('Y-m-d H:i:s') ?? '-' }}</td>
                            <td>{{ $row->instruktur_note ?: '-' }}</td>
                            <td>{{ $row->kajur_note ?: ($row->note ?: '-') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="text-align:center;">Belum ada riwayat validasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (! empty($validationHistory) && method_exists($validationHistory, 'links'))
            <div class="mt-10">{{ $validationHistory->links() }}</div>
        @endif
    </div>

    <script>
        (function () {
            const form = document.querySelector('form.weekly-toolbar');
            if (!form) return;

            const weekStart = form.querySelector('[name="week_start"]');
            const jurusan = form.querySelector('select[name="jurusan"]');
            const kelas = form.querySelector('select[name="kelas"]');
            const siswa = form.querySelector('select[name="siswa"]');
            const historyPerPage = document.getElementById('history_per_page');

            let timer = null;
            function submitAuto(delay = 0) {
                if (timer) clearTimeout(timer);
                timer = window.setTimeout(() => form.submit(), delay);
            }

            if (weekStart) weekStart.addEventListener('change', () => submitAuto());
            if (jurusan) {
                jurusan.addEventListener('change', () => {
                    if (kelas) kelas.value = '';
                    if (siswa) siswa.value = '';
                    submitAuto();
                });
            }
            if (kelas) {
                kelas.addEventListener('change', () => {
                    if (siswa) siswa.value = '';
                    submitAuto();
                });
            }
            if (siswa) siswa.addEventListener('change', () => submitAuto());
            if (historyPerPage) historyPerPage.addEventListener('change', () => {
                const historyForm = historyPerPage.closest('form');
                if (historyForm) historyForm.submit();
            });
        })();
    </script>
@endsection



