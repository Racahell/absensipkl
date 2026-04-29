@extends('layouts.app', ['title' => $title])

@section('content')
    <div class="card mb-14">
        <h3 class="mt-0 text-primary">Backup & Restore Database</h3>
        @if(session('success'))<div class="alert alert-success mb-10">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-error mb-10">{{ session('error') }}</div>@endif

        <div class="flex gap-8 wrap mb-10">
            <a href="{{ route('fitur.backup-restore', ['tab' => 'backup']) }}" style="text-decoration:none; padding:8px 12px; border-radius:999px; border:1px solid #fdba74; {{ ($tab ?? 'backup') === 'backup' ? 'background:#ea580c;color:#fff;border-color:#ea580c;' : 'background:#fff7ed;color:#9a3412;' }}">
                Backup SQL
            </a>
            <a href="{{ route('fitur.backup-restore', ['tab' => 'restore']) }}" style="text-decoration:none; padding:8px 12px; border-radius:999px; border:1px solid #fdba74; {{ ($tab ?? '') === 'restore' ? 'background:#ea580c;color:#fff;border-color:#ea580c;' : 'background:#fff7ed;color:#9a3412;' }}">
                Restore Database
            </a>
            <a href="{{ route('fitur.backup-restore', ['tab' => 'delete']) }}" style="text-decoration:none; padding:8px 12px; border-radius:999px; border:1px solid #fdba74; {{ ($tab ?? '') === 'delete' ? 'background:#ea580c;color:#fff;border-color:#ea580c;' : 'background:#fff7ed;color:#9a3412;' }}">
                Delete Isi Table
            </a>
        </div>

        @if (($tab ?? 'backup') === 'backup')
            <form method="POST" action="{{ route('backups.create') }}" class="grid gap-8" style="max-width:560px;">
                @csrf
                <label for="backup_scope">Mode backup</label>
                <select id="backup_scope" name="scope" required>
                    <option value="single">1 Table</option>
                    <option value="all">Semua Table</option>
                </select>
                <label for="backup_table_name">Pilih tabel untuk backup</label>
                <select id="backup_table_name" name="table_name">
                    <option value="">-- Pilih Tabel --</option>
                    @foreach(($tables ?? []) as $table)
                        <option value="{{ $table }}">{{ $table }}</option>
                    @endforeach
                </select>
                <small>Jika mode "Semua Table", pilihan tabel boleh diabaikan.</small>
                <button class="logout-btn w-fit" type="submit">Buat Backup (.sql)</button>
            </form>
        @elseif (($tab ?? '') === 'restore')
            <form method="POST" action="{{ route('backups.restore-upload') }}" enctype="multipart/form-data" class="grid gap-8" style="max-width:560px;">
                @csrf
                <label for="restore_scope">Mode restore</label>
                <select id="restore_scope" name="scope" required>
                    <option value="single">1 Table</option>
                    <option value="all">Semua Table</option>
                </select>
                <label for="restore_table_name">Pilih tabel tujuan restore</label>
                <select id="restore_table_name" name="table_name">
                    <option value="">-- Pilih Tabel --</option>
                    @foreach(($tables ?? []) as $table)
                        <option value="{{ $table }}">{{ $table }}</option>
                    @endforeach
                </select>
                <small>Jika mode "Semua Table", pilihan tabel boleh diabaikan.</small>
                <label for="sql_file">Upload file SQL untuk restore tabel</label>
                <input id="sql_file" name="sql_file" type="file" accept=".sql,.txt" required>
                <button class="logout-btn w-fit" type="submit">Restore Dari File SQL</button>
            </form>
        @else
            <div class="alert alert-error mb-10">
                Mode 1 Table: ketik <strong>HAPUS DATA TABEL</strong>. Mode Semua Table: ketik <strong>HAPUS SEMUA DATA</strong>.
            </div>
            <form method="POST" action="{{ route('backups.wipe') }}" class="grid gap-8" style="max-width:560px;">
                @csrf
                <label for="delete_scope">Mode hapus</label>
                <select id="delete_scope" name="scope" required>
                    <option value="single">1 Table</option>
                    <option value="all">Semua Table</option>
                </select>
                <label for="delete_table_name">Pilih tabel yang akan dihapus isinya</label>
                <select id="delete_table_name" name="table_name">
                    <option value="">-- Pilih Tabel --</option>
                    @foreach(($tables ?? []) as $table)
                        <option value="{{ $table }}">{{ $table }}</option>
                    @endforeach
                </select>
                <small>Jika mode "Semua Table", pilihan tabel boleh diabaikan.</small>
                <input name="confirm_text" placeholder="HAPUS DATA TABEL atau HAPUS SEMUA DATA" required>
                <button type="submit" class="btn-danger w-fit">
                    Delete Data
                </button>
            </form>
        @endif
    </div>

    <div class="card">
        <h3 class="mt-0 text-primary">Riwayat Backup</h3>
        <div class="table-wrap">
            <table class="w-full">
                <thead>
                    <tr>
                        <th>Nama File</th>
                        <th>Tipe</th>
                        <th>Dibuat Oleh</th>
                        <th>Restore Terakhir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->type }}</td>
                            <td>{{ $item->creator?->name ?? '-' }}</td>
                            <td>{{ $item->restored_at ? $item->restored_at.' oleh '.($item->restorer?->name ?? '-') : '-' }}</td>
                            <td>
                                <a href="{{ route('backups.download', $item) }}" class="btn btn-ghost" style="display:inline-block; text-decoration:none;">Download</a>
                                <form method="POST" action="{{ route('backups.restore', $item) }}" style="display:inline;" class="js-restore-backup-form">
                                    @csrf
                                    <button type="submit">Restore</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align:center;">Belum ada backup.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-10">{{ $backups->links() }}</div>
    </div>
    <script>
        (function () {
            document.querySelectorAll('.js-restore-backup-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const isEn = (window.localStorage.getItem('ui_lang') || 'id') === 'en';
                    const confirmed = await window.AppDialog.confirm(
                        isEn ? 'Restore this backup to database?' : 'Restore backup ini ke database?'
                    );
                    if (!confirmed) return;
                    form.submit();
                });
            });
        })();
    </script>
@endsection
