@extends('layouts.app', ['title' => $title])

@section('content')
    <div class="card mb-16">
        <h3 class="mt-0">Detail Absensi Siswa</h3>
        <p><strong>Nama:</strong> {{ $student->name }}</p>
        <p><strong>NIS:</strong> {{ $student->nis ?? '-' }}</p>
        <p><strong>Kelas:</strong> {{ $student->class_name ?? '-' }}</p>
        <p><strong>Jurusan:</strong> {{ $departmentName }}</p>
        <a href="{{ route('kajur.students.index', array_merge($departmentName !== '' ? ['jurusan' => $departmentName] : [], request()->filled('class_name') ? ['class_name' => request('class_name')] : [])) }}" class="btn btn-ghost" style="text-decoration:none;">Kembali ke daftar</a>
    </div>

    <div class="card">
        <div style="display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:10px; margin-bottom:12px;">
            <div class="panel" style="padding:10px;">
                <div style="font-size:12px; color:#9a3412;">Alamat Sama</div>
                <div style="font-size:20px; font-weight:700; color:#7c2d12;">{{ $addressConsistencySummary['same'] ?? 0 }}</div>
            </div>
            <div class="panel" style="padding:10px;">
                <div style="font-size:12px; color:#9a3412;">Alamat Berbeda</div>
                <div style="font-size:20px; font-weight:700; color:#7c2d12;">{{ $addressConsistencySummary['different'] ?? 0 }}</div>
            </div>
            <div class="panel" style="padding:10px;">
                <div style="font-size:12px; color:#9a3412;">Belum Bisa Dibandingkan</div>
                <div style="font-size:20px; font-weight:700; color:#7c2d12;">{{ $addressConsistencySummary['no_compare'] ?? 0 }}</div>
            </div>
            <div class="panel" style="padding:10px;">
                <div style="font-size:12px; color:#9a3412;">Alamat Tidak Tersedia</div>
                <div style="font-size:20px; font-weight:700; color:#7c2d12;">{{ $addressConsistencySummary['no_address'] ?? 0 }}</div>
            </div>
        </div>
        <div class="table-wrap">
            <table class="w-full">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jam Check-in</th>
                        <th>Lokasi Check-in</th>
                        <th>Konsistensi Alamat</th>
                        <th>IP Check-in</th>
                        <th>Jam Check-out</th>
                        <th>Lokasi Check-out</th>
                        <th>IP Check-out</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendances as $attendance)
                        <tr>
                            <td>{{ $attendance->attendance_date?->format('Y-m-d') ?? '-' }}</td>
                            <td>{{ $attendance->check_in_at?->format('H:i:s') ?? '-' }}</td>
                            <td>
                                <div>{{ $attendance->check_in_location_label ?: '-' }}</div>
                                <small class="text-muted">{{ $attendance->check_in_location_address ?: ($attendance->check_in_latitude && $attendance->check_in_longitude ? $attendance->check_in_latitude.', '.$attendance->check_in_longitude : '-') }}</small>
                            </td>
                            <td>
                                @php
                                    $consistency = (string) ($attendance->address_consistency ?? 'no_compare');
                                    $consistencyLabel = match ($consistency) {
                                        'same' => 'Sama',
                                        'different' => 'Berbeda',
                                        'no_address' => 'Alamat Tidak Tersedia',
                                        default => 'Belum Bisa Dibandingkan',
                                    };
                                @endphp
                                {{ $consistencyLabel }}
                            </td>
                            <td>{{ $attendance->check_in_ip ?: '-' }}</td>
                            <td>{{ $attendance->check_out_at?->format('H:i:s') ?? '-' }}</td>
                            <td>
                                <div>{{ $attendance->check_out_location_label ?: '-' }}</div>
                                <small class="text-muted">{{ $attendance->check_out_location_address ?: ($attendance->check_out_latitude && $attendance->check_out_longitude ? $attendance->check_out_latitude.', '.$attendance->check_out_longitude : '-') }}</small>
                            </td>
                            <td>{{ $attendance->check_out_ip ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;">Belum ada riwayat absensi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-10">{{ $attendances->links() }}</div>
    </div>
@endsection
