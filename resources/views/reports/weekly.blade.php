@extends('layouts.app', ['title' => $title])

@section('content')
    @php
        $formatStatus = static function (?string $status): string {
            $raw = strtolower(trim((string) $status));
            return match (true) {
                $raw === '', $raw === '-' => '-',
                $raw === 'pending_pembimbing' => 'pending pembimbing sekolah',
                $raw === 'pending_instruktur' => 'approved pembimbing sekolah',
                $raw === 'pending_kajur' => 'approved instruktur',
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
        <div class="weekly-headline">
            <span>Status: {{ $formatStatus((string) ($weeklyValidation?->status ?? 'pending')) }}</span>
        </div>
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
                <label for="week_start">Minggu Mulai</label>
                <input id="week_start" type="date" name="week_start" value="{{ $weekStart->toDateString() }}">
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
                @if (in_array($role, ['instruktur', 'pembimbing_pkl'], true))
                    <button type="button" class="btn btn-ghost" id="open-instruktur-note-modal">Tambah Catatan</button>
                @endif
                @if ($role === 'kajur')
                    <button type="button" class="btn btn-ghost" id="open-kajur-note-modal">Tambah Catatan</button>
                @endif
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

        <div class="panel mb-14">
            <h4 class="mt-0">Catatan Mingguan Saat Ini</h4>
            <p style="margin:0 0 8px;">
                <strong>Instruktur:</strong> {{ ($instructorWeeklyNote ?? null) ?: '-' }}
                @if (! empty($instructorWeeklyNoteClass))
                    <small class="text-muted">(kelas: {{ $instructorWeeklyNoteClass }})</small>
                @endif
            </p>
            <p style="margin:0;"><strong>Kajur:</strong> {{ $weeklyValidation?->kajur_note ?: ($weeklyValidation?->note ?: '-') }}</p>
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
                    @elseif ($selectedDepartment !== '')
                        Daftar Siswa (Jurusan {{ $selectedDepartment }})
                    @else
                        Daftar Siswa (Semua Siswa)
                    @endif
                </h4>
                <div style="overflow:auto;">
                    <table class="w-full">
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
                </div>
                <div id="weekly-student-pagination" class="mini-pagination" style="display:none;"></div>
            </div>
        @endif

        @if (in_array($role, ['instruktur', 'pembimbing_pkl', 'kajur', 'superadmin'], true))
            <div class="weekly-grid {{ in_array($role, ['instruktur', 'kajur'], true) ? 'single' : '' }}">
                @if (in_array($role, ['instruktur', 'pembimbing_pkl', 'superadmin'], true))
                    @if (in_array($role, ['instruktur', 'pembimbing_pkl'], true))
                        <div class="panel">
                            <h4 class="mt-0">{{ $role === 'pembimbing_pkl' ? 'Catatan Mingguan Pembimbing Sekolah' : 'Catatan Mingguan Instruktur' }}</h4>
                            @if ($weeklyValidation?->instruktur_note)
                                <small class="text-muted">Catatan terakhir: {{ $weeklyValidation->instruktur_note }}</small>
                            @else
                                <small class="text-muted">{{ $role === 'pembimbing_pkl' ? 'Belum ada catatan pembimbing sekolah minggu ini.' : 'Belum ada catatan instruktur minggu ini.' }}</small>
                            @endif
                        </div>
                    @else
                    <form method="POST" action="{{ route('reports.weekly.note') }}" class="panel">
                        @csrf
                        <h4 class="mt-0">Catatan Mingguan Instruktur</h4>
                        <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
                        <input type="hidden" name="jurusan" value="{{ $selectedDepartment }}">
                        <input type="hidden" name="kelas" value="{{ $selectedClass }}">
                        @if ($role === 'superadmin')
                            <input type="hidden" name="note_role" value="instruktur">
                        @endif
                        <textarea name="note" rows="3" placeholder="Catatan mingguan instruktur (wajib)" required>{{ old('note') }}</textarea>
                        @if ($weeklyValidation?->instruktur_note)
                            <small class="text-muted">Catatan terakhir: {{ $weeklyValidation->instruktur_note }}</small>
                        @endif
                        <button type="submit" class="btn btn-ghost">Simpan Catatan Instruktur</button>
                    </form>
                    @endif
                @endif

                @if (in_array($role, ['kajur', 'superadmin'], true))
                    @if ($role === 'kajur')
                        <div class="panel">
                            <h4 class="mt-0">Catatan Mingguan Kajur</h4>
                            <div style="margin-bottom:8px;">
                                <strong>Catatan Instruktur:</strong>
                                <div style="margin-top:4px; color:#7c2d12;">
                                    {{ ($instructorWeeklyNote ?? null) ?: 'Belum ada catatan instruktur minggu ini.' }}
                                    @if (! empty($instructorWeeklyNoteClass))
                                        <small class="text-muted">(kelas: {{ $instructorWeeklyNoteClass }})</small>
                                    @endif
                                </div>
                            </div>
                            @if ($weeklyValidation?->kajur_note)
                                <small class="text-muted">Catatan terakhir: {{ $weeklyValidation->kajur_note }}</small>
                            @else
                                <small class="text-muted">Belum ada catatan kajur minggu ini.</small>
                            @endif
                        </div>
                    @else
                        <form method="POST" action="{{ route('reports.weekly.note') }}" class="panel">
                            @csrf
                            <h4 class="mt-0">Catatan Mingguan Kajur</h4>
                            <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
                            <input type="hidden" name="jurusan" value="{{ $selectedDepartment }}">
                            <input type="hidden" name="kelas" value="{{ $selectedClass }}">
                            @if ($role === 'superadmin')
                                <input type="hidden" name="note_role" value="kajur">
                            @endif
                            <textarea name="note" rows="3" placeholder="Catatan mingguan kajur (wajib)" required>{{ old('note') }}</textarea>
                            @if ($weeklyValidation?->kajur_note)
                                <small class="text-muted">Catatan terakhir: {{ $weeklyValidation->kajur_note }}</small>
                            @endif
                            <button type="submit" class="btn btn-ghost">Simpan Catatan Kajur</button>
                        </form>
                    @endif
                @endif

            </div>
        @endif
    </div>

    @if (in_array($role, ['instruktur', 'pembimbing_pkl'], true))
        <div class="weekly-modal-backdrop" id="instruktur-note-modal">
            <div class="weekly-modal">
                <h4>{{ $role === 'pembimbing_pkl' ? 'Catatan Mingguan Pembimbing Sekolah' : 'Tambah Catatan Mingguan Instruktur' }}</h4>
                <form method="POST" action="{{ route('reports.weekly.note') }}">
                    @csrf
                    <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
                    <input type="hidden" name="jurusan" value="{{ $selectedDepartment }}">
                    <input type="hidden" name="kelas" value="{{ $selectedClass }}">
                    <textarea name="note" rows="5" placeholder="{{ $role === 'pembimbing_pkl' ? 'Tulis catatan mingguan pembimbing sekolah' : 'Tulis catatan mingguan instruktur' }}" required>{{ old('note', $weeklyValidation?->instruktur_note) }}</textarea>
                    <div class="weekly-modal-actions">
                        @if (!empty($weeklyValidation?->instruktur_note))
                            <button
                                type="submit"
                                class="btn btn-danger js-weekly-note-delete"
                                formaction="{{ route('reports.weekly.note.delete') }}"
                                formnovalidate>
                                Hapus Catatan
                            </button>
                        @endif
                        <button type="button" class="btn" id="close-instruktur-note-modal">Batal</button>
                        <button type="submit" class="btn btn-ghost">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($role === 'kajur')
        <div class="weekly-modal-backdrop" id="kajur-note-modal">
            <div class="weekly-modal">
                <h4>Tambah Catatan Mingguan Kajur</h4>
                <form method="POST" action="{{ route('reports.weekly.note') }}">
                    @csrf
                    <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
                    <input type="hidden" name="jurusan" value="{{ $selectedDepartment }}">
                    <input type="hidden" name="kelas" value="{{ $selectedClass }}">
                    <input type="hidden" name="note_role" value="kajur">
                    <textarea name="note" rows="5" placeholder="Tulis catatan mingguan kajur" required>{{ old('note', $weeklyValidation?->kajur_note) }}</textarea>
                    <div class="weekly-modal-actions">
                        @if (!empty($weeklyValidation?->kajur_note))
                            <button
                                type="submit"
                                class="btn btn-danger js-weekly-note-delete"
                                formaction="{{ route('reports.weekly.note.delete') }}"
                                formnovalidate>
                                Hapus Catatan
                            </button>
                        @endif
                        <button type="button" class="btn" id="close-kajur-note-modal">Batal</button>
                        <button type="submit" class="btn btn-ghost">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card mt-14" style="margin-top:24px;">
        <div class="flex items-center justify-between wrap gap-10 mb-10">
            <h3 class="mt-0" style="margin-bottom:0;">Riwayat Validasi Mingguan</h3>
            <form method="GET" action="{{ route('reports.weekly') }}" class="flex items-center gap-8">
                <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
                <input type="hidden" name="jurusan" value="{{ $selectedDepartment }}">
                <input type="hidden" name="kelas" value="{{ $selectedClass }}">
                <input type="hidden" name="siswa" value="{{ $selectedStudent }}">
                <label for="history_per_page" style="margin:0;">Tampilkan</label>
                <select id="history_per_page" name="history_per_page">
                    @foreach (($historyPerPageOptions ?? [10, 20, 50, 100]) as $opt)
                        <option value="{{ $opt }}" {{ (int) ($historyPerPage ?? 10) === (int) $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="table-wrap">
            <table class="w-full">
                <thead>
                    <tr>
                        <th>Minggu</th>
                        <th>Jurusan</th>
                        <th>Kelas</th>
                        <th>Status</th>
                        <th>Validator</th>
                        <th>Waktu</th>
                        <th>Catatan Pembimbing</th>
                        <th>Catatan Kajur</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($validationHistory ?? collect()) as $row)
                        <tr>
                            <td>{{ optional($row->week_start)->format('Y-m-d') }} s/d {{ optional($row->week_end)->format('Y-m-d') }}</td>
                            <td>{{ $row->department_name ?: '-' }}</td>
                            <td>{{ $row->class_name ?: '-' }}</td>
                            <td>{{ $formatStatus($row->status) }}</td>
                            <td>{{ $row->approverKajur?->name ?? $row->validator?->name ?? '-' }}</td>
                            <td>{{ optional($row->validated_at)->format('Y-m-d H:i:s') ?? '-' }}</td>
                            <td>{{ $row->instruktur_note ?: '-' }}</td>
                            <td>{{ $row->kajur_note ?: ($row->note ?: '-') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="text-align:center;">Belum ada riwayat validasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (! empty($validationHistory) && method_exists($validationHistory, 'links'))
            <div class="mt-10">{{ $validationHistory->links() }}</div>
        @endif
    </div>

    <script>
        (function () {
            const rows = Array.from(document.querySelectorAll('.weekly-student-row'));
            const pagination = document.getElementById('weekly-student-pagination');
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
                pagination.innerHTML = '';
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
            document.querySelectorAll('.js-weekly-note-delete').forEach((btn) => {
                btn.addEventListener('click', async (event) => {
                    event.preventDefault();
                    const form = btn.closest('form');
                    const confirmed = await window.AppDialog.confirm('Hapus catatan minggu ini?');
                    if (!confirmed || !form) return;
                    form.action = btn.getAttribute('formaction');
                    form.submit();
                });
            });
        })();

        (function () {
            const openBtn = document.getElementById('open-instruktur-note-modal');
            const closeBtn = document.getElementById('close-instruktur-note-modal');
            const modal = document.getElementById('instruktur-note-modal');
            if (!openBtn || !closeBtn || !modal) return;

            function openModal() {
                modal.classList.add('show');
            }

            function closeModal() {
                modal.classList.remove('show');
            }

            openBtn.addEventListener('click', openModal);
            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
        })();

        (function () {
            const openBtn = document.getElementById('open-kajur-note-modal');
            const closeBtn = document.getElementById('close-kajur-note-modal');
            const modal = document.getElementById('kajur-note-modal');
            if (!closeBtn || !modal) return;

            function openModal() {
                modal.classList.add('show');
            }

            function closeModal() {
                modal.classList.remove('show');
            }

            if (openBtn) openBtn.addEventListener('click', openModal);
            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
        })();
    </script>
@endsection



