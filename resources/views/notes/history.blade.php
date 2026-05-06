@extends('layouts.app', ['title' => 'Riwayat Catatan'])

@section('content')
    <style>
        .notes-table-wrap {
            border: 1px solid #fed7aa;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }

        .notes-table {
            width: 100%;
            border-collapse: collapse;
        }

        .notes-table th,
        .notes-table td {
            border-bottom: 1px solid #ffedd5;
            padding: 10px 12px;
            vertical-align: middle;
        }

        .notes-table th {
            background: #fff7ed;
            color: #9a3412;
            text-align: left;
            font-weight: 700;
            white-space: nowrap;
        }

        .notes-table tbody tr:last-child td {
            border-bottom: 0;
        }
        .notes-filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }
        .notes-filters label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #9a3412;
            margin-bottom: 6px;
        }
        .notes-filters input,
        .notes-filters select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #fdba74;
            border-radius: 8px;
            background: #fff;
        }
        .notes-empty-filter {
            display: none;
            margin-top: 10px;
            padding: 10px 12px;
            border: 1px dashed #fdba74;
            border-radius: 10px;
            color: #9a3412;
            background: #fff7ed;
        }

        .detail-btn {
            border: 1px solid #fdba74;
            background: #fff;
            color: #9a3412;
            border-radius: 10px;
            padding: 8px 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .detail-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1200;
            padding: 14px;
        }

        .detail-modal.is-open {
            display: flex;
        }

        .detail-card {
            width: min(820px, 100%);
            max-height: 90vh;
            overflow: auto;
            border: 1px solid #fdba74;
            border-radius: 14px;
            background: #fff;
        }

        .detail-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 14px;
            border-bottom: 1px solid #fed7aa;
            background: #fff7ed;
            position: sticky;
            top: 0;
        }

        .detail-body {
            padding: 14px;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 8px;
            margin-bottom: 12px;
        }
        .meta-item {
            border: 1px solid #fed7aa;
            border-radius: 10px;
            background: #fffaf5;
            padding: 10px;
        }
        .meta-item strong {
            color: #9a3412;
        }
        .note-role-card {
            border: 1px solid #fed7aa;
            border-radius: 10px;
            background: #fff;
            padding: 10px 12px;
            margin-bottom: 10px;
        }
        .note-role-title {
            margin: 0 0 8px;
            font-weight: 700;
            color: #9a3412;
        }
        .note-points {
            margin: 0;
            padding-left: 18px;
        }
        .note-points li {
            margin-bottom: 4px;
        }
        .note-empty {
            color: #64748b;
            font-style: italic;
            margin: 0;
        }
    </style>

    <div class="card">
        <h3 class="mt-0">Riwayat Catatan Pembimbing Sekolah</h3>

        @if ($notes->isEmpty())
            <p>Belum ada catatan validasi.</p>
        @else
            <div class="notes-filters">
                <div>
                    <label for="notes-filter-date">Tanggal</label>
                    <input type="text" id="notes-filter-date" placeholder="Contoh: 2026-04-20">
                </div>
                <div>
                    <label for="notes-filter-category">Kategori</label>
                    <select id="notes-filter-category">
                        <option value="">Semua</option>
                        <option value="absensi / laporan harian">Absensi / Laporan Harian</option>
                        <option value="pengajuan izin/sakit">Pengajuan Izin/Sakit</option>
                    </select>
                </div>
                <div>
                    <label for="notes-filter-status">Status</label>
                    <select id="notes-filter-status">
                        <option value="">Semua</option>
                        <option value="approved">approved</option>
                        <option value="rejected">rejected</option>
                        <option value="pending">pending</option>
                    </select>
                </div>
                <div>
                    <label for="notes-filter-role-note">Cari Isi Catatan</label>
                    <input type="text" id="notes-filter-role-note" placeholder="Ketik isi catatan...">
                </div>
            </div>
            <div class="notes-table-wrap">
                <table class="notes-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Jumlah Catatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($notes as $idx => $item)
                            @php
                                $notesText = collect($item['notes'] ?? [])
                                    ->pluck('value')
                                    ->filter(fn ($value) => filled($value) && (string) $value !== '-')
                                    ->implode(' | ');
                            @endphp
                            <tr>
                                <td
                                    data-filter-date="{{ strtolower((string) $item['date']) }}"
                                    data-filter-type="{{ strtolower((string) $item['type']) }}"
                                    data-filter-status="{{ strtolower((string) $item['status']) }}"
                                    data-filter-notes="{{ strtolower((string) $notesText) }}"
                                >{{ $item['date'] }}</td>
                                <td>{{ $item['type'] }}</td>
                                <td>{{ $item['status'] }}</td>
                                <td>{{ $item['notes_count'] ?? count(array_filter($item['notes'] ?? [], fn ($note) => filled($note['value'] ?? null) && ($note['value'] ?? '-') !== '-')) }}</td>
                                <td>
                                    <button type="button" class="detail-btn" data-open-modal="notes-detail-{{ $idx }}">Detail</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="notes-empty-filter" class="notes-empty-filter">Data tidak ditemukan untuk filter yang dipilih.</div>

            @foreach ($notes as $idx => $item)
                <div class="detail-modal" id="notes-detail-{{ $idx }}" aria-hidden="true">
                    <div class="detail-card">
                        <div class="detail-head">
                            <h4 style="margin:0; color:#9a3412;">Detail Catatan</h4>
                            <button type="button" class="detail-btn" data-close-modal="notes-detail-{{ $idx }}">Close</button>
                        </div>
                        <div class="detail-body">
                            <div class="meta-grid">
                                <div class="meta-item"><strong>Tanggal:</strong><br>{{ $item['date'] }}</div>
                                <div class="meta-item"><strong>Kategori:</strong><br>{{ $item['type'] }}</div>
                                <div class="meta-item"><strong>Status:</strong><br>{{ $item['status'] }}</div>
                            </div>
                            <p style="margin:0 0 8px;"><strong>Catatan:</strong></p>
                            @foreach ($item['notes'] as $note)
                                @php
                                    $raw = trim((string) ($note['value'] ?? ''));
                                    $parts = collect(explode('|', $raw))
                                        ->map(fn ($value) => trim((string) $value))
                                        ->filter(fn ($value) => $value !== '' && $value !== '-')
                                        ->values();
                                @endphp
                                <div class="note-role-card">
                                    <p class="note-role-title">{{ $note['role'] }}</p>
                                    @if ($parts->isEmpty())
                                        <p class="note-empty">Belum ada catatan.</p>
                                    @else
                                        <ul class="note-points">
                                            @foreach ($parts as $point)
                                                <li>{{ $point }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <script>
        (function () {
            const dateFilterEl = document.getElementById('notes-filter-date');
            const categoryFilterEl = document.getElementById('notes-filter-category');
            const statusFilterEl = document.getElementById('notes-filter-status');
            const noteFilterEl = document.getElementById('notes-filter-role-note');
            const tableRows = document.querySelectorAll('.notes-table tbody tr');
            const notesEmptyFilterEl = document.getElementById('notes-empty-filter');
            const openButtons = document.querySelectorAll('[data-open-modal]');
            const closeButtons = document.querySelectorAll('[data-close-modal]');

            function openModal(id) {
                const modal = document.getElementById(id);
                if (!modal) return;
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
            }

            function closeModal(id) {
                const modal = document.getElementById(id);
                if (!modal) return;
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            }

            openButtons.forEach((button) => {
                button.addEventListener('click', () => openModal(button.dataset.openModal));
            });

            closeButtons.forEach((button) => {
                button.addEventListener('click', () => closeModal(button.dataset.closeModal));
            });

            function applyNotesFilters() {
                if (!tableRows.length) return;
                const dateKeyword = (dateFilterEl?.value || '').trim().toLowerCase();
                const category = (categoryFilterEl?.value || '').trim().toLowerCase();
                const status = (statusFilterEl?.value || '').trim().toLowerCase();
                const noteKeyword = (noteFilterEl?.value || '').trim().toLowerCase();
                let visible = 0;

                tableRows.forEach((row) => {
                    const sourceCell = row.querySelector('td[data-filter-date]');
                    if (!sourceCell) return;
                    const rowDate = (sourceCell.dataset.filterDate || '').toLowerCase();
                    const rowType = (sourceCell.dataset.filterType || '').toLowerCase();
                    const rowStatus = (sourceCell.dataset.filterStatus || '').toLowerCase();
                    const rowNotes = (sourceCell.dataset.filterNotes || '').toLowerCase();

                    const matchDate = dateKeyword === '' || rowDate.includes(dateKeyword);
                    const matchCategory = category === '' || rowType === category;
                    const matchStatus = status === '' || rowStatus === status;
                    const matchNote = noteKeyword === '' || rowNotes.includes(noteKeyword);
                    const show = matchDate && matchCategory && matchStatus && matchNote;

                    row.style.display = show ? '' : 'none';
                    if (show) visible += 1;
                });

                if (notesEmptyFilterEl) {
                    notesEmptyFilterEl.style.display = visible > 0 ? 'none' : 'block';
                }
            }

            [dateFilterEl, categoryFilterEl, statusFilterEl, noteFilterEl].forEach((el) => {
                if (!el) return;
                el.addEventListener('input', applyNotesFilters);
                el.addEventListener('change', applyNotesFilters);
            });

            applyNotesFilters();
        })();
    </script>
@endsection

