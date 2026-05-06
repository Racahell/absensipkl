@extends('layouts.app', ['title' => $title])

@section('content')
    @php
        $formatStatus = static function (?string $status): string {
            $raw = strtolower(trim((string) $status));
            return match (true) {
                $raw === '', $raw === '-' => '-',
                $raw === 'pending',
                $raw === 'pending_pembimbing',
                $raw === 'pending_instruktur',
                $raw === 'pending_kajur' => '-',
                $raw === 'hadir',
                $raw === 'approved_final',
                str_starts_with($raw, 'approved'),
                str_starts_with($raw, 'reviewed_') => 'approved',
                default => str_replace('_', ' ', $raw),
            };
        };
    @endphp
    <style>
        .weekly-toolbar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px;
            align-items: end;
            margin-bottom: 14px;
        }

        .weekly-toolbar button[type="submit"] {
            display: none !important;
        }

        .weekly-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .weekly-headline {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 16px;
            margin: 0 0 10px;
            color: #7c2d12;
            font-weight: 600;
        }

        .weekly-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1200;
            padding: 16px;
        }

        .weekly-modal-backdrop.show {
            display: flex;
        }

        .weekly-modal {
            width: 100%;
            max-width: 620px;
            background: #fff;
            border: 1px solid #fdba74;
            border-radius: 12px;
            padding: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .weekly-modal h4 {
            margin: 0 0 10px;
            color: #9a3412;
        }

        .weekly-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 10px;
        }

        .weekly-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .weekly-student-table {
            table-layout: fixed;
            width: 100%;
        }

        .weekly-student-table th,
        .weekly-student-table td {
            vertical-align: top;
        }

        .weekly-student-table .col-no { width: 44px; }
        .weekly-student-table .col-name { width: 130px; }
        .weekly-student-table .col-nis { width: 96px; }
        .weekly-student-table .col-class { width: 80px; }
        .weekly-student-table .col-time { width: 76px; }
        .weekly-student-table .col-absen { width: 72px; }
        .weekly-student-table .col-note { width: 170px; }
        .weekly-student-table .col-note-wide { width: 210px; }

        .weekly-student-table .text-cell {
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .weekly-student-table textarea[name="mentor_note"] {
            width: 100%;
            min-height: 70px;
            resize: vertical;
            box-sizing: border-box;
        }

        .weekly-student-table textarea[name="kajur_note"] {
            width: 100%;
            min-height: 70px;
            resize: vertical;
            box-sizing: border-box;
        }

        .weekly-grid.single {
            grid-template-columns: 1fr;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .kpi-card {
            border: 1px solid #fdba74;
            border-radius: 10px;
            padding: 10px;
            background: #fff7ed;
        }

        .kpi-card small {
            color: #9a3412;
        }

        .kpi-card strong {
            display: block;
            font-size: 22px;
            color: #7c2d12;
            margin-top: 4px;
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid #fdba74;
            background: #fff7ed;
            color: #9a3412;
        }

        .mini-pagination {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            margin-top: 10px;
        }
        .mini-pagination-info {
            margin-right: auto;
            color: #9a3412;
            font-size: 12px;
            font-weight: 600;
        }

        .mini-page-btn {
            border: 1px solid #fdba74;
            background: #fff;
            color: #9a3412;
            border-radius: 8px;
            padding: 5px 10px;
            min-width: 36px;
            cursor: pointer;
        }

        .mini-page-btn.active {
            background: #ea580c;
            color: #fff;
            border-color: #ea580c;
        }

        .mini-page-btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        @media (max-width: 980px) {
            .weekly-toolbar {
                grid-template-columns: 1fr 1fr;
            }

            .weekly-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .weekly-toolbar {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @if (session('success'))
        <div class="card alert mb-16">{{ session('success') }}</div>
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

    <div class="card mb-14">
        <h3 class="mt-0">Summary Report Mingguan</h3>
        @if (in_array($role, ['kajur', 'wali_kelas', 'instruktur'], true))
            <p class="text-primary" style="margin-top:-4px; margin-bottom:10px;">
                Jurusan: <strong>{{ $selectedDepartment !== '' ? $selectedDepartment : '-' }}</strong>
            </p>
            @if ($role === 'wali_kelas')
                <p class="text-primary" style="margin-top:-4px; margin-bottom:10px;">
                    Kelas: <strong>{{ $selectedClass !== '' ? $selectedClass : '-' }}</strong>
                </p>
            @endif
        @endif
        <form method="GET" action="{{ route('reports.weekly') }}" class="weekly-toolbar">
            <div>
                <label for="week_start">Tanggal</label>
                <input id="week_start" type="date" name="week_start" value="{{ $selectedDate ?? $weekStart->toDateString() }}">
            </div>
            @if (! in_array($role, ['kajur', 'wali_kelas', 'instruktur'], true))
                <div>
                    <label for="jurusan">Jurusan</label>
                    <select id="jurusan" name="jurusan">
                        <option value="">Semua Jurusan</option>
                        @foreach ($departmentOptions as $department)
                            <option value="{{ $department }}" {{ $selectedDepartment === $department ? 'selected' : '' }}>{{ $department }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="jurusan" value="{{ $selectedDepartment }}">
            @endif
            @if (! in_array($role, ['kajur', 'wali_kelas', 'instruktur'], true))
                <div>
                    <label for="kelas">Kelas</label>
                    <select id="kelas" name="kelas">
                        <option value="">Semua Kelas</option>
                        @foreach ($classOptions as $className)
                            <option value="{{ $className }}" {{ $selectedClass === $className ? 'selected' : '' }}>{{ $className }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="kelas" value="{{ $selectedClass }}">
            @endif
            <div>
                <label for="siswa">Siswa</label>
                <select id="siswa" name="siswa">
                    <option value="">Semua Siswa</option>
                    @foreach (($studentOptions ?? []) as $student)
                        <option value="{{ $student['id'] }}" {{ (string) ($selectedStudent ?? '') === (string) $student['id'] ? 'selected' : '' }}>
                            {{ $student['name'] }} ({{ $student['nis'] }}){{ ! empty($student['class_name']) ? ' - '.$student['class_name'] : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="weekly-actions">
                <a class="btn btn-ghost" href="{{ route('reports.weekly') }}">Reset</a>
            </div>
        </form>

        <div class="kpi-grid">
            <div class="kpi-card"><small>Hadir</small><strong>{{ $summary['hadir'] }}</strong></div>
            <div class="kpi-card"><small>Izin</small><strong>{{ $summary['izin'] }}</strong></div>
            <div class="kpi-card"><small>Sakit</small><strong>{{ $summary['sakit'] }}</strong></div>
            <div class="kpi-card"><small>Alpha</small><strong>{{ $summary['alpha'] }}</strong></div>
            <div class="kpi-card"><small>Pending</small><strong>{{ $summary['pending'] }}</strong></div>
            <div class="kpi-card"><small>Total</small><strong>{{ $summary['total'] }}</strong></div>
        </div>

        @php
            $canShowStudentList = true;
            $selectedStudentId = (string) ($selectedStudent ?? '');
            $displayStudents = collect($studentOptions ?? []);
            if ($selectedStudentId !== '') {
                $displayStudents = $displayStudents->where('id', (int) $selectedStudentId)->values();
            }
        @endphp

        @if ($canShowStudentList)
            <div class="panel mb-14">
                <h4 class="mt-0">
                    @if ($selectedStudentId !== '')
                        Daftar Siswa Terpilih
                    @elseif ($selectedDepartment !== '' && ! in_array($role, ['pembimbing_pkl', 'instruktur'], true))
                        Daftar Siswa (Jurusan {{ $selectedDepartment }})
                    @else
                        Daftar Siswa (Semua Siswa)
                    @endif
                </h4>
                <div style="overflow:auto;">
                    @if (in_array($role, ['pembimbing_pkl', 'instruktur'], true))
                        <div id="weekly-mentor-autosave-status" style="font-size:12px; color:#6b7280; margin-bottom:8px;"></div>
                        <table class="w-full weekly-student-table">
                            <colgroup>
                                <col class="col-no">
                                <col class="col-name">
                                <col class="col-nis">
                                <col class="col-class">
                                <col class="col-time">
                                <col class="col-absen">
                                <col class="col-note-wide">
                                <col class="col-note-wide">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>NIS</th>
                                    <th>Kelas</th>
                                    <th>Jam</th>
                                    <th>Absensi</th>
                                    <th>Catatan Siswa</th>
                                    <th>Catatan Pembimbing</th>
                                </tr>
                            </thead>
                            <tbody id="weekly-student-list-body">
                                @forelse (($guidanceRows ?? collect()) as $index => $row)
                                    @php
                                        $isMentor1 = (int) ($row->mentor1_user_id ?? 0) === (int) auth()->id();
                                        $isMentor2 = (int) ($row->mentor2_user_id ?? 0) === (int) auth()->id();
                                        $canValidate = $isMentor1 || $isMentor2 || $role === 'instruktur';
                                        $mentorNoteNow = $isMentor1 ? ($row->mentor1_note ?? '') : ($isMentor2 ? ($row->mentor2_note ?? '') : '');
                                        $isMentorApproved = $isMentor1
                                            ? (($row->mentor1_status ?? null) === 'approved')
                                            : (($isMentor2 ? (($row->mentor2_status ?? null) === 'approved') : false));
                                        $attendanceLabel = $isMentorApproved ? 'Hadir' : 'Alpha';
                                    @endphp
                                    <tr class="weekly-student-row">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $row->student?->name ?? '-' }}</td>
                                        <td>{{ $row->student?->nis ?? '-' }}</td>
                                        <td>{{ $row->student?->class_name ?? '-' }}</td>
                                        <td>{{ optional($row->student_submitted_at)->format('H:i:s') ?? '-' }}</td>
                                        <td>
                                            @if ($canValidate)
                                                <form method="POST" action="{{ route('guidance.mentor.validate', $row->id) }}" class="weekly-mentor-auto-save-form">
                                                    @csrf
                                                    <input type="hidden" name="approved" value="0">
                                                    <input type="checkbox" name="approved" value="1" {{ $isMentorApproved ? 'checked' : '' }}>
                                                    <span style="margin-left:6px;">{{ $attendanceLabel }}</span>
                                                </form>
                                            @else
                                                {{ $attendanceLabel }}
                                            @endif
                                        </td>
                                        <td class="text-cell">{{ $row->student_note }}</td>
                                        <td>
                                            @if ($canValidate)
                                                <form method="POST" action="{{ route('guidance.mentor.validate', $row->id) }}" class="weekly-mentor-auto-save-form">
                                                    @csrf
                                                    <input type="hidden" name="approved" value="{{ $isMentorApproved ? '1' : '0' }}">
                                                    <textarea name="mentor_note" placeholder="Catatan pembimbing">{{ $mentorNoteNow }}</textarea>
                                                </form>
                                            @else
                                                {{ $mentorNoteNow !== '' ? $mentorNoteNow : '-' }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8">Belum ada siswa yang membuat catatan pada minggu/periode ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @elseif ($role === 'kajur')
                        <div id="weekly-kajur-autosave-status" style="font-size:12px; color:#6b7280; margin-bottom:8px;"></div>
                        @php
                            $mentor1HeaderName = collect($guidanceRows ?? [])->map(fn ($row) => $row->mentor1?->name)->filter()->first();
                            $mentor2HeaderName = collect($guidanceRows ?? [])->map(fn ($row) => $row->mentor2?->name)->filter()->first();
                        @endphp
                        <table class="w-full weekly-student-table">
                            <colgroup>
                                <col class="col-no">
                                <col class="col-name">
                                <col class="col-nis">
                                <col class="col-class">
                                <col class="col-time">
                                <col class="col-note">
                                <col class="col-absen">
                                <col class="col-note">
                                <col class="col-note">
                                <col class="col-note-wide">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>NIS</th>
                                    <th>Kelas</th>
                                    <th>Jam</th>
                                    <th>Catatan Siswa</th>
                                    <th>Absensi</th>
                                    <th>{{ $mentor1HeaderName ? 'Catatan Pembimbing 1 - '.$mentor1HeaderName : 'Catatan Pembimbing 1' }}</th>
                                    <th>{{ $mentor2HeaderName ? 'Catatan Pembimbing 2 - '.$mentor2HeaderName : 'Catatan Pembimbing 2' }}</th>
                                    <th>Catatan Kajur</th>
                                </tr>
                            </thead>
                            <tbody id="weekly-student-list-body">
                                @forelse (($guidanceRows ?? collect()) as $index => $row)
                                    @php
                                        $isChecked = ($row->final_attendance_status === 'hadir');
                                    @endphp
                                    <tr class="weekly-student-row">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $row->student?->name ?? '-' }}</td>
                                        <td>{{ $row->student?->nis ?? '-' }}</td>
                                        <td>{{ $row->student?->class_name ?? '-' }}</td>
                                        <td>{{ optional($row->student_submitted_at)->format('H:i:s') ?? '-' }}</td>
                                        <td class="text-cell">{{ $row->student_note ?: '-' }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('guidance.kajur.note', $row->id) }}" class="weekly-kajur-auto-save-form flex items-center gap-8">
                                                @csrf
                                                <input type="hidden" name="approved" value="0">
                                                <input type="checkbox" name="approved" value="1" {{ $isChecked ? 'checked' : '' }}>
                                            </form>
                                        </td>
                                        <td class="text-cell">{{ $row->mentor1_note ?: '-' }}</td>
                                        <td class="text-cell">{{ $row->mentor2_note ?: '-' }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('guidance.kajur.note', $row->id) }}" class="weekly-kajur-auto-save-form">
                                                @csrf
                                                <textarea name="kajur_note" placeholder="Catatan Kajur">{{ $row->kajur_note ?? '' }}</textarea>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10">Belum ada siswa yang membuat catatan pada minggu/periode ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @else
                        <table class="w-full weekly-student-table">
                            <thead>
                                <tr>
                                    <th style="width:70px;">No</th>
                                    <th>Nama</th>
                                    <th>NIS</th>
                                    <th>Kelas</th>
                                    <th>Jurusan</th>
                                </tr>
                            </thead>
                            <tbody id="weekly-student-list-body">
                                @forelse ($displayStudents as $index => $student)
                                    <tr class="weekly-student-row">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $student['name'] ?? '-' }}</td>
                                        <td>{{ $student['nis'] ?? '-' }}</td>
                                        <td>{{ $student['class_name'] ?? '-' }}</td>
                                        <td>{{ $student['department_name'] ?? ($selectedDepartment !== '' ? $selectedDepartment : '-') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">Belum ada data siswa.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endif
                </div>
                <div id="weekly-student-pagination" class="mini-pagination" style="display:none;">
                    <span id="weekly-student-pagination-info" class="mini-pagination-info"></span>
                </div>
            </div>
        @endif

    </div>

    <script>
        (function () {
            const rows = Array.from(document.querySelectorAll('.weekly-student-row'));
            const pagination = document.getElementById('weekly-student-pagination');
            const paginationInfo = document.getElementById('weekly-student-pagination-info');
            const perPage = 10;
            if (!pagination || rows.length <= perPage) {
                return;
            }

            let currentPage = 1;
            const totalPages = Math.ceil(rows.length / perPage);

            function renderRows() {
                const start = (currentPage - 1) * perPage;
                const end = start + perPage;
                rows.forEach((row, idx) => {
                    row.style.display = idx >= start && idx < end ? '' : 'none';
                });

                if (paginationInfo) {
                    const from = start + 1;
                    const to = Math.min(end, rows.length);
                    paginationInfo.textContent = `Menampilkan ${from}-${to} dari ${rows.length} data`;
                }
            }

            function createButton(label, page, isActive = false, disabled = false) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'mini-page-btn' + (isActive ? ' active' : '');
                btn.textContent = label;
                btn.disabled = disabled;
                btn.addEventListener('click', () => {
                    currentPage = page;
                    renderRows();
                    renderPagination();
                });
                return btn;
            }

            function renderPagination() {
                const infoNode = paginationInfo;
                pagination.innerHTML = '';
                if (infoNode) {
                    pagination.appendChild(infoNode);
                }
                pagination.style.display = 'flex';
                pagination.appendChild(createButton('Prev', Math.max(1, currentPage - 1), false, currentPage === 1));
                for (let i = 1; i <= totalPages; i++) {
                    pagination.appendChild(createButton(String(i), i, i === currentPage));
                }
                pagination.appendChild(createButton('Next', Math.min(totalPages, currentPage + 1), false, currentPage === totalPages));
            }

            renderRows();
            renderPagination();
        })();

        (function () {
            const form = document.querySelector('form.weekly-toolbar');
            if (!form) return;

            const weekStart = form.querySelector('[name="week_start"]');
            const jurusan = form.querySelector('select[name="jurusan"]');
            const kelas = form.querySelector('select[name="kelas"]');
            const siswa = form.querySelector('select[name="siswa"]');
            const historyPerPage = document.getElementById('history_per_page');

            let timer = null;
            function submitAuto(delay = 0) {
                if (timer) clearTimeout(timer);
                timer = window.setTimeout(() => form.submit(), delay);
            }

            if (weekStart) weekStart.addEventListener('change', () => submitAuto());
            if (jurusan) {
                jurusan.addEventListener('change', () => {
                    if (kelas) kelas.value = '';
                    if (siswa) siswa.value = '';
                    submitAuto();
                });
            }
            if (kelas) {
                kelas.addEventListener('change', () => {
                    if (siswa) siswa.value = '';
                    submitAuto();
                });
            }
            if (siswa) siswa.addEventListener('change', () => submitAuto());
            if (historyPerPage) historyPerPage.addEventListener('change', () => {
                const historyForm = historyPerPage.closest('form');
                if (historyForm) historyForm.submit();
            });
        })();

        (function () {
            const statusEl = document.getElementById('weekly-mentor-autosave-status');
            const forms = Array.from(document.querySelectorAll('.weekly-mentor-auto-save-form'));
            if (forms.length === 0) return;

            const setStatus = (text, color = '#6b7280') => {
                if (!statusEl) return;
                statusEl.textContent = text;
                statusEl.style.color = color;
            };

            forms.forEach((form) => {
                let timer = null;
                const scheduleSave = (delay = 500, savingText = 'Menyimpan catatan...') => {
                    if (timer) clearTimeout(timer);
                    setStatus(savingText);

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
                            setStatus('Catatan tersimpan otomatis.');
                        } catch (e) {
                            const code = e && e.message ? ` (HTTP ${e.message})` : '';
                            setStatus(`Gagal menyimpan catatan. Coba lagi.${code}`, '#b91c1c');
                        }
                    }, delay);
                };

                const input = form.querySelector('[name="mentor_note"]');
                if (input) {
                    input.addEventListener('input', () => scheduleSave(500, 'Menyimpan catatan...'));
                }

                const approvedInput = form.querySelector('input[name="approved"][value="1"]');
                if (approvedInput) {
                    approvedInput.addEventListener('change', () => scheduleSave(150, 'Menyimpan validasi absensi...'));
                }
            });
        })();

        (function () {
            const statusEl = document.getElementById('weekly-kajur-autosave-status');
            const forms = Array.from(document.querySelectorAll('.weekly-kajur-auto-save-form'));
            if (forms.length === 0) return;

            const setStatus = (text, color = '#6b7280') => {
                if (!statusEl) return;
                statusEl.textContent = text;
                statusEl.style.color = color;
            };

            forms.forEach((form) => {
                const noteInput = form.querySelector('[name="kajur_note"]');
                const approvedInput = form.querySelector('input[name="approved"][value="1"]');
                let timer = null;

                const scheduleSave = (delay = 500) => {
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
                    }, delay);
                };

                if (noteInput) {
                    noteInput.addEventListener('input', () => scheduleSave(500));
                }
                if (approvedInput) {
                    approvedInput.addEventListener('change', () => scheduleSave(150));
                }
            });
        })();

    </script>
@endsection



