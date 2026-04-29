@php
    $roleLabel = [
        'superadmin' => 'Superadmin',
        'admin_sekolah' => 'Admin Sekolah',
        'pembimbing_pkl' => 'instruktur',
        'instruktur' => 'pembimbing',
        'kajur' => 'Kajur',
        'kesiswaan' => 'Kesiswaan',
        'kepsek' => 'Kepsek',
        'wali_kelas' => 'Wali Kelas',
    ];
    $currentRoleLabel = $roleLabel[$role] ?? ucfirst(str_replace('_', ' ', (string) $role));
@endphp

@extends('layouts.app', ['title' => 'Validasi '.$currentRoleLabel])

@section('content')
    <style>
        .validation-toolbar {
            display: grid;
            grid-template-columns: minmax(260px, 2fr) repeat(3, minmax(140px, 1fr)) auto;
            gap: 10px;
            margin-bottom: 12px;
            align-items: end;
        }

        .validation-buckets {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .validation-bucket {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border: 1px solid #fdba74;
            border-radius: 10px;
            background: #fff7ed;
            color: #9a3412;
            text-decoration: none;
            font-weight: 700;
        }

        .validation-bucket.active {
            background: #ea580c;
            border-color: #ea580c;
            color: #fff;
        }

        .validation-toolbar .field {
            min-width: 0;
        }

        .validation-table th,
        .validation-table td {
            white-space: nowrap;
        }

        .validation-table td.wrap {
            white-space: normal;
        }

        .validation-toolbar .field.actions .btn {
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 16px;
            white-space: nowrap;
        }

        .detail-toggle {
            border: 1px solid #fdba74;
            background: #fff7ed;
            color: #9a3412;
            padding: 6px 10px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .detail-panel {
            border: 1px solid #fed7aa;
            border-radius: 12px;
            padding: 12px;
            background: #fff;
        }

        .detail-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-top: 10px;
        }

        .detail-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .detail-form textarea,
        .detail-form select {
            margin-bottom: 0 !important;
            width: 100%;
        }

        .detail-form .action-btn {
            align-self: flex-start;
            min-width: 100px;
        }
        .dual-action-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .detail-location-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .detail-map-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }
        .detail-map-card {
            border: 1px solid #fed7aa;
            border-radius: 12px;
            background: #fff7ed;
            padding: 10px;
        }
        .detail-map-card strong {
            display: block;
            margin-bottom: 8px;
            color: #9a3412;
        }
        .detail-map-frame {
            width: 100%;
            height: 260px;
            border: 1px solid #fdba74;
            border-radius: 10px;
            background: #fff;
        }

        .detail-split-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .detail-split-grid.two-col {
            grid-template-columns: 1fr 1fr;
        }

        .validation-block {
            border: 1px solid #fed7aa;
            border-radius: 12px;
            padding: 12px;
            background: #fff;
        }

        .validation-block h5 {
            margin: 0 0 10px 0;
            color: #9a3412;
        }

        .assessment-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 12px;
        }

        .assessment-grid label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .detail-modal {
            position: fixed;
            inset: 0;
            z-index: 1100;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(15, 23, 42, 0.45);
        }

        .detail-modal.open {
            display: flex;
        }

        .detail-modal-dialog {
            width: min(1240px, 96vw);
            max-height: 88vh;
            overflow: auto;
            background: #fff;
            border: 1px solid #fdba74;
            border-radius: 14px;
            box-shadow: 0 20px 40px rgba(2, 6, 23, 0.2);
        }

        .detail-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 14px;
            border-bottom: 1px solid #fed7aa;
            background: #fff7ed;
        }

        .detail-modal-title {
            margin: 0;
            color: #9a3412;
            font-size: 18px;
            font-weight: 700;
        }

        .detail-modal-close {
            border: 1px solid #fdba74;
            background: #fff;
            color: #9a3412;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            padding: 6px 12px;
        }

        .detail-modal-body {
            padding: 18px;
            background: #fff;
        }

        @media (max-width: 1180px) {
            .validation-toolbar {
                grid-template-columns: 1fr 1fr;
            }

            .detail-media-grid {
                grid-template-columns: 1fr;
            }

            .detail-split-grid.two-col {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .validation-toolbar {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @if (session('success'))
        <div class="card alert mb-16">
            {{ session('success') }}
        </div>
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

    <div class="card">
        @php
            $bucketLabel = [
                'pending_checkin' => 'Pending Check-in',
                'approved_checkin' => 'Approved Check-in',
                'rejected_checkin' => 'Rejected Check-in',
                'pending_checkout' => 'Pending Check-out',
                'approved_checkout' => 'Approved Check-out',
                'rejected_checkout' => 'Rejected Check-out',
            ][$bucket ?? 'pending_checkin'] ?? 'Pending Check-in';
            $baseQuery = request()->except('page', 'bucket');
        @endphp

        <h3 class="mt-0">Attendance Validation - {{ $bucketLabel }}</h3>

        <div class="validation-buckets">
            <a class="validation-bucket {{ ($bucket ?? 'pending_checkin') === 'pending_checkin' ? 'active' : '' }}"
                href="{{ route('validasi.index', array_merge($baseQuery, ['bucket' => 'pending_checkin'])) }}">
                Pending Check-in ({{ $bucketCounts['pending_checkin'] ?? 0 }})
            </a>
            <a class="validation-bucket {{ ($bucket ?? '') === 'pending_checkout' ? 'active' : '' }}"
                href="{{ route('validasi.index', array_merge($baseQuery, ['bucket' => 'pending_checkout'])) }}">
                Pending Check-out ({{ $bucketCounts['pending_checkout'] ?? 0 }})
            </a>
            <a class="validation-bucket {{ ($bucket ?? '') === 'approved_checkin' ? 'active' : '' }}"
                href="{{ route('validasi.index', array_merge($baseQuery, ['bucket' => 'approved_checkin'])) }}">
                Approved Check-in ({{ $bucketCounts['approved_checkin'] ?? 0 }})
            </a>
            <a class="validation-bucket {{ ($bucket ?? '') === 'approved_checkout' ? 'active' : '' }}"
                href="{{ route('validasi.index', array_merge($baseQuery, ['bucket' => 'approved_checkout'])) }}">
                Approved Check-out ({{ $bucketCounts['approved_checkout'] ?? 0 }})
            </a>
            <a class="validation-bucket {{ ($bucket ?? '') === 'rejected_checkin' ? 'active' : '' }}"
                href="{{ route('validasi.index', array_merge($baseQuery, ['bucket' => 'rejected_checkin'])) }}">
                Rejected Check-in ({{ $bucketCounts['rejected_checkin'] ?? 0 }})
            </a>
            <a class="validation-bucket {{ ($bucket ?? '') === 'rejected_checkout' ? 'active' : '' }}"
                href="{{ route('validasi.index', array_merge($baseQuery, ['bucket' => 'rejected_checkout'])) }}">
                Rejected Check-out ({{ $bucketCounts['rejected_checkout'] ?? 0 }})
            </a>
        </div>

        <form method="GET" action="{{ route('validasi.index') }}" class="validation-toolbar">
            <input type="hidden" name="bucket" value="{{ $bucket ?? 'pending_checkin' }}">
            <div class="field">
                <label for="q">Cari User</label>
                <input id="q" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Ketik nama, NIS, NUPTK, email...">
            </div>
            <div class="field">
                <label for="date_from">Tanggal Dari</label>
                <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="field">
                <label for="date_to">Tanggal Sampai</label>
                <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="field">
                <label for="per_page">Tampilkan</label>
                <select id="per_page" name="per_page">
                    @foreach (($filters['per_page_options'] ?? [10, 20, 50, 100]) as $opt)
                        <option value="{{ $opt }}" {{ (int) ($filters['per_page'] ?? 20) === (int) $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field actions">
                <a class="btn btn-ghost" href="{{ route('validasi.index') }}">Reset</a>
            </div>
        </form>

        @if ($attendances->isEmpty())
            <p>Tidak ada data pada kategori ini.</p>
        @else
            <div class="table-wrap">
                <table class="w-full validation-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>NIS</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attendances as $attendance)
                            @php
                                $activeBucket = (string) ($bucket ?? 'pending_checkin');
                                $isCheckinBucket = str_contains($activeBucket, 'checkin');
                                $stageStatusRaw = (string) ($isCheckinBucket
                                    ? ($attendance->checkin_stage_status ?? 'pending')
                                    : ($attendance->checkout_stage_status ?? 'pending'));
                                $statusText = str_replace('_', ' ', $stageStatusRaw);
                            @endphp
                            <tr>
                                <td class="wrap">{{ $attendance->user->name }}</td>
                                <td>{{ $attendance->user->nis }}</td>
                                <td>{{ $attendance->attendance_date->format('Y-m-d') }}</td>
                                <td>{{ $statusText }}</td>
                                <td>
                                    <button type="button" class="detail-toggle" data-detail-id="{{ $attendance->id }}">Detail</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-10">{{ $attendances->links() }}</div>

            <div id="attendance-detail-templates" style="display:none;">
                @foreach ($attendances as $attendance)
                    <div id="detail-content-{{ $attendance->id }}">
                        <div class="detail-panel">
                            @php
                                $checkInLat = $attendance->check_in_latitude;
                                $checkInLng = $attendance->check_in_longitude;
                                $checkOutLat = $attendance->check_out_latitude;
                                $checkOutLng = $attendance->check_out_longitude;
                                $checkInMapsUrl = (is_numeric($checkInLat) && is_numeric($checkInLng))
                                    ? 'https://www.google.com/maps?q='.$checkInLat.','.$checkInLng
                                    : null;
                                $checkOutMapsUrl = (is_numeric($checkOutLat) && is_numeric($checkOutLng))
                                    ? 'https://www.google.com/maps?q='.$checkOutLat.','.$checkOutLng
                                    : null;

                            @endphp

                            <div class="detail-map-grid">
                                @if (!str_contains((string) ($bucket ?? 'pending_checkin'), 'checkout') && $checkInMapsUrl)
                                    <div class="detail-map-card">
                                        <strong>Lokasi Check-in</strong>
                                        <iframe
                                            class="detail-map-frame"
                                            src="https://maps.google.com/maps?q={{ $checkInLat }},{{ $checkInLng }}&z=16&output=embed"
                                            loading="lazy"
                                            referrerpolicy="no-referrer-when-downgrade"
                                            title="Peta Lokasi Check-in"
                                        ></iframe>
                                    </div>
                                @endif
                                @if (!str_contains((string) ($bucket ?? 'pending_checkin'), 'checkin') && $checkOutMapsUrl)
                                    <div class="detail-map-card">
                                        <strong>Lokasi Check-out</strong>
                                        <iframe
                                            class="detail-map-frame"
                                            src="https://maps.google.com/maps?q={{ $checkOutLat }},{{ $checkOutLng }}&z=16&output=embed"
                                            loading="lazy"
                                            referrerpolicy="no-referrer-when-downgrade"
                                            title="Peta Lokasi Check-out"
                                        ></iframe>
                                    </div>
                                @endif
                            </div>
                            @php
                                $hasCheckoutData = $attendance->check_out_at || $attendance->check_out_summary;
                                $activeBucket = (string) ($bucket ?? 'pending_checkin');
                                $checkinStageStatus = (string) ($attendance->checkin_stage_status ?? 'pending');
                                $checkoutStageStatus = (string) ($attendance->checkout_stage_status ?? ($hasCheckoutData ? 'pending' : 'not_submitted'));
                                $isCheckinBucket = str_contains($activeBucket, 'checkin');
                                $showCheckinPanel = $isCheckinBucket;
                                $showCheckoutPanel = ! $isCheckinBucket && $hasCheckoutData;
                                $showCheckinActions = $activeBucket === 'pending_checkin' && $checkinStageStatus === 'pending';
                                $showCheckoutActions = $activeBucket === 'pending_checkout' && $checkoutStageStatus === 'pending' && $hasCheckoutData;
                                $canAddHigherNote = in_array($role, ['instruktur', 'kajur', 'superadmin'], true);
                                $showCheckinHigherNoteForm = $canAddHigherNote && in_array($checkinStageStatus, ['approved', 'rejected'], true);
                                $showCheckoutHigherNoteForm = $canAddHigherNote && $hasCheckoutData && in_array($checkoutStageStatus, ['approved', 'rejected'], true);
                            @endphp

                            <div class="detail-split-grid {{ $showCheckinPanel && $showCheckoutPanel ? 'two-col' : '' }}">
                                @if ($showCheckinPanel)
                                    <div class="validation-block">
                                        <h5>Validasi Check-in</h5>
                                        @php
                                            $checkInDateLabel = $attendance->attendance_date?->format('Y-m-d') ?? '-';
                                            $checkInTimeRaw = trim((string) ($attendance->check_in_at ?? ''));
                                            $checkInTimeLabel = $checkInTimeRaw !== '' ? date('H:i:s', strtotime($checkInTimeRaw)) : '-';
                                            $checkInLabel = trim((string) ($attendance->check_in_location_label ?? ''));
                                            $checkInAddress = trim((string) ($attendance->check_in_location_address ?? ''));
                                            $checkInCoord = (is_numeric($checkInLat) && is_numeric($checkInLng)) ? $checkInLat.', '.$checkInLng : '-';
                                        @endphp
                                        <p>Tanggal Check-in: {{ $checkInDateLabel }}</p>
                                        <p>Jam Check-in: {{ $checkInTimeLabel }}</p>
                                        <p>Status Check-in: {{ str_replace('_', ' ', $checkinStageStatus) }}</p>
                                        <p>Nama Siswa: {{ $attendance->user->name ?? '-' }}</p>
                                        <p>NIS: {{ $attendance->user->nis ?? '-' }}</p>
                                        <p>Kelas: {{ $attendance->user->class_name ?? '-' }}</p>
                                        <p>Lokasi Check-in: {{ $checkInLabel !== '' ? $checkInLabel : '-' }}</p>
                                        <p>Alamat Check-in: {{ $checkInAddress !== '' ? $checkInAddress : '-' }}</p>
                                        <p>Titik Map: {{ $checkInCoord }}</p>
                                        <p>IP Check-in: {{ $attendance->check_in_ip ?: '-' }}</p>
                                        <p>Device Check-in: -</p>
                                        @if ($attendance->pembimbing_note || $attendance->instruktur_note || $attendance->kajur_note)
                                            <div class="panel mb-10" style="border-color:#fed7aa; background:#fffaf5;">
                                                <p style="margin:0 0 6px;"><strong>Catatan Validasi</strong></p>
                                                @if ($attendance->pembimbing_note)
                                                    <p style="margin:0 0 4px;"><strong>Pembimbing:</strong> {{ $attendance->pembimbing_note }}</p>
                                                @endif
                                                @if ($attendance->instruktur_note)
                                                    <p style="margin:0 0 4px;"><strong>Pembimbing:</strong> {{ $attendance->instruktur_note }}</p>
                                                @endif
                                                @if ($attendance->kajur_note)
                                                    <p style="margin:0;"><strong>Kajur:</strong> {{ $attendance->kajur_note }}</p>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($showCheckinActions)
                                            <div class="detail-actions">
                                                <form action="{{ route('validasi.approve', $attendance) }}" method="POST" class="detail-form js-checkin-dual-form">
                                                    @csrf
                                                    <input type="hidden" name="validation_stage" value="checkin">
                                                    <input type="hidden" name="reject_reason_code" value="">
                                                    <textarea name="note" rows="2" placeholder="Catatan approve check-in (opsional)"></textarea>
                                                    <div class="dual-action-row">
                                                        <button
                                                            type="submit"
                                                            class="btn-success action-btn js-checkin-action-btn"
                                                            data-action="approve"
                                                            data-form-action="{{ route('validasi.approve', $attendance) }}"
                                                        >Setujui Check-in</button>
                                                        <button
                                                            type="submit"
                                                            class="btn-danger action-btn js-checkin-action-btn"
                                                            data-action="reject"
                                                            data-form-action="{{ route('validasi.reject', $attendance) }}"
                                                        >Tolak Check-in</button>
                                                    </div>
                                                </form>
                                            </div>
                                        @endif

                                        @if ($showCheckinHigherNoteForm)
                                            <div class="detail-actions">
                                                <form action="{{ route('validasi.note', $attendance) }}" method="POST" class="detail-form">
                                                    @csrf
                                                    <input type="hidden" name="validation_stage" value="checkin">
                                                    <textarea name="note" rows="2" placeholder="{{ in_array($role, ['kajur'], true) ? 'Catatan kajur (wajib)' : 'Catatan instruktur (wajib)' }}" required></textarea>
                                                    <button type="submit" class="btn btn-ghost action-btn">Simpan Catatan</button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if ($showCheckoutPanel)
                                    <div class="validation-block">
                                        <h5>Validasi Check-out</h5>
                                        @php
                                            $checkOutTimeRaw = trim((string) ($attendance->check_out_at ?? ''));
                                            $checkOutDateLabel = $checkOutTimeRaw !== '' ? date('Y-m-d', strtotime($checkOutTimeRaw)) : ($attendance->attendance_date?->format('Y-m-d') ?? '-');
                                            $checkOutTimeLabel = $checkOutTimeRaw !== '' ? date('H:i:s', strtotime($checkOutTimeRaw)) : '-';
                                            $checkOutLabel = trim((string) ($attendance->check_out_location_label ?? ''));
                                            $checkOutAddress = trim((string) ($attendance->check_out_location_address ?? ''));
                                            $checkOutCoord = (is_numeric($checkOutLat) && is_numeric($checkOutLng)) ? $checkOutLat.', '.$checkOutLng : '-';
                                        @endphp
                                        <p>Tanggal Check-out: {{ $checkOutDateLabel }}</p>
                                        <p>Jam Check-out: {{ $checkOutTimeLabel }}</p>
                                        <p>Status Check-out: {{ str_replace('_', ' ', $checkoutStageStatus) }}</p>
                                        <p>Nama Siswa: {{ $attendance->user->name ?? '-' }}</p>
                                        <p>NIS: {{ $attendance->user->nis ?? '-' }}</p>
                                        <p>Kelas: {{ $attendance->user->class_name ?? '-' }}</p>
                                        <p>Lokasi Check-out: {{ $checkOutLabel !== '' ? $checkOutLabel : '-' }}</p>
                                        <p>Alamat Check-out: {{ $checkOutAddress !== '' ? $checkOutAddress : '-' }}</p>
                                        <p>Titik Map: {{ $checkOutCoord }}</p>
                                        <p>IP Check-out: {{ $attendance->check_out_ip ?: '-' }}</p>
                                        <p>Device Check-out: -</p>
                                        <p class="mb-10">Ringkasan checkout: {{ $attendance->check_out_summary ?? '-' }}</p>
                                        @if ($attendance->pembimbing_note || $attendance->instruktur_note || $attendance->kajur_note)
                                            <div class="panel mb-10" style="border-color:#fed7aa; background:#fffaf5;">
                                                <p style="margin:0 0 6px;"><strong>Catatan Validasi</strong></p>
                                                @if ($attendance->pembimbing_note)
                                                    <p style="margin:0 0 4px;"><strong>Pembimbing:</strong> {{ $attendance->pembimbing_note }}</p>
                                                @endif
                                                @if ($attendance->instruktur_note)
                                                    <p style="margin:0 0 4px;"><strong>Pembimbing:</strong> {{ $attendance->instruktur_note }}</p>
                                                @endif
                                                @if ($attendance->kajur_note)
                                                    <p style="margin:0;"><strong>Kajur:</strong> {{ $attendance->kajur_note }}</p>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($attendance->report)
                                            @php
                                                $reportStatusRaw = strtolower(trim((string) ($attendance->report->review_status ?? '-')));
                                                $reportStatusLabel = match (true) {
                                                    $reportStatusRaw === '', $reportStatusRaw === '-' => '-',
                                                    $reportStatusRaw === 'pending_pembimbing' => 'pending pembimbing sekolah',
                                                    $reportStatusRaw === 'pending_instruktur' => 'approved pembimbing sekolah',
                                                    $reportStatusRaw === 'pending_kajur' => 'approved instruktur',
                                                    $reportStatusRaw === 'hadir',
                                                    $reportStatusRaw === 'approved_final',
                                                    str_starts_with($reportStatusRaw, 'approved'),
                                                    str_starts_with($reportStatusRaw, 'reviewed_') => 'approved',
                                                    default => str_replace('_', ' ', $reportStatusRaw),
                                                };
                                            @endphp
                                            <div class="text-muted mb-10">
                                                <p><strong>Status Laporan:</strong> {{ $reportStatusLabel }}</p>
                                                <p><strong>Rencana Pekerjaan:</strong> {{ $attendance->report->plan_work ?: '-' }}</p>
                                                <p><strong>Realisasi Pekerjaan:</strong> {{ $attendance->report->actual_work ?: '-' }}</p>
                                                <p><strong>Penugasan dari Atasan:</strong> {{ ($attendance->report->assigned_task ?? $attendance->report->special_assignment) ?: '-' }}</p>
                                                <p><strong>Masalah di Lapangan:</strong> {{ $attendance->report->field_issue ?: '-' }}</p>
                                            </div>
                                        @endif

                                        @if ($showCheckoutActions)
                                            <div class="detail-actions">
                                                <form action="{{ route('validasi.approve', $attendance) }}" method="POST" class="detail-form js-checkout-dual-form">
                                                    @csrf
                                                    <input type="hidden" name="validation_stage" value="checkout">
                                                    <input type="hidden" name="reject_reason_code" value="">
                                                    @if (in_array($role, ['pembimbing_pkl', 'superadmin'], true))
                                                        <div class="assessment-grid">
                                                            <label><input type="hidden" name="senyum_baik" value="0">Senyum baik <input type="checkbox" name="senyum_baik" value="1" checked></label>
                                                            <label><input type="hidden" name="keramahan_baik" value="0">Keramahan baik <input type="checkbox" name="keramahan_baik" value="1" checked></label>
                                                            <label><input type="hidden" name="penampilan_baik" value="0">Penampilan baik <input type="checkbox" name="penampilan_baik" value="1" checked></label>
                                                            <label><input type="hidden" name="komunikasi_baik" value="0">Komunikasi baik <input type="checkbox" name="komunikasi_baik" value="1" checked></label>
                                                            <label><input type="hidden" name="realisasi_kerja_baik" value="0">Realisasi kerja baik <input type="checkbox" name="realisasi_kerja_baik" value="1" checked></label>
                                                        </div>
                                                    @endif
                                                    <textarea name="note" rows="2" placeholder="Catatan approve check-out (opsional)"></textarea>
                                                    <div class="dual-action-row">
                                                        <button
                                                            type="submit"
                                                            class="btn-success action-btn js-checkout-action-btn"
                                                            data-action="approve"
                                                            data-form-action="{{ route('validasi.approve', $attendance) }}"
                                                        >Setujui Check-out</button>
                                                        <button
                                                            type="submit"
                                                            class="btn-danger action-btn js-checkout-action-btn"
                                                            data-action="reject"
                                                            data-form-action="{{ route('validasi.reject', $attendance) }}"
                                                        >Tolak Check-out</button>
                                                    </div>
                                                </form>
                                            </div>
                                        @endif

                                        @if ($showCheckoutHigherNoteForm)
                                            <div class="detail-actions">
                                                <form action="{{ route('validasi.note', $attendance) }}" method="POST" class="detail-form">
                                                    @csrf
                                                    <input type="hidden" name="validation_stage" value="checkout">
                                                    <textarea name="note" rows="2" placeholder="{{ in_array($role, ['kajur'], true) ? 'Catatan kajur (wajib)' : 'Catatan instruktur (wajib)' }}" required></textarea>
                                                    <button type="submit" class="btn btn-ghost action-btn">Simpan Catatan</button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div id="attendance-detail-modal" class="detail-modal" aria-hidden="true">
        <div class="detail-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="attendance-detail-modal-title">
            <div class="detail-modal-header">
                <h4 id="attendance-detail-modal-title" class="detail-modal-title">Detail Absensi</h4>
                <button type="button" class="detail-modal-close" id="attendance-detail-close">Tutup</button>
            </div>
            <div class="detail-modal-body" id="attendance-detail-modal-body"></div>
        </div>
    </div>

    <script>
        (function () {
            const filterForm = document.querySelector('.validation-toolbar');
            const qInput = document.getElementById('q');
            const dateFrom = document.getElementById('date_from');
            const dateTo = document.getElementById('date_to');
            const perPage = document.getElementById('per_page');

            let submitTimer = null;
            function submitFilters(delay = 0) {
                if (!filterForm) return;
                if (submitTimer) {
                    clearTimeout(submitTimer);
                }
                submitTimer = window.setTimeout(() => {
                    filterForm.submit();
                }, delay);
            }

            if (qInput) {
                qInput.addEventListener('input', function () {
                    submitFilters(350);
                });
            }
            if (dateFrom) {
                dateFrom.addEventListener('change', function () {
                    submitFilters(0);
                });
            }
            if (dateTo) {
                dateTo.addEventListener('change', function () {
                    submitFilters(0);
                });
            }
            if (perPage) {
                perPage.addEventListener('change', function () {
                    submitFilters(0);
                });
            }

            const modal = document.getElementById('attendance-detail-modal');
            const modalBody = document.getElementById('attendance-detail-modal-body');
            const closeBtn = document.getElementById('attendance-detail-close');

            function closeModal() {
                if (!modal || !modalBody) return;
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
                modalBody.innerHTML = '';
            }

            function openModal(detailId) {
                if (!modal || !modalBody || !detailId) return;
                const source = document.getElementById('detail-content-' + detailId);
                if (!source) return;
                modalBody.innerHTML = '';
                const sourceContent = source.firstElementChild ? source.firstElementChild.cloneNode(true) : source.cloneNode(true);
                modalBody.appendChild(sourceContent);
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }

            document.querySelectorAll('.detail-toggle').forEach((btn) => {
                btn.addEventListener('click', function () {
                    openModal(this.getAttribute('data-detail-id'));
                });
            });

            if (modalBody) {
                modalBody.addEventListener('click', function (event) {
                    const actionBtn = event.target.closest('.js-checkin-action-btn');
                    if (!actionBtn) return;

                    const form = actionBtn.closest('.js-checkin-dual-form');
                    if (!form) return;

                    const noteEl = form.querySelector('textarea[name="note"]');
                    const rejectCodeEl = form.querySelector('input[name="reject_reason_code"]');
                    const isReject = actionBtn.getAttribute('data-action') === 'reject';
                    const actionUrl = actionBtn.getAttribute('data-form-action') || form.getAttribute('action') || '';

                    form.setAttribute('action', actionUrl);
                    if (noteEl) {
                        noteEl.required = isReject;
                        noteEl.placeholder = isReject ? 'Alasan reject check-in (wajib)' : 'Catatan approve check-in (opsional)';
                    }
                    if (rejectCodeEl) {
                        rejectCodeEl.value = isReject ? 'reject_checkin' : '';
                    }
                });

                modalBody.addEventListener('click', function (event) {
                    const actionBtn = event.target.closest('.js-checkout-action-btn');
                    if (!actionBtn) return;

                    const form = actionBtn.closest('.js-checkout-dual-form');
                    if (!form) return;

                    const noteEl = form.querySelector('textarea[name="note"]');
                    const rejectCodeEl = form.querySelector('input[name="reject_reason_code"]');
                    const isReject = actionBtn.getAttribute('data-action') === 'reject';
                    const actionUrl = actionBtn.getAttribute('data-form-action') || form.getAttribute('action') || '';

                    form.setAttribute('action', actionUrl);
                    if (noteEl) {
                        noteEl.required = isReject;
                        noteEl.placeholder = isReject ? 'Alasan reject check-out (wajib)' : 'Catatan approve check-out (opsional)';
                    }
                    if (rejectCodeEl) {
                        rejectCodeEl.value = isReject ? 'reject_checkout' : '';
                    }
                });
            }
        })();
    </script>
@endsection


