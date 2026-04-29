@php
    $activeRole = $currentRole ?? auth()->user()->role;
@endphp

@extends('layouts.app', ['title' => $title ?? 'Dasbor'])

@section('content')
    <style>
        .dashboard-filter-actions {
            align-items: flex-end;
        }

        .wali-table-tools {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .wali-search {
            min-width: 260px;
            flex: 1;
        }

        .wali-pagination {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            margin-top: 10px;
        }

        .wali-page-btn {
            border: 1px solid #fdba74;
            background: #fff;
            color: #9a3412;
            border-radius: 8px;
            padding: 5px 10px;
            min-width: 36px;
            cursor: pointer;
        }

        .wali-page-btn.active {
            background: #ea580c;
            color: #fff;
            border-color: #ea580c;
        }

        .wali-page-btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }
    </style>

    @if ($activeRole === 'pembimbing_pkl' && ! empty($pembimbingSummary))
        <div style="display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:10px; margin-bottom:14px;">
            @foreach ($pembimbingSummary['cards'] as $card)
                <div class="card" style="padding:14px;">
                    <div style="font-size:12px; color:#9a3412;">{{ $card['label'] }}</div>
                    <div style="font-size:24px; font-weight:700; color:#7c2d12;">{{ $card['value'] }}</div>
                </div>
            @endforeach
        </div>
    @endif

    @if (! empty($kpiCards))
        <div style="display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:10px; margin-bottom:14px;">
            @foreach ($kpiCards as $card)
                <div class="card" style="padding:14px;">
                    <div style="font-size:12px; color:#9a3412;">{{ $card['label'] }}</div>
                    <div style="font-size:24px; font-weight:700; color:#7c2d12;">{{ $card['value'] }}</div>
                </div>
            @endforeach
        </div>
    @endif

    @if (! empty($showAnalytics))
        <div class="card" style="margin-bottom: 14px;">
            <h3 style="margin-top:0; color:#9a3412;">Diagram Kehadiran</h3>
            @if ($activeRole === 'instruktur')
                <small style="display:block; margin-bottom:8px; color:#6b7280;">
                    Data diagram khusus jurusan: <strong>{{ $selectedDepartment ?: '-' }}</strong>
                </small>
            @endif
            <form method="GET" action="{{ route('dashboard') }}" class="flex gap-10 wrap dashboard-filter-actions">
                @if ($activeRole === 'kesiswaan')
                    <div>
                        <label for="jurusan">Jurusan</label>
                        <select id="jurusan" name="jurusan" required>
                            <option value="">Pilih Jurusan</option>
                            @foreach (($departmentOptions ?? []) as $department)
                                <option value="{{ $department }}" {{ ($selectedDepartment ?? '') === $department ? 'selected' : '' }}>
                                    {{ $department }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="kelas">Kelas</label>
                        <select id="kelas" name="kelas" {{ empty($selectedDepartment) ? 'disabled' : '' }}>
                            <option value="">Semua Murid Jurusan</option>
                            @foreach (($classOptions ?? []) as $className)
                                <option value="{{ $className }}" {{ ($selectedClass ?? '') === $className ? 'selected' : '' }}>
                                    {{ $className }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label for="mode">Mode</label>
                    <select id="mode" name="mode">
                        <option value="daily" {{ ($analyticsMode ?? 'daily') === 'daily' ? 'selected' : '' }}>HARIAN</option>
                        <option value="monthly" {{ ($analyticsMode ?? '') === 'monthly' ? 'selected' : '' }}>BULANAN</option>
                    </select>
                </div>
                <input type="hidden" id="range" name="range" value="{{ ($analyticsMode ?? 'daily') === 'monthly' ? ($analyticsRange ?? 'this_month') : ($analyticsRange ?? 'today') }}">
                <div>
                    <label for="chart_type">Tipe Diagram</label>
                    <select id="chart_type" name="chart_type">
                        <option value="bar" {{ ($chartType ?? 'bar') === 'bar' ? 'selected' : '' }}>Batang</option>
                        <option value="line" {{ ($chartType ?? '') === 'line' ? 'selected' : '' }}>Garis</option>
                        <option value="pie" {{ ($chartType ?? '') === 'pie' ? 'selected' : '' }}>Pai</option>
                        <option value="doughnut" {{ ($chartType ?? '') === 'doughnut' ? 'selected' : '' }}>Donat</option>
                    </select>
                </div>
            </form>
            @if ($activeRole === 'kesiswaan' && empty($selectedDepartment))
                <div class="alert alert-error mt-10">Pilih jurusan terlebih dahulu untuk melihat data.</div>
            @elseif ($activeRole === 'instruktur' && empty($selectedDepartment))
                <div class="alert alert-error mt-10">Jurusan akun instruktur belum diatur. Isi department name agar diagram bisa tampil.</div>
            @endif
        </div>

        @if (!($activeRole === 'instruktur' && empty($selectedDepartment)))
            <div class="card" style="margin-bottom: 14px;">
                <canvas id="dashboardAttendanceChart" height="120"></canvas>
            </div>
        @endif
    @endif

    @if ($activeRole === 'siswa' || in_array($activeRole, ['pembimbing_pkl', 'instruktur', 'kajur'], true))
        <div class="card" style="margin-bottom:14px;">
            @if ($activeRole === 'siswa')
            <a href="{{ route('absensi.checkin.page') }}" style="display:inline-block; margin-top:10px; text-decoration:none; padding:9px 12px; border:1px solid #ea580c; background:#ea580c; color:#fff; border-radius:8px;">
                Menu Check-in
            </a>
            <a href="{{ route('absensi.checkout.page') }}" style="display:inline-block; margin-top:10px; margin-left:8px; text-decoration:none; padding:9px 12px; border:1px solid #f97316; background:#f97316; color:#fff; border-radius:8px;">
                Menu Check-out
            </a>
            <a href="{{ route('pengajuan.index') }}" style="display:inline-block; margin-top:10px; margin-left:8px; text-decoration:none; padding:9px 12px; border:1px solid #f97316; background:#f97316; color:#fff; border-radius:8px;">
                Pengajuan Izin/Sakit
            </a>
            @elseif ($activeRole === 'pembimbing_pkl')
            <a href="{{ route('validasi.index') }}" style="display:inline-block; margin-top:10px; text-decoration:none; padding:9px 12px; border:1px solid #ea580c; background:#ea580c; color:#fff; border-radius:8px;">
                Buka Menu Validasi
            </a>
            <a href="{{ route('validasi.pengajuan.index') }}" style="display:inline-block; margin-top:10px; margin-left:8px; text-decoration:none; padding:9px 12px; border:1px solid #f97316; background:#f97316; color:#fff; border-radius:8px;">
                Validasi Pengajuan
            </a>
            @elseif ($activeRole === 'instruktur')
            <a href="{{ route('reports.weekly') }}" style="display:inline-block; margin-top:10px; text-decoration:none; padding:9px 12px; border:1px solid #ea580c; background:#ea580c; color:#fff; border-radius:8px;">
                Catatan Mingguan
            </a>
            <a href="{{ route('reports.weekly.recap') }}" style="display:inline-block; margin-top:10px; margin-left:8px; text-decoration:none; padding:9px 12px; border:1px solid #f97316; background:#f97316; color:#fff; border-radius:8px;">
                Rekap Mingguan
            </a>
            @elseif ($activeRole === 'kajur')
            <a href="{{ route('reports.weekly') }}" style="display:inline-block; margin-top:10px; text-decoration:none; padding:9px 12px; border:1px solid #ea580c; background:#ea580c; color:#fff; border-radius:8px;">
                Rekap Jurusan
            </a>
            @endif
        </div>
    @endif

    @if ($activeRole === 'siswa' && ! empty($studentDashboard))
        <div class="card" style="margin-bottom:14px;">
            <h3 style="margin-top:0; color:#9a3412;">Ringkasan Siswa</h3>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:10px;">
                <div class="panel" style="padding:12px;">
                    <div style="font-size:12px; color:#9a3412;">Status Absensi Hari Ini</div>
                    <div style="font-size:20px; font-weight:700; color:#7c2d12;">{{ $studentDashboard['todayAttendanceStatus'] }}</div>
                </div>
                <div class="panel" style="padding:12px;">
                    <div style="font-size:12px; color:#9a3412;">Pengajuan Terakhir</div>
                    <div style="font-size:16px; font-weight:700; color:#7c2d12;">{{ $studentDashboard['latestLeaveStatus'] }}</div>
                    <small style="color:#6b7280;">{{ $studentDashboard['latestLeaveDate'] }}</small>
                </div>
            </div>
        </div>
    @endif

    @if ($activeRole === 'instruktur' && ! empty($instrukturSummary) && ! empty($instrukturSummary['departmentName']))
        <div class="card" style="margin-bottom:14px;">
            <h3 style="margin-top:0; color:#9a3412;">Ringkasan Instruktur - {{ $instrukturSummary['departmentName'] }}</h3>
            <div style="display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:10px; margin-bottom:12px;">
                @foreach (($instrukturSummary['cards'] ?? []) as $card)
                    <div class="panel" style="padding:12px;">
                        <div style="font-size:12px; color:#9a3412;">{{ $card['label'] }}</div>
                        <div style="font-size:22px; font-weight:700; color:#7c2d12;">{{ $card['value'] }}</div>
                    </div>
                @endforeach
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:10px;">
                <div class="card">
                    <h4 style="margin-top:0; color:#9a3412;">Pending Validasi Absensi</h4>
                    @forelse (($instrukturSummary['pendingAttendance'] ?? []) as $item)
                        <div style="padding:8px 0; border-bottom:1px solid #fed7aa;">
                            <div style="font-weight:600; color:#7c2d12;">{{ $item->user?->name ?? '-' }}</div>
                            @php
                                $statusRaw = strtolower(trim((string) ($item->status ?? '-')));
                                $statusLabel = match (true) {
                                    $statusRaw === '', $statusRaw === '-' => '-',
                                    $statusRaw === 'pending_pembimbing' => 'pending pembimbing sekolah',
                                    $statusRaw === 'pending_instruktur' => 'approved pembimbing sekolah',
                                    $statusRaw === 'pending_kajur' => 'approved instruktur',
                                    $statusRaw === 'hadir',
                                    $statusRaw === 'approved_final',
                                    str_starts_with($statusRaw, 'approved'),
                                    str_starts_with($statusRaw, 'reviewed_') => 'approved',
                                    default => str_replace('_', ' ', $statusRaw),
                                };
                            @endphp
                            <small style="color:#6b7280;">{{ optional($item->attendance_date)->format('d M Y') }} - {{ strtoupper($statusLabel) }}</small>
                        </div>
                    @empty
                        <small style="color:#6b7280;">Belum ada pending validasi absensi.</small>
                    @endforelse
                </div>

                <div class="card">
                    <h4 style="margin-top:0; color:#9a3412;">Catatan Mingguan Terakhir</h4>
                    @forelse (($instrukturSummary['weeklyNotes'] ?? []) as $item)
                        <div style="padding:8px 0; border-bottom:1px solid #fed7aa;">
                            <small style="color:#6b7280;">Minggu {{ optional($item->week_start)->format('d M') }} - {{ optional($item->week_end)->format('d M Y') }}</small>
                            <div style="margin-top:4px; color:#7c2d12;">{{ $item->instruktur_note ?: 'Belum ada catatan instruktur.' }}</div>
                        </div>
                    @empty
                        <small style="color:#6b7280;">Belum ada catatan mingguan minggu ini.</small>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @if ($activeRole === 'kajur' && ! empty($kajurSummary) && ! empty($kajurSummary['departmentName']))
        <div class="card" style="margin-bottom:14px;">
            <h3 style="margin-top:0; color:#9a3412;">Ringkasan Kajur - {{ $kajurSummary['departmentName'] }}</h3>
            <div style="display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:10px; margin-bottom:12px;">
                @foreach (($kajurSummary['cards'] ?? []) as $card)
                    <div class="panel" style="padding:12px;">
                        <div style="font-size:12px; color:#9a3412;">{{ $card['label'] }}</div>
                        <div style="font-size:22px; font-weight:700; color:#7c2d12;">{{ $card['value'] }}</div>
                    </div>
                @endforeach
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:10px;">
                <div class="card">
                    <h4 style="margin-top:0; color:#9a3412;">Riwayat Validasi Mingguan</h4>
                    @forelse (($kajurSummary['recentWeekly'] ?? []) as $item)
                        <div style="padding:8px 0; border-bottom:1px solid #fed7aa;">
                            <small style="color:#6b7280;">{{ optional($item->week_start)->format('d M Y') }} - {{ optional($item->week_end)->format('d M Y') }}</small>
                            @php
                                $statusRaw = strtolower(trim((string) ($item->status ?? '-')));
                                $statusLabel = match (true) {
                                    $statusRaw === '', $statusRaw === '-' => '-',
                                    $statusRaw === 'pending_pembimbing' => 'pending pembimbing sekolah',
                                    $statusRaw === 'pending_instruktur' => 'approved pembimbing sekolah',
                                    $statusRaw === 'pending_kajur' => 'approved instruktur',
                                    $statusRaw === 'hadir',
                                    $statusRaw === 'approved_final',
                                    str_starts_with($statusRaw, 'approved'),
                                    str_starts_with($statusRaw, 'reviewed_') => 'approved',
                                    default => str_replace('_', ' ', $statusRaw),
                                };
                            @endphp
                            <div style="margin-top:4px; color:#7c2d12;">Status: {{ strtoupper($statusLabel) }}</div>
                        </div>
                    @empty
                        <small style="color:#6b7280;">Belum ada riwayat validasi mingguan.</small>
                    @endforelse
                </div>

                <div class="card">
                    <h4 style="margin-top:0; color:#9a3412;">Siswa Alpha Hari Ini</h4>
                    @forelse (($kajurSummary['alphaTodayRows'] ?? []) as $item)
                        <div style="padding:8px 0; border-bottom:1px solid #fed7aa;">
                            <div style="font-weight:600; color:#7c2d12;">{{ $item->user?->name ?? '-' }} ({{ $item->user?->nis ?? '-' }})</div>
                            <small style="color:#6b7280;">{{ optional($item->attendance_date)->format('d M Y') }}</small>
                        </div>
                    @empty
                        <small style="color:#6b7280;">Tidak ada siswa alpha hari ini.</small>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @if ($activeRole === 'pembimbing_pkl' && ! empty($pembimbingSummary))
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:10px;">
            <div class="card">
                <h3 style="margin-top:0; color:#9a3412;">Pending Absensi</h3>
                @forelse ($pembimbingSummary['pendingAbsensi'] as $item)
                    <div style="padding:8px 0; border-bottom:1px solid #fed7aa;">
                        <div style="font-weight:600; color:#7c2d12;">{{ $item->user?->name ?? '-' }}</div>
                        <small style="color:#6b7280;">{{ optional($item->attendance_date)->format('d M Y') }}</small>
                    </div>
                @empty
                    <small style="color:#6b7280;">Tidak ada pending absensi.</small>
                @endforelse
            </div>

            <div class="card">
                <h3 style="margin-top:0; color:#9a3412;">Pending Pengajuan</h3>
                @forelse ($pembimbingSummary['pendingPengajuan'] as $item)
                    <div style="padding:8px 0; border-bottom:1px solid #fed7aa;">
                        <div style="font-weight:600; color:#7c2d12;">{{ $item->user?->name ?? '-' }}</div>
                        <small style="color:#6b7280;">{{ optional($item->request_date)->format('d M Y') }} - {{ strtoupper($item->type) }}</small>
                    </div>
                @empty
                    <small style="color:#6b7280;">Tidak ada pending pengajuan.</small>
                @endforelse
            </div>

            <div class="card">
                <h3 style="margin-top:0; color:#9a3412;">Pending Laporan</h3>
                @forelse ($pembimbingSummary['pendingLaporan'] as $item)
                    <div style="padding:8px 0; border-bottom:1px solid #fed7aa;">
                        <div style="font-weight:600; color:#7c2d12;">{{ $item->attendance?->user?->name ?? '-' }}</div>
                        <small style="color:#6b7280;">Laporan #{{ $item->id }}</small>
                    </div>
                @empty
                    <small style="color:#6b7280;">Tidak ada pending laporan.</small>
                @endforelse
            </div>
        </div>
    @endif

    @if ($activeRole === 'wali_kelas' && ! empty($waliSummary))
        <div class="card" style="margin-bottom:14px;">
            <h3 style="margin-top:0; color:#9a3412;">Data Kelas Wali</h3>
            @if (empty($waliSummary['className']))
                <small style="color:#6b7280;">Kelas wali belum diatur. Silakan isi `class_name` pada akun wali kelas.</small>
            @else
                <div style="display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:10px; margin-bottom:10px;">
                    @foreach (($waliSummary['cards'] ?? []) as $card)
                        <div class="panel" style="padding:12px;">
                            <div style="font-size:12px; color:#9a3412;">{{ $card['label'] }}</div>
                            <div style="font-size:22px; font-weight:700; color:#7c2d12;">{{ $card['value'] }}</div>
                        </div>
                    @endforeach
                </div>
                <h4 style="margin:0 0 10px 0; color:#9a3412;">Kelas Wali: {{ $waliSummary['className'] }}</h4>
                <div style="margin-bottom:10px; color:#7c2d12;">Total Siswa: <strong>{{ $waliSummary['students']->count() }}</strong></div>
                <div class="wali-table-tools">
                    <input
                        type="text"
                        id="wali-student-search"
                        class="wali-search"
                        placeholder="Cari siswa (nama/NIS/jurusan/status)...">
                    <div class="text-muted"><small id="wali-student-count"></small></div>
                </div>
                <div class="table-wrap">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>NIS</th>
                                <th>Jurusan</th>
                                <th>Status Hari Ini</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($waliSummary['students'] as $student)
                                @php
                                    $attendanceStatus = $waliSummary['attendanceMap'][$student->id] ?? null;
                                    $leaveStatus = $waliSummary['leaveMap'][$student->id]['status'] ?? null;
                                    $statusLabel = $leaveStatus
                                        ? strtoupper(str_replace('_', ' ', $leaveStatus))
                                        : ($attendanceStatus ? strtoupper(str_replace('_', ' ', $attendanceStatus)) : 'BELUM CHECK-IN');
                                @endphp
                                <tr class="wali-student-row" data-search="{{ strtolower(($student->name ?? '').' '.($student->nis ?? '').' '.($student->department_name ?? '').' '.$statusLabel) }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $student->name }}</td>
                                    <td>{{ $student->nis }}</td>
                                    <td>{{ $student->department_name ?? '-' }}</td>
                                    <td>{{ $statusLabel }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align:center; color:#7c2d12;">Belum ada siswa pada kelas ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div id="wali-student-pagination" class="wali-pagination" style="display:none;"></div>

                <h4 style="margin:14px 0 10px 0; color:#9a3412;">Monitoring Harian</h4>
                <div class="wali-table-tools">
                    <input
                        type="text"
                        id="wali-monitor-search"
                        class="wali-search"
                        placeholder="Cari monitoring (nama/NIS/check-in/check-out/report)...">
                    <div class="text-muted"><small id="wali-monitor-count"></small></div>
                </div>
                <div class="table-wrap">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>NIS</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Daily Report</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($waliSummary['monitoringToday'] ?? []) as $row)
                                <tr class="wali-monitor-row" data-search="{{ strtolower(($row['name'] ?? '').' '.($row['nis'] ?? '').' '.($row['has_checkin'] ? 'sudah' : 'belum').' '.($row['has_checkout'] ? 'sudah' : 'belum').' '.($row['has_daily_report'] ? 'sudah' : 'belum')) }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $row['name'] }}</td>
                                    <td>{{ $row['nis'] }}</td>
                                    <td>{{ $row['has_checkin'] ? 'Sudah' : 'Belum' }}</td>
                                    <td>{{ $row['has_checkout'] ? 'Sudah' : 'Belum' }}</td>
                                    <td>{{ $row['has_daily_report'] ? 'Sudah' : 'Belum' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align:center; color:#7c2d12;">Belum ada data monitoring harian.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div id="wali-monitor-pagination" class="wali-pagination" style="display:none;"></div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:10px; margin-top:14px;">
                    <div class="card">
                        <h4 style="margin-top:0; color:#9a3412;">Siswa Alpha Terbanyak</h4>
                        <div class="table-wrap">
                            <table class="w-full">
                                <thead><tr><th>Nama</th><th>Alpha</th></tr></thead>
                                <tbody>
                                    @forelse (($waliSummary['analytics']['alphaTop'] ?? []) as $row)
                                        <tr><td>{{ $row['name'] }} ({{ $row['nis'] }})</td><td>{{ $row['alpha'] }}</td></tr>
                                    @empty
                                        <tr><td colspan="2" style="text-align:center;">Tidak ada data alpha.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card">
                        <h4 style="margin-top:0; color:#9a3412;">Siswa Pending Validasi</h4>
                        <div class="table-wrap">
                            <table class="w-full">
                                <thead><tr><th>Nama</th><th>Hari Pending</th></tr></thead>
                                <tbody>
                                    @forelse (($waliSummary['analytics']['pendingTop'] ?? []) as $row)
                                        <tr><td>{{ $row['name'] }} ({{ $row['nis'] }})</td><td>{{ $row['pending_days_count'] }}</td></tr>
                                    @empty
                                        <tr><td colspan="2" style="text-align:center;">Tidak ada data pending.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card">
                        <h4 style="margin-top:0; color:#9a3412;">Top 10 Siswa Terbaik</h4>
                        <div class="table-wrap">
                            <table class="w-full">
                                <thead><tr><th>Nama</th><th>Skor</th></tr></thead>
                                <tbody>
                                    @forelse (($waliSummary['analytics']['bestTop'] ?? []) as $row)
                                        <tr><td>{{ $row['name'] }} ({{ $row['nis'] }})</td><td>{{ $row['best_score'] }}</td></tr>
                                    @empty
                                        <tr><td colspan="2" style="text-align:center;">Belum ada data ranking.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card">
                        <h4 style="margin-top:0; color:#9a3412;">Top 10 Siswa Risiko Tertinggi</h4>
                        <div class="table-wrap">
                            <table class="w-full">
                                <thead><tr><th>Nama</th><th>Skor Risiko</th></tr></thead>
                                <tbody>
                                    @forelse (($waliSummary['analytics']['worstTop'] ?? []) as $row)
                                        <tr><td>{{ $row['name'] }} ({{ $row['nis'] }})</td><td>{{ $row['risk_score'] }}</td></tr>
                                    @empty
                                        <tr><td colspan="2" style="text-align:center;">Tidak ada data risiko.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <h4 style="margin:14px 0 10px 0; color:#9a3412;">Smart Alert Kelas</h4>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:10px;">
                    <div class="card">
                        <strong>Alpha &gt; 2 hari</strong>
                        @forelse (($waliSummary['analytics']['alerts']['alpha_over_2'] ?? []) as $row)
                            <div style="margin-top:8px;">{{ $row['name'] }} - {{ $row['alpha'] }} hari</div>
                        @empty
                            <div style="margin-top:8px; color:#6b7280;">Tidak ada alert.</div>
                        @endforelse
                    </div>
                    <div class="card">
                        <strong>Pending &gt; 2 hari</strong>
                        @forelse (($waliSummary['analytics']['alerts']['pending_over_2'] ?? []) as $row)
                            <div style="margin-top:8px;">{{ $row['name'] }} - {{ $row['pending_days_count'] }} hari</div>
                        @empty
                            <div style="margin-top:8px; color:#6b7280;">Tidak ada alert.</div>
                        @endforelse
                    </div>
                    <div class="card">
                        <strong>Report kosong &gt; 2 hari</strong>
                        @forelse (($waliSummary['analytics']['alerts']['missing_report_over_2'] ?? []) as $row)
                            <div style="margin-top:8px;">{{ $row['name'] }} - {{ $row['missing_report_days_count'] }} hari</div>
                        @empty
                            <div style="margin-top:8px; color:#6b7280;">Tidak ada alert.</div>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if ($activeRole === 'wali_kelas' && ! empty($waliSummary) && ! empty($waliSummary['className']))
        <script>
            (function () {
                function setupTablePagination(config) {
                    const rows = Array.from(document.querySelectorAll(config.rowSelector));
                    const pagination = document.getElementById(config.paginationId);
                    const searchInput = document.getElementById(config.searchId);
                    const countLabel = document.getElementById(config.countId);
                    const perPage = 10;

                    if (!pagination || !searchInput) return;

                    let keyword = '';
                    let currentPage = 1;
                    let filteredRows = rows;

                    function updateCount() {
                        if (!countLabel) return;
                        countLabel.textContent = `Menampilkan ${filteredRows.length} data`;
                    }

                    function visibleRowsForPage() {
                        const start = (currentPage - 1) * perPage;
                        const end = start + perPage;
                        return filteredRows.slice(start, end);
                    }

                    function renderRows() {
                        rows.forEach((row) => {
                            row.style.display = 'none';
                        });
                        visibleRowsForPage().forEach((row) => {
                            row.style.display = '';
                        });
                    }

                    function createButton(label, page, isActive = false, disabled = false) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'wali-page-btn' + (isActive ? ' active' : '');
                        btn.textContent = label;
                        btn.disabled = disabled;
                        btn.addEventListener('click', () => {
                            currentPage = page;
                            renderRows();
                            renderPagination();
                        });
                        return btn;
                    }

                    function renderPagination() {
                        const totalPages = Math.max(1, Math.ceil(filteredRows.length / perPage));
                        if (currentPage > totalPages) currentPage = totalPages;
                        pagination.innerHTML = '';

                        if (filteredRows.length <= perPage) {
                            pagination.style.display = 'none';
                            return;
                        }

                        pagination.style.display = 'flex';
                        pagination.appendChild(createButton('Prev', Math.max(1, currentPage - 1), false, currentPage === 1));

                        for (let i = 1; i <= totalPages; i++) {
                            pagination.appendChild(createButton(String(i), i, i === currentPage));
                        }

                        pagination.appendChild(createButton('Next', Math.min(totalPages, currentPage + 1), false, currentPage === totalPages));
                    }

                    function applyFilter() {
                        keyword = (searchInput.value || '').toLowerCase().trim();
                        filteredRows = rows.filter((row) => {
                            const hay = (row.getAttribute('data-search') || '').toLowerCase();
                            return keyword === '' || hay.includes(keyword);
                        });
                        currentPage = 1;
                        updateCount();
                        renderRows();
                        renderPagination();
                    }

                    searchInput.addEventListener('input', applyFilter);
                    applyFilter();
                }

                setupTablePagination({
                    rowSelector: '.wali-student-row',
                    paginationId: 'wali-student-pagination',
                    searchId: 'wali-student-search',
                    countId: 'wali-student-count',
                });

                setupTablePagination({
                    rowSelector: '.wali-monitor-row',
                    paginationId: 'wali-monitor-pagination',
                    searchId: 'wali-monitor-search',
                    countId: 'wali-monitor-count',
                });
            })();
        </script>
    @endif

    @if (! empty($showAnalytics) || ($activeRole === 'pembimbing_pkl' && ! empty($pembimbingSummary)))
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const chartType = @json($chartType ?? 'bar');
            const labels = @json($labels ?? []);
            const hadirData = @json($hadirData ?? []);
            const izinData = @json($izinData ?? []);
            const sakitData = @json($sakitData ?? []);
            const alphaData = @json($alphaData ?? []);
            const pendingData = @json($pendingData ?? []);

            const pieLikeData = [
                hadirData.reduce((a, b) => a + b, 0),
                izinData.reduce((a, b) => a + b, 0),
                sakitData.reduce((a, b) => a + b, 0),
                alphaData.reduce((a, b) => a + b, 0),
                pendingData.reduce((a, b) => a + b, 0),
            ];

            function getChartLabels() {
                const uiLang = window.localStorage.getItem('ui_lang') || 'id';
                return uiLang === 'en'
                    ? { present: 'Present', leave: 'Leave', sick: 'Sick', absent: 'Absent', pending: 'Pending', total: 'Total' }
                    : { present: 'Hadir', leave: 'Izin', sick: 'Sakit', absent: 'Alpha', pending: 'Menunggu', total: 'Total' };
            }

            function localizePeriodLabels(rawLabels) {
                const uiLang = window.localStorage.getItem('ui_lang') || 'id';
                const mapToEn = {
                    'Hari Ini': 'Today',
                    'Kemarin': 'Yesterday',
                };
                const mapToId = {
                    'Today': 'Hari Ini',
                    'Yesterday': 'Kemarin',
                };

                return (rawLabels || []).map((label) => {
                    const text = String(label || '');
                    return uiLang === 'en'
                        ? (mapToEn[text] || text)
                        : (mapToId[text] || text);
                });
            }

            function buildDatasets(chartLabels) {
                return [
                    { label: chartLabels.present, data: hadirData, backgroundColor: '#16a34a', borderColor: '#16a34a' },
                    { label: chartLabels.leave, data: izinData, backgroundColor: '#0284c7', borderColor: '#0284c7' },
                    { label: chartLabels.sick, data: sakitData, backgroundColor: '#ca8a04', borderColor: '#ca8a04' },
                    { label: chartLabels.absent, data: alphaData, backgroundColor: '#dc2626', borderColor: '#dc2626' },
                    { label: chartLabels.pending, data: pendingData, backgroundColor: '#9333ea', borderColor: '#9333ea' },
                ];
            }

            const dashboardCanvas = document.getElementById('dashboardAttendanceChart');
            let dashboardChart = null;

            function renderDashboardChart() {
                if (!dashboardCanvas) return;
                const chartLabels = getChartLabels();
                const datasets = buildDatasets(chartLabels);
                const localizedPeriodLabels = localizePeriodLabels(labels);
                if (dashboardChart) {
                    dashboardChart.destroy();
                }
                dashboardChart = new Chart(dashboardCanvas, {
                    type: chartType,
                    data: {
                        labels: chartType === 'pie' || chartType === 'doughnut'
                            ? [chartLabels.present, chartLabels.leave, chartLabels.sick, chartLabels.absent, chartLabels.pending]
                            : localizedPeriodLabels,
                        datasets: chartType === 'pie' || chartType === 'doughnut'
                            ? [{
                                label: chartLabels.total,
                                data: pieLikeData,
                                backgroundColor: ['#16a34a','#0284c7','#ca8a04','#dc2626','#9333ea'],
                            }]
                            : datasets,
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: true } },
                    },
                });
            }

            if (dashboardCanvas) {
                renderDashboardChart();
                window.addEventListener('ui-language-changed', renderDashboardChart);
            }

            const modeSelect = document.getElementById('mode');
            const rangeSelect = document.getElementById('range');
            const chartTypeSelect = document.getElementById('chart_type');
            const jurusanSelect = document.getElementById('jurusan');
            const kelasSelect = document.getElementById('kelas');

            function applyDashboardFilters(forceResetRange = false) {
                if (!modeSelect || !rangeSelect || !chartTypeSelect) return;
                const params = new URLSearchParams(window.location.search);

                params.set('mode', modeSelect.value);
                params.set('chart_type', chartTypeSelect.value);

                if (jurusanSelect) {
                    const jurusanVal = jurusanSelect.value || '';
                    if (jurusanVal === '') {
                        params.delete('jurusan');
                    } else {
                        params.set('jurusan', jurusanVal);
                    }
                }

                if (kelasSelect) {
                    const kelasVal = kelasSelect.value || '';
                    if (kelasVal === '') {
                        params.delete('kelas');
                    } else {
                        params.set('kelas', kelasVal);
                    }
                }

                if (forceResetRange) {
                    params.set('range', modeSelect.value === 'monthly' ? 'this_month' : 'today');
                } else {
                    params.set('range', rangeSelect.value);
                }

                window.location.search = params.toString();
            }

            if (modeSelect) {
                modeSelect.addEventListener('change', function () {
                    applyDashboardFilters(true);
                });
            }
            if (rangeSelect) {
                rangeSelect.addEventListener('change', function () {
                    applyDashboardFilters(false);
                });
            }
            if (chartTypeSelect) {
                chartTypeSelect.addEventListener('change', function () {
                    applyDashboardFilters(false);
                });
            }
            if (jurusanSelect) {
                jurusanSelect.addEventListener('change', function () {
                    applyDashboardFilters(false);
                });
            }
            if (kelasSelect) {
                kelasSelect.addEventListener('change', function () {
                    applyDashboardFilters(false);
                });
            }

        </script>
    @endif
@endsection


