@extends('layouts.app', ['title' => $title])

@section('content')
    <style>
        .weekly-toolbar {
            display: grid;
            grid-template-columns: repeat(6, minmax(140px, 1fr)) auto;
            gap: 10px;
            align-items: end;
            margin-bottom: 14px;
        }

        .weekly-toolbar button[type="submit"] {
            display: none !important;
        }

        .weekly-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .bars-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 12px;
        }

        .bar-stack {
            display: flex;
            gap: 8px;
            align-items: center;
            margin: 8px 0;
        }

        .bar-track {
            flex: 1;
            height: 10px;
            border-radius: 999px;
            overflow: hidden;
            background: #ffedd5;
        }

        .bar-fill {
            height: 100%;
            border-radius: 999px;
            background: #f97316;
        }

        .alert-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .alert-box {
            border: 1px solid #fdba74;
            border-radius: 10px;
            padding: 10px;
            background: #fff7ed;
        }

        .alert-box h4 {
            margin: 0 0 8px;
            font-size: 15px;
            color: #9a3412;
        }

        .muted {
            color: #9ca3af;
            font-size: 13px;
        }

        .analysis-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .analysis-tab {
            border: 1px solid #fdba74;
            background: #fff7ed;
            color: #9a3412;
            border-radius: 999px;
            padding: 7px 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .analysis-tab.active {
            background: #ea580c;
            border-color: #ea580c;
            color: #fff;
        }

        .analysis-pane {
            display: none;
        }

        .analysis-pane.active {
            display: block;
        }

        .analysis-card-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .analysis-table-box {
            border: 1px solid #fdba74;
            border-radius: 10px;
            padding: 10px;
            background: #fff;
        }

        .analysis-table-box h4 {
            margin: 0 0 8px;
            font-size: 15px;
            color: #9a3412;
        }

        .analysis-table-scroll {
            max-height: 280px;
            overflow: auto;
        }

        .analysis-table-scroll table th {
            position: sticky;
            top: 0;
            background: #fff7ed;
        }

        @media (max-width: 980px) {
            .weekly-toolbar {
                grid-template-columns: 1fr 1fr;
            }

            .analytics-grid,
            .bars-grid,
            .alert-grid,
            .analysis-card-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .weekly-toolbar {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="card mb-14">
        <h3 class="mt-0">Analisis Mingguan</h3>
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
        <form method="GET" action="{{ route('reports.weekly.analysis') }}" class="weekly-toolbar">
            <div>
                <label for="week_start">Minggu Mulai</label>
                <input id="week_start" type="date" name="week_start" value="{{ $weekStart->toDateString() }}">
            </div>
            @if (! in_array($role, ['kajur', 'wali_kelas'], true))
                <div>
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
                <div>
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
            <div>
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
            <div class="weekly-actions">
                <a class="btn btn-ghost" href="{{ route('reports.weekly.analysis') }}">Reset</a>
            </div>
        </form>
    </div>

    <div class="card mb-14">
        <h3 class="mt-0">Tren Kehadiran & Distribusi Status</h3>
        @php
            $maxDaily = max(1, collect($dailyRecap ?? [])->map(fn ($r) => (int) ($r['hadir'] + $r['izin'] + $r['sakit'] + $r['alpha'] + $r['pending']))->max() ?? 1);
            $distribution = [
                'Hadir' => (int) ($summary['hadir'] ?? 0),
                'Izin' => (int) ($summary['izin'] ?? 0),
                'Sakit' => (int) ($summary['sakit'] ?? 0),
                'Alpha' => (int) ($summary['alpha'] ?? 0),
                'Pending' => (int) ($summary['pending'] ?? 0),
            ];
            $maxDistribution = max(1, max($distribution));
        @endphp
        <div class="bars-grid">
            <div>
                <strong>Tren Kehadiran Mingguan</strong>
                @forelse ($dailyRecap as $row)
                    @php
                        $dayTotal = (int) $row['hadir'] + (int) $row['izin'] + (int) $row['sakit'] + (int) $row['alpha'] + (int) $row['pending'];
                        $width = (int) round(($dayTotal / $maxDaily) * 100);
                    @endphp
                    <div class="bar-stack">
                        <span style="min-width:95px;">{{ $row['date'] }}</span>
                        <div class="bar-track"><div class="bar-fill" style="width: {{ $width }}%;"></div></div>
                        <strong style="min-width:24px;text-align:right;">{{ $dayTotal }}</strong>
                    </div>
                @empty
                    <p class="muted">Tidak ada data tren.</p>
                @endforelse
            </div>
            <div>
                <strong>Distribusi Status</strong>
                @foreach ($distribution as $label => $value)
                    @php $width = (int) round(($value / $maxDistribution) * 100); @endphp
                    <div class="bar-stack">
                        <span style="min-width:74px;">{{ $label }}</span>
                        <div class="bar-track"><div class="bar-fill" style="width: {{ $width }}%;"></div></div>
                        <strong style="min-width:24px;text-align:right;">{{ $value }}</strong>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card mb-14">
        <h3 class="mt-0">Insight Mingguan</h3>

        <div class="analysis-tabs">
            <button type="button" class="analysis-tab active" data-tab="analisis-masalah">Masalah Siswa</button>
            <button type="button" class="analysis-tab" data-tab="analisis-ranking">Ranking & Rekap</button>
            <button type="button" class="analysis-tab" data-tab="analisis-alert">Smart Alert</button>
        </div>

        <div id="analisis-masalah" class="analysis-pane active">
            <div class="analysis-card-grid">
                <div class="analysis-table-box">
                    <h4>Siswa dengan Alpha Terbanyak</h4>
                    <div class="analysis-table-scroll">
                        <table class="w-full">
                            <thead><tr><th>Nama</th><th>Kelas</th><th>Alpha</th><th>Status</th></tr></thead>
                            <tbody>
                            @forelse (($analytics['alphaTop'] ?? []) as $row)
                                <tr><td>{{ $row['name'] }} ({{ $row['nis'] }})</td><td>{{ $row['class_name'] }}</td><td>{{ $row['alpha'] }}</td><td>{{ $row['alpha'] > 3 ? 'Perlu Tindak Lanjut' : 'Monitor' }}</td></tr>
                            @empty
                                <tr><td colspan="4" style="text-align:center;">Tidak ada data alpha.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="analysis-table-box">
                    <h4>Siswa Belum Validasi (Menunggu)</h4>
                    <div class="analysis-table-scroll">
                        <table class="w-full">
                            <thead><tr><th>Nama</th><th>Kelas</th><th>Menunggu</th><th>Hari</th></tr></thead>
                            <tbody>
                            @forelse (($analytics['pendingTop'] ?? []) as $row)
                                <tr><td>{{ $row['name'] }} ({{ $row['nis'] }})</td><td>{{ $row['class_name'] }}</td><td>{{ $row['pending'] }}</td><td>{{ $row['pending_days_count'] }}</td></tr>
                            @empty
                                <tr><td colspan="4" style="text-align:center;">Tidak ada data pending.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="analysis-table-box">
                    <h4>Siswa Pelanggaran (C & E)</h4>
                    <div class="analysis-table-scroll">
                        <table class="w-full">
                            <thead><tr><th>Nama</th><th>Kelas</th><th>Pelanggaran</th><th>Jumlah</th></tr></thead>
                            <tbody>
                            @forelse (($analytics['violationTop'] ?? []) as $row)
                                <tr><td>{{ $row['name'] }} ({{ $row['nis'] }})</td><td>{{ $row['class_name'] }}</td><td>C: {{ $row['violation_c'] }}, E: {{ $row['violation_e'] }}</td><td>{{ $row['violation_total'] }}</td></tr>
                            @empty
                                <tr><td colspan="4" style="text-align:center;">Tidak ada data pelanggaran.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="analysis-table-box">
                    <h4>Top 10 Siswa Terburuk (Risiko Tertinggi)</h4>
                    <div class="analysis-table-scroll">
                        <table class="w-full">
                            <thead><tr><th>Nama</th><th>Kelas</th><th>Alpha</th><th>Skor Risiko</th></tr></thead>
                            <tbody>
                            @forelse (($analytics['worstTop'] ?? []) as $row)
                                <tr><td>{{ $row['name'] }} ({{ $row['nis'] }})</td><td>{{ $row['class_name'] }}</td><td>{{ $row['alpha'] }}</td><td>{{ $row['risk_score'] }}</td></tr>
                            @empty
                                <tr><td colspan="4" style="text-align:center;">Tidak ada data risiko.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div id="analisis-ranking" class="analysis-pane">
            <div class="analysis-card-grid">
                <div class="analysis-table-box">
                    <h4>Top 10 Siswa Terbaik</h4>
                    <div class="analysis-table-scroll">
                        <table class="w-full">
                            <thead><tr><th>Nama</th><th>Kelas</th><th>Hadir</th><th>Skor</th></tr></thead>
                            <tbody>
                            @forelse (($analytics['bestTop'] ?? []) as $row)
                                <tr><td>{{ $row['name'] }} ({{ $row['nis'] }})</td><td>{{ $row['class_name'] }}</td><td>{{ $row['hadir'] }}</td><td>{{ $row['best_score'] }}</td></tr>
                            @empty
                                <tr><td colspan="4" style="text-align:center;">Belum ada data ranking.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="analysis-table-box">
                    <h4>Rekap per Jurusan</h4>
                    <div class="analysis-table-scroll">
                        <table class="w-full">
                            <thead><tr><th>Jurusan</th><th>Hadir</th><th>Alpha</th><th>Izin</th><th>Ranking</th></tr></thead>
                            <tbody>
                            @forelse (($analytics['departmentRecap'] ?? []) as $row)
                                <tr><td>{{ $row['department_name'] }}</td><td>{{ $row['hadir'] }}</td><td>{{ $row['alpha'] }}</td><td>{{ $row['izin'] }}</td><td>{{ $row['ranking_score'] }}</td></tr>
                            @empty
                                <tr><td colspan="5" style="text-align:center;">Belum ada data per jurusan.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="analysis-table-box" style="grid-column: 1 / -1;">
                    <h4>Rekap per Tempat PKL (DU/DI)</h4>
                    <div class="analysis-table-scroll">
                        <table class="w-full">
                            <thead><tr><th>Tempat PKL</th><th>Jumlah Siswa</th><th>Alpha</th><th>Masalah</th></tr></thead>
                            <tbody>
                            @forelse (($analytics['locationRecap'] ?? []) as $row)
                                <tr><td>{{ $row['location_name'] }}</td><td>{{ $row['student_count'] }}</td><td>{{ $row['alpha'] }}</td><td>{{ $row['issues'] }}</td></tr>
                            @empty
                                <tr><td colspan="4" style="text-align:center;">Belum ada data per tempat PKL.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div id="analisis-alert" class="analysis-pane">
            <div class="alert-grid">
                <div class="alert-box">
                    <h4>Alpha lebih dari 3 hari</h4>
                    @forelse (($analytics['alerts']['alpha_over_3'] ?? []) as $row)
                        <div>{{ $row['name'] }} ({{ $row['class_name'] }}) - {{ $row['alpha'] }} alpha</div>
                    @empty
                        <div class="muted">Tidak ada alert.</div>
                    @endforelse
                </div>
                <div class="alert-box">
                    <h4>Tidak isi report lebih dari 2 hari</h4>
                    @forelse (($analytics['alerts']['missing_report_over_2'] ?? []) as $row)
                        <div>{{ $row['name'] }} ({{ $row['class_name'] }}) - {{ $row['missing_report_days_count'] }} hari</div>
                    @empty
                        <div class="muted">Tidak ada alert.</div>
                    @endforelse
                </div>
                <div class="alert-box">
                    <h4>Menunggu lebih dari 2 hari</h4>
                    @forelse (($analytics['alerts']['pending_over_2_days'] ?? []) as $row)
                        <div>{{ $row['name'] }} ({{ $row['class_name'] }}) - {{ $row['pending_days_count'] }} hari</div>
                    @empty
                        <div class="muted">Tidak ada alert.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const tabs = document.querySelectorAll('.analysis-tab');
            const panes = document.querySelectorAll('.analysis-pane');
            if (!tabs.length) return;
            tabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    const targetId = tab.getAttribute('data-tab');
                    tabs.forEach((btn) => btn.classList.remove('active'));
                    panes.forEach((pane) => pane.classList.remove('active'));
                    tab.classList.add('active');
                    const pane = document.getElementById(targetId);
                    if (pane) pane.classList.add('active');
                });
            });
        })();

        (function () {
            const form = document.querySelector('form.weekly-toolbar');
            if (!form) return;

            const weekStart = form.querySelector('[name="week_start"]');
            const jurusan = form.querySelector('select[name="jurusan"]');
            const kelas = form.querySelector('select[name="kelas"]');
            const siswa = form.querySelector('select[name="siswa"]');

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
        })();
    </script>
@endsection

