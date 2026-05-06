@extends('layouts.app', ['title' => $title])

@section('content')
<div class="card">
    <h3 class="mt-0">Daftar Siswa Catatan Bimbingan</h3>
    @if(session('success'))<div class="alert alert-success mb-10">{{ session('success') }}</div>@endif
    <div id="autosave-status" class="mb-10" style="font-size:12px; color:#6b7280;"></div>
    <div class="table-wrap">
        <table class="w-full">
            <thead><tr><th>No</th><th>Nama</th><th>NIS</th><th>Kelas</th><th>Catatan Siswa</th><th>Catatan Pembimbing</th></tr></thead>
            <tbody>
            @forelse($notes as $i => $n)
                <tr>
                    <td>{{ $i+1 }}</td><td>{{ $n->student?->name }}</td><td>{{ $n->student?->nis }}</td><td>{{ $n->student?->class_name }}</td>
                    <td>{{ $n->student_note }}</td>
                    <td>
                        <form method="POST" action="{{ route('guidance.mentor.validate', $n->id) }}" class="mentor-auto-save-form">@csrf
                            <input type="hidden" name="approved" value="1">
                            <input type="text" name="mentor_note" placeholder="Catatan pembimbing" value="{{ (int) $n->mentor1_user_id === (int) auth()->id() ? ($n->mentor1_note ?? '') : ((int) $n->mentor2_user_id === (int) auth()->id() ? ($n->mentor2_note ?? '') : '') }}">
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">Belum ada siswa yang membuat catatan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const statusEl = document.getElementById('autosave-status');
    const forms = document.querySelectorAll('.mentor-auto-save-form');

    const setStatus = (text, color = '#6b7280') => {
        if (!statusEl) return;
        statusEl.textContent = text;
        statusEl.style.color = color;
    };

    forms.forEach((form) => {
        const input = form.querySelector('input[name="mentor_note"]');
        if (!input) return;

        let timer = null;

        input.addEventListener('input', () => {
            if (timer) {
                clearTimeout(timer);
            }

            setStatus('Menyimpan catatan...');

            timer = setTimeout(async () => {
                const payload = new FormData(form);
                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        body: payload,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    if (!res.ok) {
                        throw new Error('Gagal menyimpan');
                    }

                    setStatus('Catatan tersimpan otomatis.');
                } catch (err) {
                    setStatus('Gagal menyimpan catatan. Coba lagi.', '#b91c1c');
                }
            }, 500);
        });
    });
});
</script>
@endpush
