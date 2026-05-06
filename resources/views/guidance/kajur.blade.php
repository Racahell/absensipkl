@extends('layouts.app', ['title' => $title])

@section('content')
<div class="card">
    <h3 class="mt-0">Monitoring Catatan Bimbingan Kajur</h3>
    @if(session('success'))<div class="alert alert-success mb-10">{{ session('success') }}</div>@endif
    <div id="kajur-autosave-status" class="mb-10" style="font-size:12px; color:#6b7280;"></div>
    <div class="table-wrap">
        <table class="w-full">
            <thead><tr><th>No</th><th>Nama</th><th>NIS</th><th>Kelas</th><th>Catatan Siswa</th><th>Di approve</th><th>Catatan Pembimbing 1</th><th>Catatan Pembimbing 2</th><th>Catatan Kajur</th></tr></thead>
            <tbody>
            @forelse($notes as $i => $n)
                <tr>
                    <td>{{ $i+1 }}</td><td>{{ $n->student?->name }}</td><td>{{ $n->student?->nis }}</td><td>{{ $n->student?->class_name }}</td>
                    <td>{{ $n->student_note }}</td>
                    <td>{{ $n->mentor1_status === 'approved' || $n->mentor2_status === 'approved' ? 'Ya' : 'Belum' }}</td>
                    <td>{{ $n->mentor1_note ?: '-' }}</td>
                    <td>{{ $n->mentor2_note ?: '-' }}</td>
                    <td>
                        <form method="POST" action="{{ route('guidance.kajur.note', $n->id) }}" class="kajur-auto-save-form">@csrf
                            <input type="text" name="kajur_note" value="{{ $n->kajur_note }}" placeholder="Catatan Kajur">
                            <button type="submit">Simpan</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">Belum ada data.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    (function () {
        const statusEl = document.getElementById('kajur-autosave-status');
        const forms = Array.from(document.querySelectorAll('.kajur-auto-save-form'));
        if (forms.length === 0) return;

        const setStatus = (text, color = '#6b7280') => {
            if (!statusEl) return;
            statusEl.textContent = text;
            statusEl.style.color = color;
        };

        forms.forEach((form) => {
            const input = form.querySelector('[name="kajur_note"]');
            if (!input) return;

            let timer = null;
            input.addEventListener('input', () => {
                if (timer) clearTimeout(timer);
                setStatus('Menyimpan catatan...');

                timer = window.setTimeout(async () => {
                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) throw new Error(String(response.status || 'save_failed'));
                        setStatus('Catatan kajur tersimpan otomatis.');
                    } catch (e) {
                        const code = e && e.message ? ` (HTTP ${e.message})` : '';
                        setStatus(`Gagal menyimpan catatan kajur. Coba lagi.${code}`, '#b91c1c');
                    }
                }, 500);
            });
        });
    })();
</script>
@endsection
