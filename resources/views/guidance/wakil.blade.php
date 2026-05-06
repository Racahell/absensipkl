@extends('layouts.app', ['title' => $title])

@section('content')
<div class="card">
    <h3 class="mt-0">{{ $title }}</h3>
    @if(session('success'))<div class="alert alert-success mb-10">{{ session('success') }}</div>@endif
    <style>
        .wakil-filter-bar {
            display: flex;
            align-items: end;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .wakil-filter-item {
            min-width: 160px;
            max-width: 220px;
        }
        .wakil-filter-item input,
        .wakil-filter-item select {
            height: 40px;
        }
    </style>
    <div class="wakil-filter-bar">
        <div class="wakil-filter-item">
            <label for="filter-date">Tanggal</label>
            <input id="filter-date" type="date" value="">
        </div>
        <div class="wakil-filter-item">
            <label for="filter-status">Status</label>
            <select id="filter-status">
                <option value="">Semua Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Disetujui</option>
                <option value="rejected">Ditolak</option>
            </select>
        </div>
        <div class="wakil-filter-item">
            <label for="filter-q">Cari</label>
            <input id="filter-q" type="text" placeholder="Nama / NIS / Kelas">
        </div>
        <div class="flex items-center gap-8">
            <button type="button" id="filter-reset" class="btn btn-ghost">Reset</button>
        </div>
    </div>
    <div class="table-wrap">
        <table class="w-full">
            <thead><tr><th>Tanggal</th><th>Nama</th><th>NIS</th><th>Kelas</th><th>Catatan Siswa</th><th>Status</th><th>Validasi</th></tr></thead>
            <tbody>
            @forelse($notes as $n)
                <tr>
                    <td data-date="{{ optional($n->guidance_date)->toDateString() }}">{{ optional($n->guidance_date)->format('d M Y') }}</td>
                    <td data-name="{{ mb_strtolower((string) ($n->student?->name ?? '')) }}">{{ $n->student?->name }}</td>
                    <td data-nis="{{ mb_strtolower((string) ($n->student?->nis ?? '')) }}">{{ $n->student?->nis }}</td>
                    <td data-class="{{ mb_strtolower((string) ($n->student?->class_name ?? '')) }}">{{ $n->student?->class_name }}</td>
                    <td>{{ $n->student_note ?: '-' }}</td>
                    <td data-status="{{ $n->wakil_status }}">
                        @if ($n->wakil_status === 'approved')
                            Disetujui
                        @elseif ($n->wakil_status === 'rejected')
                            Ditolak
                        @else
                            Pending
                        @endif
                    </td>
                    <td>
                        @if (($n->wakil_status ?? 'pending') === 'pending')
                            <form method="POST" action="{{ route('guidance.wakil.validate', $n->id) }}" class="flex gap-8 wrap">@csrf
                                <button type="submit" name="approved" value="1">Setujui</button>
                                <button type="submit" name="approved" value="0" class="btn btn-danger">Tolak</button>
                            </form>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Belum ada data validasi.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-10 flex items-center justify-between wrap gap-8">
        <div>Menampilkan {{ $notes->firstItem() ?? 0 }}-{{ $notes->lastItem() ?? 0 }} dari {{ $notes->total() }} data</div>
        <div>{{ $notes->links() }}</div>
    </div>
</div>
<script>
    (function () {
        const dateInput = document.getElementById('filter-date');
        const statusSelect = document.getElementById('filter-status');
        const qInput = document.getElementById('filter-q');
        const resetBtn = document.getElementById('filter-reset');
        const rows = Array.from(document.querySelectorAll('table tbody tr'));
        const emptyText = 'Belum ada data validasi.';

        function applyFilter() {
            const dateVal = (dateInput?.value || '').trim();
            const statusVal = (statusSelect?.value || '').trim();
            const qVal = (qInput?.value || '').trim().toLowerCase();
            let shown = 0;

            rows.forEach((row) => {
                const dateCell = row.querySelector('td[data-date]');
                const statusCell = row.querySelector('td[data-status]');
                const nameCell = row.querySelector('td[data-name]');
                const nisCell = row.querySelector('td[data-nis]');
                const classCell = row.querySelector('td[data-class]');
                if (!dateCell || !statusCell || !nameCell || !nisCell || !classCell) return;

                const dateOk = dateVal === '' || (dateCell.dataset.date || '') === dateVal;
                const statusOk = statusVal === '' || (statusCell.dataset.status || '') === statusVal;
                const joined = `${nameCell.dataset.name || ''} ${nisCell.dataset.nis || ''} ${classCell.dataset.class || ''}`;
                const qOk = qVal === '' || joined.includes(qVal);
                const visible = dateOk && statusOk && qOk;
                row.style.display = visible ? '' : 'none';
                if (visible) shown++;
            });

            let emptyRow = document.getElementById('wakil-empty-row');
            if (!emptyRow) {
                emptyRow = document.createElement('tr');
                emptyRow.id = 'wakil-empty-row';
                emptyRow.innerHTML = '<td colspan="7" style="text-align:center;"></td>';
                rows[0]?.parentElement?.appendChild(emptyRow);
            }
            emptyRow.style.display = shown === 0 ? '' : 'none';
            emptyRow.querySelector('td').textContent = shown === 0 ? 'Tidak ada data sesuai filter.' : emptyText;
        }

        dateInput?.addEventListener('change', applyFilter);
        statusSelect?.addEventListener('change', applyFilter);
        qInput?.addEventListener('input', applyFilter);
        resetBtn?.addEventListener('click', function () {
            if (dateInput) dateInput.value = '';
            if (statusSelect) statusSelect.value = '';
            if (qInput) qInput.value = '';
            applyFilter();
        });
    })();
</script>
@endsection
