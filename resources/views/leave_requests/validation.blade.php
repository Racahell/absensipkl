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
    $normalizeStatus = static function (?string $status): string {
        $raw = strtolower(trim((string) $status));
        return match (true) {
            $raw === '', $raw === '-' => 'awaiting',
            $raw === 'awaiting',
            str_starts_with($raw, 'pending') => 'awaiting',
            $raw === 'izin_approved',
            $raw === 'sakit_approved',
            $raw === 'approved_final',
            str_starts_with($raw, 'approved'),
            str_starts_with($raw, 'reviewed_') => 'approved',
            $raw === 'alpha',
            $raw === 'rejected',
            str_starts_with($raw, 'reject') => 'rejected',
            default => $raw,
        };
    };
@endphp

@extends('layouts.app', ['title' => 'Validasi Pengajuan '.$currentRoleLabel])

@section('content')
    <style>
        .validation-table-wrap {
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
            background: var(--surface);
        }
        .validation-filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }
        .validation-filters .filter-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .validation-filters label {
            font-size: 12px;
            font-weight: 700;
            color: var(--accent-text);
        }
        .validation-filters input,
        .validation-filters select {
            padding: 8px 10px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--surface);
        }
        .validation-filters .filter-reset {
            align-self: end;
            height: 38px;
        }
        .filter-empty-state {
            display: none;
            margin-top: 10px;
            padding: 10px 12px;
            border: 1px dashed var(--line);
            border-radius: 10px;
            color: var(--accent-text);
            background: var(--accent-soft);
        }

        .validation-table {
            width: 100%;
            border-collapse: collapse;
        }

        .validation-table th,
        .validation-table td {
            border-bottom: 1px solid var(--line);
            padding: 10px 12px;
            vertical-align: middle;
        }

        .validation-table th {
            background: var(--accent-soft);
            color: var(--accent-text);
            text-align: left;
            font-weight: 700;
            white-space: nowrap;
        }

        .validation-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .validation-table .name-cell strong {
            color: var(--accent-text);
        }

        .detail-btn {
            border: 1px solid var(--line);
            background: var(--surface);
            color: var(--accent-text);
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
            width: min(980px, 100%);
            max-height: 92vh;
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
            position: sticky;
            top: 0;
            background: #fff7ed;
            z-index: 2;
        }

        .detail-body {
            padding: 14px;
            display: grid;
            gap: 12px;
        }

        .detail-info p {
            margin: 0 0 8px;
        }

        .validation-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            align-items: stretch;
        }

        .validation-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-height: 100%;
            border: 1px solid #fed7aa;
            border-radius: 10px;
            padding: 10px;
            background: #fffaf5;
        }

        .validation-form textarea,
        .validation-form select {
            margin-bottom: 0 !important;
        }

        .validation-form .action-btn {
            align-self: flex-start;
            margin-top: auto;
            min-width: 110px;
        }

        .evidence-photo {
            width: 100%;
            max-width: 360px;
            border: 1px solid #fdba74;
            border-radius: 10px;
            display: block;
            background: #fff;
            margin-top: 6px;
            cursor: zoom-in;
        }

        .evidence-viewer {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1300;
            background: rgba(15, 23, 42, 0.75);
            padding: 20px;
        }

        .evidence-viewer.is-open {
            display: flex;
        }

        .evidence-viewer-card {
            width: min(1000px, 100%);
            height: min(88vh, 820px);
            background: #fff;
            border: 1px solid #fdba74;
            border-radius: 12px;
            overflow: hidden;
            display: grid;
            grid-template-rows: auto 1fr;
        }

        .evidence-viewer-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            border-bottom: 1px solid #fed7aa;
            background: #fff7ed;
        }

        .evidence-viewer-stage {
            position: relative;
            overflow: hidden;
            background: #111827;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: default;
        }

        .evidence-viewer-image {
            max-width: 100%;
            max-height: 100%;
            transform-origin: center center;
            transition: transform .05s linear;
            user-select: none;
            pointer-events: auto;
            cursor: default;
        }

        @media (max-width: 900px) {
            .validation-table {
                min-width: 720px;
            }

            .validation-actions {
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
        <h3 class="mt-0">Daftar Pengajuan Menunggu Validasi</h3>

        @if ($items->isEmpty())
            <p>Belum ada pengajuan menunggu validasi.</p>
        @else
            <div class="validation-filters">
                <div class="filter-field">
                    <label for="filter-keyword">Cari siswa / NIS</label>
                    <input type="text" id="filter-keyword" placeholder="Ketik nama atau NIS">
                </div>
                <div class="filter-field">
                    <label for="filter-type">Jenis</label>
                    <select id="filter-type">
                        <option value="">Semua</option>
                        <option value="izin">IZIN</option>
                        <option value="sakit">SAKIT</option>
                    </select>
                </div>
                <div class="filter-field">
                    <label for="filter-status">Status</label>
                    <select id="filter-status">
                        <option value="">Semua</option>
                        <option value="awaiting">awaiting</option>
                        <option value="approved">approved</option>
                        <option value="rejected">rejected</option>
                    </select>
                </div>
                <div class="filter-field">
                    <label for="filter-date">Tanggal</label>
                    <input type="date" id="filter-date">
                </div>
                <button type="button" id="filter-reset" class="detail-btn filter-reset">Reset Filter</button>
            </div>
            <div class="validation-table-wrap">
                <table class="validation-table">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>NIS</th>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr
                                data-filter-name="{{ strtolower((string) $item->user->name) }}"
                                data-filter-nis="{{ strtolower((string) $item->user->nis) }}"
                                data-filter-date="{{ $item->request_date->format('Y-m-d') }}"
                                data-filter-type="{{ strtolower((string) $item->type) }}"
                                data-filter-status="{{ strtolower($normalizeStatus($item->status)) }}"
                            >
                                <td class="name-cell"><strong>{{ $item->user->name }}</strong></td>
                                <td>{{ $item->user->nis }}</td>
                                <td>{{ $item->request_date->format('Y-m-d') }}</td>
                                <td>{{ strtoupper($item->type) }}</td>
                                <td>{{ $normalizeStatus($item->status) }}</td>
                                <td>
                                    <button type="button" class="detail-btn" data-open-modal="leave-detail-{{ $item->id }}">Detail</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="filter-empty-state" class="filter-empty-state">Data tidak ditemukan untuk filter yang dipilih.</div>

            @foreach ($items as $item)
                <div class="detail-modal" id="leave-detail-{{ $item->id }}" aria-hidden="true">
                    <div class="detail-card">
                        <div class="detail-head">
                            <h4 style="margin:0; color:#9a3412;">Detail Pengajuan</h4>
                            <button type="button" class="detail-btn" data-close-modal="leave-detail-{{ $item->id }}">Close</button>
                        </div>
                        <div class="detail-body">
                            <div class="detail-info">
                                <p><strong>{{ $item->user->name }}</strong> (NIS: {{ $item->user->nis }})</p>
                                <p>Tanggal: {{ $item->request_date->format('Y-m-d') }}</p>
                                <p>Jenis: {{ strtoupper($item->type) }}</p>
                                <p>Status: <strong>{{ $normalizeStatus($item->status) }}</strong></p>
                                <p>Alasan: {{ $item->reason }}</p>
                                @if ($item->evidence_path)
                                    <p>
                                        <strong>Bukti:</strong>
                                    </p>
                                    <img
                                        src="{{ asset('storage/'.$item->evidence_path) }}"
                                        alt="Bukti Pengajuan"
                                        class="evidence-photo"
                                        data-evidence-src="{{ asset('storage/'.$item->evidence_path) }}"
                                    >
                                @endif
                            </div>

                            <div class="validation-actions" style="grid-template-columns: 1fr;">
                                <form action="{{ route('validasi.pengajuan.approve', $item) }}" method="POST" class="validation-form">
                                    @csrf
                                    @php
                                        $normalizedStatus = $normalizeStatus($item->status);
                                        $canProcess = $normalizedStatus === 'awaiting';
                                    @endphp
                                    @if ($canProcess)
                                        <textarea name="note" rows="2" placeholder="Catatan validasi"></textarea>
                                        <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                            <button type="submit" class="btn-success action-btn">
                                                Approve
                                            </button>
                                            <button
                                                type="submit"
                                                class="btn-danger action-btn"
                                                formaction="{{ route('validasi.pengajuan.reject', $item) }}"
                                                data-reject-btn="1">
                                                Reject
                                            </button>
                                        </div>
                                    @else
                                        <small style="color:#6b7280;">Pengajuan ini sudah diproses.</small>
                                    @endif
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div id="evidence-viewer" class="evidence-viewer" aria-hidden="true">
        <div class="evidence-viewer-card">
            <div class="evidence-viewer-head">
                <strong style="color:#9a3412;">Preview Bukti</strong>
                <button type="button" id="close-evidence-viewer" class="detail-btn">Close</button>
            </div>
            <div class="evidence-viewer-stage" id="evidence-viewer-stage">
                <img id="evidence-viewer-image" class="evidence-viewer-image" alt="Evidence Preview">
            </div>
        </div>
    </div>

    <script>
        (function () {
            const openButtons = document.querySelectorAll('[data-open-modal]');
            const closeButtons = document.querySelectorAll('[data-close-modal]');
            const filterKeyword = document.getElementById('filter-keyword');
            const filterType = document.getElementById('filter-type');
            const filterStatus = document.getElementById('filter-status');
            const filterDate = document.getElementById('filter-date');
            const filterReset = document.getElementById('filter-reset');
            const tableRows = document.querySelectorAll('.validation-table tbody tr');
            const filterEmptyState = document.getElementById('filter-empty-state');
            const evidencePhotos = document.querySelectorAll('.evidence-photo');
            const evidenceViewer = document.getElementById('evidence-viewer');
            const evidenceViewerStage = document.getElementById('evidence-viewer-stage');
            const evidenceViewerImage = document.getElementById('evidence-viewer-image');
            const closeEvidenceViewerBtn = document.getElementById('close-evidence-viewer');
            let evidenceZoom = 1;
            let evidenceOffsetX = 0;
            let evidenceOffsetY = 0;
            let isDraggingEvidence = false;
            let dragStartX = 0;
            let dragStartY = 0;

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

            function applyRowFilters() {
                if (!tableRows.length) return;
                const keyword = (filterKeyword?.value || '').trim().toLowerCase();
                const type = (filterType?.value || '').trim().toLowerCase();
                const status = (filterStatus?.value || '').trim().toLowerCase();
                const date = (filterDate?.value || '').trim();
                let visibleCount = 0;

                tableRows.forEach((row) => {
                    const name = (row.dataset.filterName || '').toLowerCase();
                    const nis = (row.dataset.filterNis || '').toLowerCase();
                    const rowType = (row.dataset.filterType || '').toLowerCase();
                    const rowStatus = (row.dataset.filterStatus || '').toLowerCase();
                    const rowDate = row.dataset.filterDate || '';

                    const matchKeyword = keyword === '' || name.includes(keyword) || nis.includes(keyword);
                    const matchType = type === '' || rowType === type;
                    const matchStatus = status === '' || rowStatus === status;
                    const matchDate = date === '' || rowDate === date;
                    const show = matchKeyword && matchType && matchStatus && matchDate;

                    row.style.display = show ? '' : 'none';
                    if (show) visibleCount += 1;
                });

                if (filterEmptyState) {
                    filterEmptyState.style.display = visibleCount > 0 ? 'none' : 'block';
                }
            }

            [filterKeyword, filterType, filterStatus, filterDate].forEach((el) => {
                if (!el) return;
                el.addEventListener('input', applyRowFilters);
                el.addEventListener('change', applyRowFilters);
            });

            if (filterReset) {
                filterReset.addEventListener('click', () => {
                    if (filterKeyword) filterKeyword.value = '';
                    if (filterType) filterType.value = '';
                    if (filterStatus) filterStatus.value = '';
                    if (filterDate) filterDate.value = '';
                    applyRowFilters();
                });
            }

            applyRowFilters();
            document.querySelectorAll('[data-reject-btn]').forEach((btn) => {
                btn.addEventListener('click', (event) => {
                    const form = event.currentTarget.closest('form');
                    const note = form ? form.querySelector('textarea[name="note"]') : null;
                    if (note && !note.value.trim()) {
                        event.preventDefault();
                        window.AppDialog?.alert?.('Catatan reject wajib diisi.');
                    }
                });
            });

            function applyEvidenceZoom() {
                if (!evidenceViewerImage) return;
                evidenceViewerImage.style.transform = `translate(${evidenceOffsetX}px, ${evidenceOffsetY}px) scale(${evidenceZoom})`;
            }

            function openEvidenceViewer(src) {
                if (!evidenceViewer || !evidenceViewerImage || !src) return;
                evidenceViewerImage.src = src;
                evidenceZoom = 1;
                evidenceOffsetX = 0;
                evidenceOffsetY = 0;
                if (evidenceViewerStage) {
                    evidenceViewerStage.style.cursor = 'default';
                }
                evidenceViewerImage.style.cursor = 'default';
                applyEvidenceZoom();
                evidenceViewer.classList.add('is-open');
                evidenceViewer.setAttribute('aria-hidden', 'false');
            }

            function closeEvidenceViewer() {
                if (!evidenceViewer || !evidenceViewerImage) return;
                evidenceViewer.classList.remove('is-open');
                evidenceViewer.setAttribute('aria-hidden', 'true');
                evidenceViewerImage.src = '';
                evidenceZoom = 1;
                evidenceOffsetX = 0;
                evidenceOffsetY = 0;
                isDraggingEvidence = false;
                applyEvidenceZoom();
            }

            evidencePhotos.forEach((photo) => {
                photo.addEventListener('click', () => {
                    openEvidenceViewer(photo.dataset.evidenceSrc || photo.src);
                });
            });

            if (closeEvidenceViewerBtn) {
                closeEvidenceViewerBtn.addEventListener('click', closeEvidenceViewer);
            }

            if (evidenceViewer) {
                evidenceViewer.addEventListener('click', (event) => {
                    if (event.target === evidenceViewer) {
                        closeEvidenceViewer();
                    }
                });
            }

            if (evidenceViewerImage) {
                evidenceViewerImage.addEventListener('wheel', (event) => {
                    event.preventDefault();
                    const previousZoom = evidenceZoom;
                    evidenceZoom += event.deltaY < 0 ? 0.15 : -0.15;
                    evidenceZoom = Math.max(1, Math.min(5, evidenceZoom));
                    if (evidenceZoom === 1) {
                        evidenceOffsetX = 0;
                        evidenceOffsetY = 0;
                        if (evidenceViewerStage) {
                            evidenceViewerStage.style.cursor = 'default';
                        }
                        evidenceViewerImage.style.cursor = 'default';
                    } else if (previousZoom !== evidenceZoom) {
                        const scaleRatio = evidenceZoom / previousZoom;
                        evidenceOffsetX *= scaleRatio;
                        evidenceOffsetY *= scaleRatio;
                        if (evidenceViewerStage) {
                            evidenceViewerStage.style.cursor = 'grab';
                        }
                        evidenceViewerImage.style.cursor = 'grab';
                    }
                    applyEvidenceZoom();
                }, { passive: false });
            }

            if (evidenceViewerStage && evidenceViewerImage) {
                evidenceViewerStage.addEventListener('mousedown', (event) => {
                    if (evidenceZoom <= 1) return;
                    isDraggingEvidence = true;
                    dragStartX = event.clientX - evidenceOffsetX;
                    dragStartY = event.clientY - evidenceOffsetY;
                    evidenceViewerStage.style.cursor = 'grabbing';
                    evidenceViewerImage.style.cursor = 'grabbing';
                });

                window.addEventListener('mousemove', (event) => {
                    if (!isDraggingEvidence) return;
                    evidenceOffsetX = event.clientX - dragStartX;
                    evidenceOffsetY = event.clientY - dragStartY;
                    applyEvidenceZoom();
                });

                window.addEventListener('mouseup', () => {
                    if (!isDraggingEvidence) return;
                    isDraggingEvidence = false;
                    evidenceViewerStage.style.cursor = evidenceZoom > 1 ? 'grab' : 'default';
                    evidenceViewerImage.style.cursor = evidenceZoom > 1 ? 'grab' : 'default';
                });
            }

            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && evidenceViewer && evidenceViewer.classList.contains('is-open')) {
                    closeEvidenceViewer();
                }
            });
        })();
    </script>
@endsection


