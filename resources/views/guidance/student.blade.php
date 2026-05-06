@extends('layouts.app', ['title' => $title])

@section('content')
<style>
    .guidance-student-table {
        table-layout: fixed;
        width: 100%;
    }

    .guidance-student-table th,
    .guidance-student-table td {
        vertical-align: top;
    }

    .guidance-student-table .text-cell {
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
</style>
<div class="card mb-14">
    <h3 class="mt-0">Catatan Bimbingan (Siswa)</h3>
    @if(session('success'))<div class="alert alert-success mb-10">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-error mb-10">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('guidance.student.store') }}">
        @csrf
        <label>Catatan Bimbingan</label>
        <textarea name="student_note" required {{ $isFriday ? '' : 'disabled' }}></textarea>
        <button type="submit" {{ $isFriday ? '' : 'disabled' }}>Simpan</button>
    </form>
</div>
<div class="card">
    <h4 class="mt-0">Riwayat</h4>
    <form method="GET" action="{{ route('guidance.student.index') }}" style="margin-bottom:10px; display:flex; gap:8px; align-items:end; flex-wrap:wrap;">
        <div>
            <label for="week_start">Minggu</label>
            <select id="week_start" name="week_start">
                @foreach(($weekOptions ?? collect()) as $week)
                    <option value="{{ $week['value'] }}" {{ (string) ($selectedWeekStart ?? '') === (string) $week['value'] ? 'selected' : '' }}>
                        {{ $week['label'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit">Tampilkan</button>
    </form>
    <div class="table-wrap">
        <table class="w-full guidance-student-table">
            <thead><tr><th>Tanggal</th><th>Absensi Sekolah</th><th>Catatan Siswa</th><th>Catatan Pembimbing 1</th><th>Catatan Pembimbing 2</th><th>Catatan Kajur</th><th>Status Akhir</th></tr></thead>
            <tbody>
            @forelse($notes as $n)
                @php
                    $dateKey = optional($n->guidance_date)->toDateString();
                    $schoolAttendance = strtoupper((string) (($attendanceMap[$dateKey]->status ?? '-') ?: '-'));
                    $finalStatusLabel = ($n->wakil_status ?? 'pending') === 'pending'
                        ? '-'
                        : strtoupper((string) ($n->final_attendance_status ?? '-'));
                @endphp
                <tr>
                    <td>{{ optional($n->guidance_date)->format('d M Y') }}</td>
                    <td>{{ $schoolAttendance }}</td>
                    <td class="text-cell">{{ $n->student_note }}</td>
                    <td class="text-cell">{{ $n->mentor1_status === 'approved' ? ($n->mentor1_note ?: '-') : '-' }}</td>
                    <td class="text-cell">{{ $n->mentor2_status === 'approved' ? ($n->mentor2_note ?: '-') : '-' }}</td>
                    <td class="text-cell">{{ $n->kajur_note ?: '-' }}</td>
                    <td>{{ $finalStatusLabel }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Belum ada catatan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
