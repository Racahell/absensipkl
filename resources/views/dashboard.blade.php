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
            border: 1px solid var(--line);
            background: #fff;
            color: var(--accent-text);
            border-radius: 8px;
            padding: 5px 10px;
            min-width: 36px;
            cursor: pointer;
        }

        .wali-page-btn.active {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }

        .wali-page-btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }
        .student-calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 6px;
        }
        .student-calendar-weekday {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--accent-soft);
            color: var(--accent-text);
            font-weight: 700;
            text-align: center;
            padding: 6px;
            font-size: 12px;
        }
        .student-calendar-empty {
            border: 1px dashed var(--line);
            border-radius: 10px;
            min-height: 58px;
            background: #fff;
            opacity: .35;
        }
        .student-calendar-cell {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 8px;
            min-height: 58px;
            cursor: pointer;
            background: #fff;
        }
        .student-calendar-cell.hadir { background: #ecfdf3; border-color: #86efac; }
        .student-calendar-cell.alpha { background: #fef2f2; border-color: #fca5a5; }
        .student-calendar-cell.pending { background: var(--accent-soft); border-color: var(--line); }
        .student-calendar-cell.no_schedule { background: #f8fafc; border-color: #e5e7eb; }
        .student-calendar-cell.is-holiday { box-shadow: inset 0 0 0 1px #ef4444; }
        .student-calendar-cell .day { font-weight: 700; color: var(--accent-text); }
        .student-calendar-cell .label { font-size: 11px; color: var(--muted); margin-top: 4px; }
        .student-calendar-cell .holiday-label { font-size: 10px; color: #b91c1c; font-weight: 700; margin-top: 3px; }
        .monthly-filter-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(150px, 1fr)) auto;
            gap: 8px;
            align-items: end;
            margin-bottom: 10px;
        }
        .monthly-filter-field {
            min-width: 0;
        }
        .monthly-filter-field label {
            display: block;
        }
        .monthly-filter-field select {
            width: 100%;
        }
        @media (max-width: 640px) {
            .monthly-filter-form {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @if ($activeRole === 'pembimbing_pkl' && ! empty($pembimbingSummary))
        <div style="display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:10px; margin-bottom:14px;">
            @foreach ($pembimbingSummary['cards'] as $card)
                <div class="card" style="padding:14px;">
                    <div style="font-size:12px; color:var(--accent-text);">{{ $card['label'] }}</div>
                    <div style="font-size:24px; font-weight:700; color:var(--accent-text);">{{ $card['value'] }}</div>
                </div>
            @endforeach
        </div>
    @endif

    @if (! empty($kpiCards))
        <div style="display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:10px; margin-bottom:14px;">
            @foreach ($kpiCards as $card)
                <div class="card" style="padding:14px;">
                    <div style="font-size:12px; color:var(--accent-text);">{{ $card['label'] }}</div>
                    <div style="font-size:24px; font-weight:700; color:var(--accent-text);">{{ $card['value'] }}</div>
                </div>
            @endforeach
        </div>
    @endif

    @if (! empty($showAnalytics))
        <div class="card" style="margin-bottom: 14px;">
            <h3 style="margin-top:0; color:var(--accent-text);">Diagram Kehadiran</h3>
            @if ($activeRole === 'instruktur')
                <small style="display:block; margin-bottom:8px; color:var(--muted);">
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
            <a href="{{ route('absensi.checkin.page') }}" style="display:inline-block; margin-top:10px; text-decoration:none; padding:9px 12px; border:1px solid var(--accent); background:var(--accent); color:#fff; border-radius:8px;">
                Menu Check-in
            </a>
            <a href="{{ route('absensi.checkout.page') }}" style="display:inline-block; margin-top:10px; margin-left:8px; text-decoration:none; padding:9px 12px; border:1px solid var(--accent); background:var(--accent); color:#fff; border-radius:8px;">
                Menu Check-out
            </a>
            <a href="{{ route('pengajuan.index') }}" style="display:inline-block; margin-top:10px; margin-left:8px; text-decoration:none; padding:9px 12px; border:1px solid var(--accent); background:var(--accent); color:#fff; border-radius:8px;">
                Pengajuan Izin/Sakit
            </a>
            @elseif ($activeRole === 'pembimbing_pkl')
            <a href="{{ route('validasi.index') }}" style="display:inline-block; margin-top:10px; text-decoration:none; padding:9px 12px; border:1px solid var(--accent); background:var(--accent); color:#fff; border-radius:8px;">
                Buka Menu Validasi
            </a>
            <a href="{{ route('validasi.pengajuan.index') }}" style="display:inline-block; margin-top:10px; margin-left:8px; text-decoration:none; padding:9px 12px; border:1px solid var(--accent); background:var(--accent); color:#fff; border-radius:8px;">
                Validasi Pengajuan
            </a>
            @elseif ($activeRole === 'instruktur')
            <a href="{{ route('reports.weekly') }}" style="display:inline-block; margin-top:10px; text-decoration:none; padding:9px 12px; border:1px solid var(--accent); background:var(--accent); color:#fff; border-radius:8px;">
                Catatan Mingguan
            </a>
            <a href="{{ route('reports.weekly.recap') }}" style="display:inline-block; margin-top:10px; margin-left:8px; text-decoration:none; padding:9px 12px; border:1px solid var(--accent); background:var(--accent); color:#fff; border-radius:8px;">
                Rekap Mingguan
            </a>
            @elseif ($activeRole === 'kajur')
            <a href="{{ route('reports.weekly') }}" style="display:inline-block; margin-top:10px; text-decoration:none; padding:9px 12px; border:1px solid var(--accent); background:var(--accent); color:#fff; border-radius:8px;">
                Rekap Jurusan
            </a>
            @endif
        </div>
    @endif

    @if ($activeRole === 'siswa' && ! empty($studentDashboard))
        <div class="card" style="margin-bottom:14px;">
            <h3 style="margin-top:0; color:var(--accent-text);">Ringkasan Siswa</h3>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:10px;">
                <div class="panel" style="padding:12px;">
                    <div style="font-size:12px; color:var(--accent-text);">Status Absensi Hari Ini</div>
                    <div style="font-size:20px; font-weight:700; color:var(--accent-text);">{{ $studentDashboard['todayAttendanceStatus'] }}</div>
                </div>
                <div class="panel" style="padding:12px;">
                    <div style="font-size:12px; color:var(--accent-text);">Pengajuan Terakhir</div>
                    <div style="font-size:16px; font-weight:700; color:var(--accent-text);">{{ $studentDashboard['latestLeaveStatus'] }}</div>
                    <small style="color:var(--muted);">{{ $studentDashboard['latestLeaveDate'] }}</small>
                </div>
            </div>
        </div>

        @if (! empty($studentMonthlySummary))
            <div class="card" style="margin-bottom:14px;">
                <h3 style="margin-top:0; color:var(--accent-text);">Ringkasan Catatan Bulanan</h3>
                <form method="GET" class="monthly-filter-form">
                    <div class="monthly-filter-field">
                        <label>Bulan</label>
                        <select name="month">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ (int) ($studentMonthlySummary['month'] ?? 0) === $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="monthly-filter-field">
                        <label>Tahun</label>
                        <select name="year">
                            @for ($y = now()->year + 1; $y >= 2020; $y--)
                                <option value="{{ $y }}" {{ (int) ($studentMonthlySummary['year'] ?? 0) === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <button type="submit">Tampilkan</button>
                </form>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:10px;">
                    <div class="panel"><div>Total Hadir</div><strong>{{ $studentMonthlySummary['total_hadir'] }}</strong></div>
                    <div class="panel"><div>Total Alpha</div><strong>{{ $studentMonthlySummary['total_alpha'] }}</strong></div>
                    <div class="panel"><div>Total Catatan Dibuat</div><strong>{{ $studentMonthlySummary['total_catatan_dibuat'] }}</strong></div>
                    <div class="panel"><div>Total Catatan Disetujui</div><strong>{{ $studentMonthlySummary['total_catatan_disetujui'] }}</strong></div>
                    <div class="panel"><div>Tolak/Belum Validasi</div><strong>{{ $studentMonthlySummary['total_catatan_tolak_belum_validasi'] }}</strong></div>
                </div>
            </div>
        @endif

        @if (! empty($studentCalendar))
            <div class="card" style="margin-bottom:14px;">
                <h3 style="margin-top:0; color:var(--accent-text);">Kalender Absensi & Catatan</h3>
                <div class="flex gap-8 wrap mb-10">
                    <span class="panel" style="padding:6px 10px; background:#ecfdf3; border-color:#86efac;">Hadir</span>
                    <span class="panel" style="padding:6px 10px; background:#fef2f2; border-color:#fca5a5;">Alpha</span>
                    <span class="panel" style="padding:6px 10px; background:var(--accent-soft); border-color:var(--line);">Menunggu Validasi</span>
                    <span class="panel" style="padding:6px 10px; background:#f8fafc; border-color:#e5e7eb;">Tidak Ada Jadwal</span>
                    <span class="panel" style="padding:6px 10px; background:#fff1f2; border-color:#fca5a5;">Tanggal Merah</span>
                </div>
                @php
                    $weekdays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                    $firstDate = $studentCalendar[0]['date'] ?? null;
                    $leadingPad = 0;
                    if ($firstDate) {
                        $leadingPad = \Illuminate\Support\Carbon::parse($firstDate)->dayOfWeekIso - 1;
                    }
                    $cellCount = $leadingPad + count($studentCalendar);
                    $trailingPad = (7 - ($cellCount % 7)) % 7;
                @endphp
                <div class="student-calendar-grid mb-10">
                    @foreach ($weekdays as $weekday)
                        <div class="student-calendar-weekday">{{ $weekday }}</div>
                    @endforeach
                    @for ($i = 0; $i < $leadingPad; $i++)
                        <div class="student-calendar-empty" aria-hidden="true"></div>
                    @endfor
                    @foreach ($studentCalendar as $item)
                        <button
                            type="button"
                            class="student-calendar-cell {{ $item['status_key'] }} {{ !empty($item['is_holiday']) ? 'is-holiday' : '' }}"
                            data-calendar-detail='@json($item['detail'])'
                        >
                            <div class="day">{{ $item['day'] }}</div>
                            <div class="label">{{ $item['status_label'] }}</div>
                            @if (!empty($item['holiday_label']))
                                <div class="holiday-label">{{ $item['holiday_label'] }}</div>
                            @endif
                        </button>
                    @endforeach
                    @for ($i = 0; $i < $trailingPad; $i++)
                        <div class="student-calendar-empty" aria-hidden="true"></div>
                    @endfor
                </div>
                <div id="student-calendar-detail" class="panel">
                    Klik tanggal untuk melihat detail.
                </div>
            </div>
        @endif
    @endif

    @if ($activeRole === 'instruktur' && ! empty($instrukturSummary) && ! empty($instrukturSummary['departmentName']))
        <div class="card" style="margin-bottom:14px;">
            <h3 style="margin-top:0; color:var(--accent-text);">Ringkasan Instruktur - {{ $instrukturSummary['departmentName'] }}</h3>
            <div class="card" style="margin-bottom:12px;">
                <h4 style="margin-top:0; color:var(--accent-text);">Catatan Mingguan Terakhir</h4>
                @forelse (($instrukturSummary['weeklyNotes'] ?? []) as $item)
                    <div style="padding:8px 0; border-bottom:1px solid #fed7aa;">
                        <small style="color:var(--muted);">Minggu {{ optional($item->week_start)->format('d M') }} - {{ optional($item->week_end)->format('d M Y') }}</small>
                        <div style="margin-top:4px; color:var(--accent-text);">{{ $item->instruktur_note ?: 'Belum ada catatan instruktur.' }}</div>
                    </div>
                @empty
                    <small style="color:var(--muted);">Belum ada catatan mingguan minggu ini.</small>
                @endforelse
            </div>
            <div style="display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:10px; margin-bottom:12px;">
                @foreach (($instrukturSummary['cards'] ?? []) as $card)
                    <div class="panel" style="padding:12px;">
                        <div style="font-size:12px; color:var(--accent-text);">{{ $card['label'] }}</div>
                        <div style="font-size:22px; font-weight:700; color:var(--accent-text);">{{ $card['value'] }}</div>
                    </div>
                @endforeach
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:10px;">
                <div class="card">
                    <h4 style="margin-top:0; color:var(--accent-text);">Pending Validasi Absensi</h4>
                    @forelse (($instrukturSummary['pendingAttendance'] ?? []) as $item)
                        <div style="padding:8px 0; border-bottom:1px solid #fed7aa;">
                            <div style="font-weight:600; color:var(--accent-text);">{{ $item->user?->name ?? '-' }}</div>
                            @php
                                $statusRaw = strtolower(trim((string) ($item->status ?? '-')));
                                $statusLabel = match (true) {
                                    $statusRaw === '', $statusRaw === '-' => '-',
                                    $statusRaw === 'pending',
                                    $statusRaw === 'pending_pembimbing',
                                    $statusRaw === 'pending_instruktur',
                                    $statusRaw === 'pending_kajur' => '-',
                                    $statusRaw === 'hadir',
                                    $statusRaw === 'approved_final',
                                    str_starts_with($statusRaw, 'approved'),
                                    str_starts_with($statusRaw, 'reviewed_') => 'approved',
                                    default => str_replace('_', ' ', $statusRaw),
                                };
                            @endphp
                            <small style="color:var(--muted);">{{ optional($item->attendance_date)->format('d M Y') }} - {{ strtoupper($statusLabel) }}</small>
                        </div>
                    @empty
                        <small style="color:var(--muted);">Belum ada pending validasi absensi.</small>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @if ($activeRole === 'kajur' && ! empty($kajurSummary) && ! empty($kajurSummary['departmentName']))
        <div class="card" style="margin-bottom:14px;">
            <h3 style="margin-top:0; color:var(--accent-text);">Ringkasan Kajur - {{ $kajurSummary['departmentName'] }}</h3>
            <div style="display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:10px; margin-bottom:12px;">
                @foreach (($kajurSummary['cards'] ?? []) as $card)
                    <div class="panel" style="padding:12px;">
                        <div style="font-size:12px; color:var(--accent-text);">{{ $card['label'] }}</div>
                        <div style="font-size:22px; font-weight:700; color:var(--accent-text);">{{ $card['value'] }}</div>
                    </div>
                @endforeach
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:10px;">
                <div class="card">
                    <h4 style="margin-top:0; color:var(--accent-text);">Siswa Alpha Hari Ini</h4>
                    @forelse (($kajurSummary['alphaTodayRows'] ?? []) as $item)
                        <div style="padding:8px 0; border-bottom:1px solid #fed7aa;">
                            <div style="font-weight:600; color:var(--accent-text);">{{ $item->user?->name ?? '-' }} ({{ $item->user?->nis ?? '-' }})</div>
                            <small style="color:var(--muted);">{{ optional($item->attendance_date)->format('d M Y') }}</small>
                        </div>
                    @empty
                        <small style="color:var(--muted);">Tidak ada siswa alpha hari ini.</small>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @if ($activeRole === 'wakil_kepsek' && ! empty($wakilSummary))
        <div class="card" style="margin-bottom:14px;">
            <h3 style="margin-top:0; color:var(--accent-text);">Ringkasan Wakil Kepsek</h3>
            <div style="display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:10px; margin-bottom:12px;">
                @foreach (($wakilSummary['cards'] ?? []) as $card)
                    <div class="panel" style="padding:12px;">
                        <div style="font-size:12px; color:var(--accent-text);">{{ $card['label'] }}</div>
                        <div style="font-size:22px; font-weight:700; color:var(--accent-text);">{{ $card['value'] }}</div>
                    </div>
                @endforeach
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:10px;">
                <div class="card">
                    <h4 style="margin-top:0; color:var(--accent-text);">Tren 7 Hari (Pending / Approved / Rejected)</h4>
                    <div class="table-wrap">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Pending</th>
                                    <th>Approved</th>
                                    <th>Rejected</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (($wakilSummary['trend']['labels'] ?? []) as $idx => $label)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td>{{ (int) (($wakilSummary['trend']['pending'][$idx] ?? 0)) }}</td>
                                        <td>{{ (int) (($wakilSummary['trend']['approved'][$idx] ?? 0)) }}</td>
                                        <td>{{ (int) (($wakilSummary['trend']['rejected'][$idx] ?? 0)) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <h4 style="margin-top:0; color:var(--accent-text);">Distribusi per Jurusan</h4>
                    <div class="table-wrap">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th>Jurusan</th>
                                    <th>Pending</th>
                                    <th>Approved</th>
                                    <th>Rejected</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($wakilSummary['departmentSummary'] ?? []) as $row)
                                    <tr>
                                        <td>{{ $row->department_name }}</td>
                                        <td>{{ (int) ($row->pending_count ?? 0) }}</td>
                                        <td>{{ (int) ($row->approved_count ?? 0) }}</td>
                                        <td>{{ (int) ($row->rejected_count ?? 0) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" style="text-align:center;">Belum ada data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top:10px;">
                <h4 style="margin-top:0; color:var(--accent-text);">Daftar Menunggu Validasi</h4>
                @forelse (($wakilSummary['pendingRows'] ?? []) as $item)
                    <div style="padding:8px 0; border-bottom:1px solid #fed7aa;">
                        <div style="font-weight:600; color:var(--accent-text);">
                            {{ $item->student?->name ?? '-' }} ({{ $item->student?->nis ?? '-' }})
                        </div>
                        <small style="color:var(--muted);">
                            {{ $item->student?->class_name ?? '-' }} - {{ $item->student?->department_name ?? '-' }} - {{ optional($item->guidance_date)->format('d M Y') }}
                        </small>
                    </div>
                @empty
                    <small style="color:var(--muted);">Tidak ada data pending validasi kehadiran.</small>
                @endforelse
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:10px; margin-top:10px;">
                <div class="card">
                    <h4 style="margin-top:0; color:var(--accent-text);">Terbaru Disetujui</h4>
                    @forelse (($wakilSummary['approvedRows'] ?? []) as $item)
                        <div style="padding:8px 0; border-bottom:1px solid #fed7aa;">
                            <div style="font-weight:600; color:var(--accent-text);">
                                {{ $item->student?->name ?? '-' }} ({{ $item->student?->nis ?? '-' }})
                            </div>
                            <small style="color:var(--muted);">
                                {{ $item->student?->class_name ?? '-' }} - {{ $item->student?->department_name ?? '-' }} - {{ optional($item->guidance_date)->format('d M Y') }}
                            </small>
                        </div>
                    @empty
                        <small style="color:var(--muted);">Belum ada data disetujui.</small>
                    @endforelse
                </div>

                <div class="card">
                    <h4 style="margin-top:0; color:var(--accent-text);">Terbaru Ditolak</h4>
                    @forelse (($wakilSummary['rejectedRows'] ?? []) as $item)
                        <div style="padding:8px 0; border-bottom:1px solid #fed7aa;">
                            <div style="font-weight:600; color:var(--accent-text);">
                                {{ $item->student?->name ?? '-' }} ({{ $item->student?->nis ?? '-' }})
                            </div>
                            <small style="color:var(--muted);">
                                {{ $item->student?->class_name ?? '-' }} - {{ $item->student?->department_name ?? '-' }} - {{ optional($item->guidance_date)->format('d M Y') }}
                            </small>
                        </div>
                    @empty
                        <small style="color:var(--muted);">Belum ada data ditolak.</small>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @if ($activeRole === 'pembimbing_pkl' && ! empty($pembimbingSummary))
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:10px;">
            <div class="card">
                <h3 style="margin-top:0; color:var(--accent-text);">Pending Absensi</h3>
                @forelse ($pembimbingSummary['pendingAbsensi'] as $item)
                    <div style="padding:8px 0; border-bottom:1px solid #fed7aa;">
                        <div style="font-weight:600; color:var(--accent-text);">{{ $item->user?->name ?? $item->student?->name ?? '-' }}</div>
                        <small style="color:var(--muted);">{{ optional($item->attendance_date ?? $item->guidance_date ?? null)->format('d M Y') }}</small>
                    </div>
                @empty
                    <small style="color:var(--muted);">Tidak ada pending absensi.</small>
                @endforelse
            </div>

            <div class="card">
                <h3 style="margin-top:0; color:var(--accent-text);">Pending Pengajuan</h3>
                @forelse ($pembimbingSummary['pendingPengajuan'] as $item)
                    <div style="padding:8px 0; border-bottom:1px solid #fed7aa;">
                        <div style="font-weight:600; color:var(--accent-text);">{{ $item->user?->name ?? '-' }}</div>
                        <small style="color:var(--muted);">{{ optional($item->request_date)->format('d M Y') }} - {{ strtoupper($item->type) }}</small>
                    </div>
                @empty
                    <small style="color:var(--muted);">Tidak ada pending pengajuan.</small>
                @endforelse
            </div>

            <div class="card">
                <h3 style="margin-top:0; color:var(--accent-text);">Pending Laporan</h3>
                @forelse ($pembimbingSummary['pendingLaporan'] as $item)
                    <div style="padding:8px 0; border-bottom:1px solid #fed7aa;">
                        <div style="font-weight:600; color:var(--accent-text);">{{ $item->attendance?->user?->name ?? '-' }}</div>
                        <small style="color:var(--muted);">Laporan #{{ $item->id }}</small>
                    </div>
                @empty
                    <small style="color:var(--muted);">Tidak ada pending laporan.</small>
                @endforelse
            </div>
        </div>
    @endif

    @if ($activeRole === 'wali_kelas' && ! empty($waliSummary))
        <div class="card" style="margin-bottom:14px;">
            <h3 style="margin-top:0; color:var(--accent-text);">Data Kelas Wali</h3>
            @if (empty($waliSummary['className']))
                <small style="color:var(--muted);">Kelas wali belum diatur. Silakan isi `class_name` pada akun wali kelas.</small>
            @else
                <div style="display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:10px; margin-bottom:10px;">
                    @foreach (($waliSummary['cards'] ?? []) as $card)
                        <div class="panel" style="padding:12px;">
                            <div style="font-size:12px; color:var(--accent-text);">{{ $card['label'] }}</div>
                            <div style="font-size:22px; font-weight:700; color:var(--accent-text);">{{ $card['value'] }}</div>
                        </div>
                    @endforeach
                </div>
                <h4 style="margin:0 0 10px 0; color:var(--accent-text);">Kelas Wali: {{ $waliSummary['className'] }}</h4>
                <div style="margin-bottom:10px; color:var(--accent-text);">Total Siswa: <strong>{{ $waliSummary['students']->count() }}</strong></div>
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
                                    <td colspan="5" style="text-align:center; color:var(--accent-text);">Belum ada siswa pada kelas ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div id="wali-student-pagination" class="wali-pagination" style="display:none;"></div>

                <h4 style="margin:14px 0 10px 0; color:var(--accent-text);">Monitoring Harian</h4>
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
                                    <td colspan="6" style="text-align:center; color:var(--accent-text);">Belum ada data monitoring harian.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div id="wali-monitor-pagination" class="wali-pagination" style="display:none;"></div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:10px; margin-top:14px;">
                    <div class="card">
                        <h4 style="margin-top:0; color:var(--accent-text);">Siswa Alpha Terbanyak</h4>
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
                        <h4 style="margin-top:0; color:var(--accent-text);">Siswa Pending Validasi</h4>
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
                        <h4 style="margin-top:0; color:var(--accent-text);">Top 10 Siswa Terbaik</h4>
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
                        <h4 style="margin-top:0; color:var(--accent-text);">Top 10 Siswa Risiko Tertinggi</h4>
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

                <h4 style="margin:14px 0 10px 0; color:var(--accent-text);">Smart Alert Kelas</h4>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:10px;">
                    <div class="card">
                        <strong>Alpha &gt; 2 hari</strong>
                        @forelse (($waliSummary['analytics']['alerts']['alpha_over_2'] ?? []) as $row)
                            <div style="margin-top:8px;">{{ $row['name'] }} - {{ $row['alpha'] }} hari</div>
                        @empty
                            <div style="margin-top:8px; color:var(--muted);">Tidak ada alert.</div>
                        @endforelse
                    </div>
                    <div class="card">
                        <strong>Pending &gt; 2 hari</strong>
                        @forelse (($waliSummary['analytics']['alerts']['pending_over_2'] ?? []) as $row)
                            <div style="margin-top:8px;">{{ $row['name'] }} - {{ $row['pending_days_count'] }} hari</div>
                        @empty
                            <div style="margin-top:8px; color:var(--muted);">Tidak ada alert.</div>
                        @endforelse
                    </div>
                    <div class="card">
                        <strong>Report kosong &gt; 2 hari</strong>
                        @forelse (($waliSummary['analytics']['alerts']['missing_report_over_2'] ?? []) as $row)
                            <div style="margin-top:8px;">{{ $row['name'] }} - {{ $row['missing_report_days_count'] }} hari</div>
                        @empty
                            <div style="margin-top:8px; color:var(--muted);">Tidak ada alert.</div>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    @endif

    <script>
        (function () {
            const detailEl = document.getElementById('student-calendar-detail');
            const cells = Array.from(document.querySelectorAll('.student-calendar-cell[data-calendar-detail]'));
            if (!detailEl || cells.length === 0) return;

            cells.forEach((cell) => {
                cell.addEventListener('click', function () {
                    const raw = cell.getAttribute('data-calendar-detail');
                    if (!raw) return;
                    let data = null;
                    try { data = JSON.parse(raw); } catch (e) { data = null; }
                    if (!data) return;
                    detailEl.innerHTML = [
                        `<div><strong>Tanggal:</strong> ${data.tanggal ?? '-'}</div>`,
                        `<div><strong>Hari:</strong> ${data.hari ?? '-'}</div>`,
                        `<div><strong>Tanggal Merah:</strong> ${data.tanggal_merah ?? '-'}</div>`,
                        `<div><strong>Status Absensi:</strong> ${data.status_absensi ?? '-'}</div>`,
                        `<div><strong>Catatan Siswa:</strong> ${data.catatan_siswa ?? '-'}</div>`,
                        `<div><strong>Catatan Pembimbing 1:</strong> ${data.catatan_pembimbing_1 ?? '-'}</div>`,
                        `<div><strong>Catatan Pembimbing 2:</strong> ${data.catatan_pembimbing_2 ?? '-'}</div>`,
                        `<div><strong>Catatan Kajur:</strong> ${data.catatan_kajur ?? '-'}</div>`,
                        `<div><strong>Status Validasi Wakil Kepala Sekolah:</strong> ${data.status_validasi_wakil ?? '-'}</div>`
                    ].join('');
                });
            });
        })();
    </script>

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



