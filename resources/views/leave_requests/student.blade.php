@extends('layouts.app', ['title' => 'Pengajuan Izin / Sakit'])

@section('content')
    @php
        $formatStatus = static function (?string $status): string {
            $raw = strtolower(trim((string) $status));
            return match (true) {
                $raw === '', $raw === '-' => '-',
                $raw === 'awaiting',
                str_starts_with($raw, 'pending') => 'awaiting',
                $raw === 'approved_final',
                str_starts_with($raw, 'approved'),
                str_starts_with($raw, 'reviewed_') => 'approved',
                $raw === 'alpha',
                $raw === 'rejected',
                str_starts_with($raw, 'reject') => 'rejected',
                default => 'awaiting',
            };
        };
    @endphp
    <style>
        .history-table-wrap {
            border: 1px solid #fed7aa;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th,
        .history-table td {
            border-bottom: 1px solid #ffedd5;
            padding: 9px 10px;
            text-align: left;
            vertical-align: middle;
        }

        .history-table th {
            background: #fff7ed;
            color: #9a3412;
            font-weight: 700;
            white-space: nowrap;
        }

        .history-table tbody tr:last-child td {
            border-bottom: 0;
        }
        .history-filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }
        .history-filters label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #9a3412;
            margin-bottom: 6px;
        }
        .history-filters input,
        .history-filters select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #fdba74;
            border-radius: 8px;
            background: #fff;
        }
        .history-empty-filter {
            display: none;
            margin-top: 10px;
            padding: 10px 12px;
            border: 1px dashed #fdba74;
            border-radius: 10px;
            color: #9a3412;
            background: #fff7ed;
        }

        .history-detail-btn {
            border: 1px solid #fdba74;
            background: #fff;
            color: #9a3412;
            border-radius: 10px;
            padding: 7px 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .history-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1200;
            padding: 14px;
        }

        .history-modal.is-open {
            display: flex;
        }

        .history-modal-card {
            width: min(840px, 100%);
            max-height: 92vh;
            overflow: auto;
            border: 1px solid #fdba74;
            border-radius: 14px;
            background: #fff;
        }

        .history-modal-head {
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

        .history-modal-body {
            padding: 14px;
        }

        .leave-action-row {
            display: flex;
            gap: 8px;
            flex-wrap: nowrap;
            align-items: center;
            margin: 6px 0 12px;
        }

        .leave-action-row button {
            white-space: nowrap;
        }
        .evidence-dropzone {
            border: 2px dashed #fdba74;
            border-radius: 12px;
            background: #fff7ed;
            padding: 14px;
            text-align: center;
            color: #9a3412;
            margin: 6px 0 10px;
            cursor: pointer;
            transition: all .2s ease;
        }
        .evidence-dropzone.is-dragover {
            border-color: #ea580c;
            background: #ffedd5;
        }
        .evidence-dropzone strong {
            display: block;
            margin-bottom: 4px;
        }

        .evidence-thumb {
            width: 100%;
            max-width: 320px;
            border: 1px solid #fdba74;
            border-radius: 10px;
            cursor: zoom-in;
            display: block;
            background: #fff;
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
            cursor: grab;
        }

        .evidence-viewer-image {
            max-width: 100%;
            max-height: 100%;
            transform-origin: center center;
            transition: transform .05s linear;
            user-select: none;
            pointer-events: auto;
            cursor: grab;
        }
    </style>

    @if (session('success'))
        <div class="card" style="border-color:#fdba74; margin-bottom: 16px; background:#fff7ed;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="card" style="border-color:#fecaca; margin-bottom: 16px; background:#fff1f2;">
            <strong>Terjadi kesalahan:</strong>
            <ul style="margin:8px 0 0 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="margin-bottom:16px;">
        <h3 style="margin-top:0;">Form Pengajuan</h3>
        <form action="{{ route('pengajuan.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label>Tanggal</label>
            <input type="date" name="request_date" style="width:100%; margin:6px 0 10px; padding:8px; border:1px solid #fdba74; border-radius:8px;" required>

            <label>Jenis</label>
            <select name="type" style="width:100%; margin:6px 0 10px; padding:8px; border:1px solid #fdba74; border-radius:8px;">
                <option value="izin">Izin</option>
                <option value="sakit">Sakit</option>
            </select>

            <label>Alasan</label>
            <textarea name="reason" rows="3" style="width:100%; margin:6px 0 10px; padding:8px; border:1px solid #fdba74; border-radius:8px;" required></textarea>

            <label>Bukti (wajib, foto)</label>
            <p style="margin:6px 0 8px; color:#9a3412;">Upload bukti foto. Bisa drag & drop atau pilih file.</p>
            <div id="leave-evidence-dropzone" class="evidence-dropzone">
                <strong>Drag & drop foto di sini</strong>
                <span>atau klik untuk pilih file</span>
            </div>
            <div id="leave-photo-preview-wrapper" style="display:none; margin:6px 0 10px;">
                <img
                    id="leave-photo-preview"
                    alt="Preview Bukti Pengajuan"
                    style="width:100%; max-width:360px; border:1px solid #fdba74; border-radius:10px; display:block;">
            </div>
            <p id="leave-file-info" style="margin:6px 0 8px; color:#9a3412;">Belum ada file dipilih.</p>
            <div class="leave-action-row">
                <button id="reset-leave-file-btn" type="button" style="display:none; padding:8px 10px; border:1px solid #ea580c; background:#fff; color:#9a3412; border-radius:8px;">
                    Hapus File
                </button>
                <button type="submit" style="padding:9px 12px; border:1px solid #ea580c; background:#ea580c; color:#fff; border-radius:8px;">
                    Kirim Pengajuan
                </button>
            </div>
            <input type="file" id="leave-evidence-file" name="evidence" accept="image/*" required style="display:none;" />
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Riwayat Pengajuan</h3>
        @if ($items->isEmpty())
            <p style="margin:0;">Belum ada pengajuan.</p>
        @else
            <div class="history-filters">
                <div>
                    <label for="history-filter-keyword">Cari Alasan</label>
                    <input type="text" id="history-filter-keyword" placeholder="Ketik alasan...">
                </div>
                <div>
                    <label for="history-filter-type">Jenis</label>
                    <select id="history-filter-type">
                        <option value="">Semua</option>
                        <option value="izin">izin</option>
                        <option value="sakit">sakit</option>
                    </select>
                </div>
                <div>
                    <label for="history-filter-status">Status</label>
                    <select id="history-filter-status">
                        <option value="">Semua</option>
                        <option value="awaiting">awaiting</option>
                        <option value="approved">approved</option>
                        <option value="rejected">rejected</option>
                    </select>
                </div>
                <div>
                    <label for="history-filter-date">Tanggal</label>
                    <input type="date" id="history-filter-date">
                </div>
            </div>
            <div class="history-table-wrap">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Status</th>
                            <th>Alasan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr
                                data-filter-date="{{ $item->request_date->format('Y-m-d') }}"
                                data-filter-type="{{ strtolower((string) $item->type) }}"
                                data-filter-status="{{ strtolower($formatStatus($item->status)) }}"
                                data-filter-reason="{{ strtolower((string) $item->reason) }}"
                            >
                                <td>{{ $item->request_date->format('Y-m-d') }}</td>
                                <td>{{ $item->type }}</td>
                                <td>{{ $formatStatus($item->status) }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($item->reason, 60) }}</td>
                                <td>
                                    <button
                                        type="button"
                                        class="history-detail-btn"
                                        data-open-modal="leave-history-{{ $item->id }}">
                                        Detail
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="history-empty-filter" class="history-empty-filter">Data tidak ditemukan untuk filter yang dipilih.</div>

            @foreach ($items as $item)
                @php
                    $notes = collect([
                        ['label' => 'Catatan Pembimbing Sekolah', 'value' => $item->pembimbing_note],
                        ['label' => 'Catatan Instruktur', 'value' => $item->instruktur_note],
                        ['label' => 'Catatan Kajur', 'value' => $item->kajur_note],
                    ])->filter(fn ($row) => filled($row['value']))->values();
                @endphp
                <div class="history-modal" id="leave-history-{{ $item->id }}" aria-hidden="true">
                    <div class="history-modal-card">
                        <div class="history-modal-head">
                            <h4 style="margin:0; color:#9a3412;">Detail Pengajuan</h4>
                            <button type="button" class="history-detail-btn" data-close-modal="leave-history-{{ $item->id }}">Close</button>
                        </div>
                        <div class="history-modal-body">
                            <p style="margin:0 0 8px;"><strong>Tanggal:</strong> {{ $item->request_date->format('Y-m-d') }}</p>
                            <p style="margin:0 0 8px;"><strong>Jenis:</strong> {{ $item->type }}</p>
                            <p style="margin:0 0 8px;"><strong>Status:</strong> {{ $formatStatus($item->status) }}</p>
                            <p style="margin:0 0 8px;"><strong>Alasan:</strong> {{ $item->reason }}</p>
                            @if ($item->evidence_path)
                                <p style="margin:0 0 10px;">
                                    <strong>Bukti:</strong>
                                </p>
                                <img
                                    src="{{ asset('storage/'.$item->evidence_path) }}"
                                    alt="Bukti Pengajuan"
                                    class="evidence-thumb"
                                    data-evidence-src="{{ asset('storage/'.$item->evidence_path) }}"
                                >
                            @endif

                            <div class="panel" style="border-color:#fed7aa; background:#fffaf5;">
                                <p style="margin:0 0 8px;"><strong>Catatan Validator</strong></p>
                                @if ($notes->isEmpty())
                                    <p style="margin:0;">Belum ada catatan validator.</p>
                                @else
                                    <ul style="margin:0 0 0 18px;">
                                        @foreach ($notes as $row)
                                            <li><strong>{{ $row['label'] }}:</strong> {{ $row['value'] }}</li>
                                        @endforeach
                                    </ul>
                                @endif
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
                <button type="button" id="close-evidence-viewer" class="history-detail-btn">Close</button>
            </div>
            <div class="evidence-viewer-stage" id="evidence-viewer-stage">
                <img id="evidence-viewer-image" class="evidence-viewer-image" alt="Evidence Preview">
            </div>
        </div>
    </div>

    <script>
        (function () {
            const keywordEl = document.getElementById('history-filter-keyword');
            const typeEl = document.getElementById('history-filter-type');
            const statusEl = document.getElementById('history-filter-status');
            const dateEl = document.getElementById('history-filter-date');
            const historyRows = document.querySelectorAll('.history-table tbody tr');
            const historyEmptyFilter = document.getElementById('history-empty-filter');
            const fileInput = document.getElementById('leave-evidence-file');
            const dropzone = document.getElementById('leave-evidence-dropzone');
            const resetBtn = document.getElementById('reset-leave-file-btn');
            const infoEl = document.getElementById('leave-file-info');
            const previewWrapperEl = document.getElementById('leave-photo-preview-wrapper');
            const previewEl = document.getElementById('leave-photo-preview');
            const formEl = document.querySelector('form[action="{{ route('pengajuan.store') }}"]');
            const openButtons = document.querySelectorAll('[data-open-modal]');
            const closeButtons = document.querySelectorAll('[data-close-modal]');
            const evidenceThumbs = document.querySelectorAll('.evidence-thumb');
            const evidenceViewer = document.getElementById('evidence-viewer');
            const evidenceViewerStage = document.getElementById('evidence-viewer-stage');
            const evidenceViewerImage = document.getElementById('evidence-viewer-image');
            const closeEvidenceViewerBtn = document.getElementById('close-evidence-viewer');
            let leavePreviewUrl = null;
            let evidenceZoom = 1;
            let evidenceOffsetX = 0;
            let evidenceOffsetY = 0;
            let isDraggingEvidence = false;
            let dragStartX = 0;
            let dragStartY = 0;

            function setSelectedFile(file) {
                if (!fileInput || !file) return;
                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;

                if (leavePreviewUrl) {
                    URL.revokeObjectURL(leavePreviewUrl);
                }
                leavePreviewUrl = URL.createObjectURL(file);
                previewEl.src = leavePreviewUrl;
                previewWrapperEl.style.display = 'block';
                resetBtn.style.display = 'inline-block';
                infoEl.textContent = `File dipilih: ${file.name}`;
                infoEl.style.color = '#166534';
            }

            function clearSelectedFile() {
                if (!fileInput) return;
                fileInput.value = '';
                previewWrapperEl.style.display = 'none';
                resetBtn.style.display = 'none';
                if (leavePreviewUrl) {
                    URL.revokeObjectURL(leavePreviewUrl);
                    leavePreviewUrl = null;
                }
                infoEl.textContent = 'Belum ada file dipilih.';
                infoEl.style.color = '#9a3412';
            }

            if (dropzone) {
                dropzone.addEventListener('click', () => fileInput?.click());
                dropzone.addEventListener('dragover', (event) => {
                    event.preventDefault();
                    dropzone.classList.add('is-dragover');
                });
                dropzone.addEventListener('dragleave', () => {
                    dropzone.classList.remove('is-dragover');
                });
                dropzone.addEventListener('drop', (event) => {
                    event.preventDefault();
                    dropzone.classList.remove('is-dragover');
                    const file = event.dataTransfer?.files?.[0];
                    if (!file) return;
                    if (!file.type.startsWith('image/')) {
                        infoEl.textContent = 'File harus berupa gambar.';
                        infoEl.style.color = '#b91c1c';
                        return;
                    }
                    setSelectedFile(file);
                });
            }

            if (fileInput) {
                fileInput.addEventListener('change', () => {
                    const file = fileInput.files?.[0];
                    if (!file) return;
                    setSelectedFile(file);
                });
            }

            if (resetBtn) {
                resetBtn.addEventListener('click', clearSelectedFile);
            }

            if (formEl) {
                formEl.addEventListener('submit', (event) => {
                    if (!fileInput?.files || fileInput.files.length === 0) {
                        event.preventDefault();
                        infoEl.textContent = 'Upload bukti foto terlebih dahulu.';
                        infoEl.style.color = '#b91c1c';
                    }
                });
            }

            function applyHistoryFilters() {
                if (!historyRows.length) return;
                const keyword = (keywordEl?.value || '').trim().toLowerCase();
                const type = (typeEl?.value || '').trim().toLowerCase();
                const status = (statusEl?.value || '').trim().toLowerCase();
                const date = (dateEl?.value || '').trim();
                let visible = 0;

                historyRows.forEach((row) => {
                    const rowDate = row.dataset.filterDate || '';
                    const rowType = (row.dataset.filterType || '').toLowerCase();
                    const rowStatus = (row.dataset.filterStatus || '').toLowerCase();
                    const rowReason = (row.dataset.filterReason || '').toLowerCase();

                    const matchKeyword = keyword === '' || rowReason.includes(keyword);
                    const matchType = type === '' || rowType === type;
                    const matchStatus = status === '' || rowStatus === status;
                    const matchDate = date === '' || rowDate === date;
                    const show = matchKeyword && matchType && matchStatus && matchDate;
                    row.style.display = show ? '' : 'none';
                    if (show) visible += 1;
                });

                if (historyEmptyFilter) {
                    historyEmptyFilter.style.display = visible > 0 ? 'none' : 'block';
                }
            }

            [keywordEl, typeEl, statusEl, dateEl].forEach((el) => {
                if (!el) return;
                el.addEventListener('input', applyHistoryFilters);
                el.addEventListener('change', applyHistoryFilters);
            });

            applyHistoryFilters();

            function openHistoryModal(id) {
                const modal = document.getElementById(id);
                if (!modal) return;
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
            }

            function closeHistoryModal(id) {
                const modal = document.getElementById(id);
                if (!modal) return;
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            }

            openButtons.forEach((button) => {
                button.addEventListener('click', () => openHistoryModal(button.dataset.openModal));
            });

            closeButtons.forEach((button) => {
                button.addEventListener('click', () => closeHistoryModal(button.dataset.closeModal));
            });

            function applyEvidenceZoom() {
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

            evidenceThumbs.forEach((thumb) => {
                thumb.addEventListener('click', () => {
                    openEvidenceViewer(thumb.dataset.evidenceSrc || thumb.src);
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

            window.addEventListener('beforeunload', () => {
                if (leavePreviewUrl) {
                    URL.revokeObjectURL(leavePreviewUrl);
                }
            });
        })();
    </script>
@endsection


