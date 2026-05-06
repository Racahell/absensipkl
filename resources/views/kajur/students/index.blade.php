@extends('layouts.app', ['title' => $title])

@section('content')
    <style>
        .monitoring-grid {
            display: grid;
            grid-template-columns: 170px 220px 180px minmax(220px, 1fr) 110px auto auto;
            gap: 10px;
            align-items: end;
        }
        .monitoring-grid select,
        .monitoring-grid input[type="text"],
        .monitoring-grid .btn {
            height: 40px;
        }
        .monitoring-grid > div {
            min-width: 0;
        }
        .monitoring-grid > .field-search {
            min-width: 220px;
        }
        .monitoring-grid > .field-action {
            min-width: auto;
        }
        .monitoring-grid > .field-action .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }
        .monitoring-actions {
            display: flex;
            gap: 8px;
            align-items: end;
            flex-wrap: wrap;
        }
        .monitoring-actions .btn {
            white-space: nowrap;
        }
        .mentor-cell-form {
            display: grid;
            gap: 8px;
            min-width: 220px;
        }
        .mentor-cell-form .btn {
            width: fit-content;
        }
        .mentor-save-state {
            font-size: 12px;
            color: #6b7280;
            min-height: 16px;
        }
        .mentor-save-state.ok {
            color: #166534;
        }
        .mentor-save-state.err {
            color: #991b1b;
        }
        .student-table th,
        .student-table td {
            vertical-align: middle;
        }
        .pagination-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .confirm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2100;
            padding: 16px;
        }
        .confirm-overlay.show {
            display: flex;
        }
        .confirm-modal {
            width: min(420px, 100%);
            background: #fff;
            border: 1px solid #fdba74;
            border-radius: 14px;
            box-shadow: 0 18px 36px rgba(17, 24, 39, 0.25);
            padding: 16px;
        }
        .confirm-modal h4 {
            margin: 0 0 8px;
            color: #7c2d12;
            font-size: 18px;
        }
        .confirm-modal p {
            margin: 0;
            color: #374151;
            line-height: 1.5;
            font-size: 14px;
        }
        .confirm-actions {
            margin-top: 14px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        @media (max-width: 980px) {
            .mentor-cell-form {
                min-width: 180px;
            }
            .monitoring-grid {
                grid-template-columns: 1fr 1fr;
            }
            .monitoring-grid > .field-search {
                grid-column: 1 / -1;
            }
        }
        @media (max-width: 640px) {
            .monitoring-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <div class="card mb-16">
        <h3 class="mt-0">Monitoring Siswa Jurusan {{ $departmentName !== '' ? $departmentName : '-' }}</h3>
        @if (in_array((string) (auth()->user()->role ?? ''), ['admin_sekolah', 'superadmin'], true))
            <form id="jurusan-form" method="GET" action="{{ route('kajur.students.index') }}" class="monitoring-actions mb-10">
                <div style="min-width:260px; width:260px;">
                    <label for="jurusan">Jurusan</label>
                    <select id="jurusan" name="jurusan" required>
                        <option value="">Pilih Jurusan</option>
                        @foreach (($departmentOptions ?? []) as $dept)
                            <option value="{{ $dept }}" {{ (string) ($departmentName ?? '') === (string) $dept ? 'selected' : '' }}>{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        @endif
        @if ($departmentName !== '')
        <form id="mass-assign-form" method="POST" action="{{ route('kajur.students.assign-mentor-department') }}" class="monitoring-grid mb-10">
            @csrf
            <input type="hidden" name="jurusan" value="{{ $departmentName }}">
            <div>
                <label for="mentor-role-all">Role Penugasan</label>
                <select id="mentor-role-all" name="mentor_role">
                    <option value="pembimbing_pkl">Instruktur PKL</option>
                    <option value="instruktur">Pembimbing</option>
                </select>
            </div>
            <div>
                <label for="mentor-all" id="mentor-all-label">Pembimbing</label>
                <select id="mentor-all" name="mentor_user_id">
                    <option value="">- Pilih Mentor -</option>
                    @foreach ($schoolMentors as $mentor)
                        <option value="{{ $mentor->id }}" data-role="pembimbing_pkl">{{ $mentor->name }}</option>
                    @endforeach
                    @foreach ($instructors as $mentor)
                        <option value="{{ $mentor->id }}" data-role="instruktur">{{ $mentor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="class-name-filter">Kelas</label>
                <select id="class-name-filter" name="class_name">
                    <option value="">Semua Kelas</option>
                    @foreach (($classOptions ?? []) as $classOption)
                        <option value="{{ $classOption }}" {{ (string) ($filters['class_name'] ?? '') === (string) $classOption ? 'selected' : '' }}>{{ $classOption }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field-search">
                <label for="q">Cari siswa</label>
                <input id="q" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nama / NIS / Kelas">
            </div>
            <div>
                <label for="per-page-filter">Tampilkan</label>
                <select id="per-page-filter" name="per_page">
                    @foreach (($perPageOptions ?? [10, 20, 50, 100]) as $option)
                        <option value="{{ $option }}" {{ (int) ($filters['per_page'] ?? 20) === (int) $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field-action">
                <input type="hidden" name="apply_all" value="1">
                <button type="submit" class="btn">Terapkan</button>
            </div>
            <div class="field-action">
                <button type="button" id="btn-filter-reset" class="btn btn-ghost">Reset</button>
            </div>
        </form>
        @endif
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="w-full student-table">
                <thead>
                    <tr>
                        <th style="width:40px; text-align:center;">
                            <input type="checkbox" id="check-all-students" title="Pilih semua baris di halaman ini">
                        </th>
                        <th>Nama</th>
                        <th>NIS</th>
                        <th>Kelas</th>
                        <th>Pembimbing</th>
                        <th>Instruktur</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        @php
                            $assignment = $assignmentMap[(int) $student->id] ?? ['pembimbing_pkl' => null, 'instruktur' => null];
                        @endphp
                        <tr
                            data-student-name="{{ mb_strtolower((string) $student->name) }}"
                            data-student-nis="{{ mb_strtolower((string) ($student->nis ?? '')) }}"
                            data-student-class="{{ mb_strtolower((string) ($student->class_name ?? '')) }}"
                        >
                            <td style="text-align:center;">
                                <input type="checkbox" class="student-pick" value="{{ (int) $student->id }}">
                            </td>
                            <td>{{ $student->name }}</td>
                            <td>{{ $student->nis ?? '-' }}</td>
                            <td>{{ $student->class_name ?? '-' }}</td>
                            <td>
                                <form method="POST" action="{{ route('kajur.students.assign-mentor', $student) }}" class="mentor-cell-form">
                                    @csrf
                                    <input type="hidden" name="jurusan" value="{{ $departmentName }}">
                                    <input type="hidden" name="mentor_role" value="pembimbing_pkl">
                                    <select name="mentor_user_id">
                                        <option value="">- Pilih -</option>
                                        @foreach ($schoolMentors as $mentor)
                                            <option value="{{ $mentor->id }}" {{ (int) ($assignment['pembimbing_pkl'] ?? 0) === (int) $mentor->id ? 'selected' : '' }}>
                                                {{ $mentor->name }}{{ $mentor->is_school_mentor_all_students ? ' (Semua Siswa)' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="mentor-save-state" aria-live="polite"></div>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('kajur.students.assign-mentor', $student) }}" class="mentor-cell-form">
                                    @csrf
                                    <input type="hidden" name="jurusan" value="{{ $departmentName }}">
                                    <input type="hidden" name="mentor_role" value="instruktur">
                                    <select name="mentor_user_id">
                                        <option value="">- Pilih -</option>
                                        @foreach ($instructors as $mentor)
                                            <option value="{{ $mentor->id }}" {{ (int) ($assignment['instruktur'] ?? 0) === (int) $mentor->id ? 'selected' : '' }}>
                                                {{ $mentor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="mentor-save-state" aria-live="polite"></div>
                                </form>
                            </td>
                            <td>
                                <a href="{{ route('kajur.students.show', array_merge(['student' => $student], $departmentName !== '' ? ['jurusan' => $departmentName] : [], ($filters['class_name'] ?? '') !== '' ? ['class_name' => $filters['class_name']] : [])) }}" class="btn btn-ghost" style="text-decoration:none;">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr id="students-empty-row">
                            <td colspan="7" style="text-align:center;">{{ $departmentName === '' ? 'Pilih jurusan untuk menampilkan data siswa.' : 'Tidak ada siswa pada jurusan ini.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-10 pagination-meta">
            <div>Data {{ $students->firstItem() ?? 0 }} - {{ $students->lastItem() ?? 0 }} dari {{ $students->total() }} total.</div>
            <div>{{ $students->links() }}</div>
        </div>
    </div>

    <div id="confirm-overlay" class="confirm-overlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
            <h4 id="confirm-title">Konfirmasi Perubahan</h4>
            <p id="confirm-message">Apakah Anda yakin ingin mengubah assignment mentor siswa ini?</p>
            <div class="confirm-actions">
                <button type="button" id="confirm-cancel" class="btn btn-ghost">Tidak</button>
                <button type="button" id="confirm-ok" class="btn">Ya, Ubah</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const roleSelect = document.getElementById('mentor-role-all');
            const mentorSelect = document.getElementById('mentor-all');
            const classFilterSelect = document.getElementById('class-name-filter');
            const searchInput = document.getElementById('q');
            const perPageSelect = document.getElementById('per-page-filter');
            const resetButton = document.getElementById('btn-filter-reset');
            const rows = Array.from(document.querySelectorAll('.student-table tbody tr[data-student-name]'));
            const emptyRow = document.getElementById('students-empty-row');
            const jurusanSelect = document.getElementById('jurusan');
            const jurusanForm = document.getElementById('jurusan-form');
            const massAssignForm = document.getElementById('mass-assign-form');
            const checkAllStudents = document.getElementById('check-all-students');
            const studentPickers = Array.from(document.querySelectorAll('.student-pick'));
            const confirmOverlay = document.getElementById('confirm-overlay');
            const confirmOk = document.getElementById('confirm-ok');
            const confirmCancel = document.getElementById('confirm-cancel');

            if (roleSelect && mentorSelect) {
                const options = Array.from(mentorSelect.options);
                function syncMentorOptions() {
                    const role = roleSelect.value;
                    const mentorLabel = document.getElementById('mentor-all-label');
                    let selectedValid = false;
                    options.forEach((opt) => {
                        const visible = (opt.dataset.role || '') === role;
                        opt.hidden = !visible;
                        if (visible && opt.selected) selectedValid = true;
                    });
                    if (mentorLabel) {
                        mentorLabel.textContent = role === 'instruktur' ? 'Pembimbing' : 'Instruktur PKL';
                    }
                    if (!selectedValid) {
                        const firstVisible = options.find((opt) => !opt.hidden);
                        if (firstVisible) mentorSelect.value = firstVisible.value;
                    }
                }

                roleSelect.addEventListener('change', syncMentorOptions);
                syncMentorOptions();
            }

            function applyLocalFilter() {
                const classVal = (classFilterSelect ? classFilterSelect.value : '').trim().toLowerCase();
                const qVal = (searchInput ? searchInput.value : '').trim().toLowerCase();
                let shown = 0;

                rows.forEach((row) => {
                    const rowClass = (row.dataset.studentClass || '').toLowerCase();
                    const rowName = (row.dataset.studentName || '').toLowerCase();
                    const rowNis = (row.dataset.studentNis || '').toLowerCase();
                    const classOk = classVal === '' || rowClass === classVal;
                    const qOk = qVal === '' || rowName.includes(qVal) || rowNis.includes(qVal) || rowClass.includes(qVal);
                    const visible = classOk && qOk;
                    row.style.display = visible ? '' : 'none';
                    if (visible) shown++;
                });

                if (emptyRow) {
                    emptyRow.style.display = shown === 0 ? '' : 'none';
                    if (shown === 0) {
                        emptyRow.querySelector('td').textContent = 'Tidak ada siswa sesuai filter.';
                    }
                }
            }

            if (resetButton) {
                resetButton.addEventListener('click', function () {
                    if (classFilterSelect) classFilterSelect.value = '';
                    if (searchInput) searchInput.value = '';
                    applyLocalFilter();
                });
            }
            if (searchInput) {
                let typingTimer = null;
                searchInput.addEventListener('input', function () {
                    if (typingTimer) clearTimeout(typingTimer);
                    typingTimer = setTimeout(applyLocalFilter, 180);
                });
                searchInput.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        applyLocalFilter();
                    }
                });
            }
            if (classFilterSelect) {
                classFilterSelect.addEventListener('change', applyLocalFilter);
            }
            if (perPageSelect) {
                perPageSelect.addEventListener('change', function () {
                    const url = new URL(window.location.href);
                    url.searchParams.set('per_page', perPageSelect.value);
                    if (classFilterSelect && classFilterSelect.value) {
                        url.searchParams.set('class_name', classFilterSelect.value);
                    } else {
                        url.searchParams.delete('class_name');
                    }
                    if (searchInput && searchInput.value.trim() !== '') {
                        url.searchParams.set('q', searchInput.value.trim());
                    } else {
                        url.searchParams.delete('q');
                    }
                    if (jurusanSelect && jurusanSelect.value) {
                        url.searchParams.set('jurusan', jurusanSelect.value);
                    }
                    window.location.href = url.toString();
                });
            }

            if (jurusanSelect && jurusanForm) {
                jurusanSelect.addEventListener('change', function () {
                    const value = (jurusanSelect.value || '').trim();
                    if (value === '') return;
                    jurusanForm.submit();
                });
            }

            if (checkAllStudents) {
                checkAllStudents.addEventListener('change', function () {
                    const checked = !!checkAllStudents.checked;
                    studentPickers.forEach((cb) => {
                        if (!cb.disabled && cb.closest('tr') && cb.closest('tr').style.display !== 'none') {
                            cb.checked = checked;
                        }
                    });
                });
            }

            if (massAssignForm) {
                massAssignForm.addEventListener('submit', function (event) {
                    massAssignForm.querySelectorAll('input[name="selected_ids[]"]').forEach((el) => el.remove());
                    const selectedVisible = studentPickers.filter((cb) => cb.checked);
                    const applyAllInput = massAssignForm.querySelector('input[name="apply_all"]');
                    if (applyAllInput) {
                        applyAllInput.value = selectedVisible.length === 0 ? '1' : '0';
                    }
                    if (selectedVisible.length === 0) {
                        return;
                    }
                    selectedVisible.forEach((cb) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'selected_ids[]';
                        input.value = cb.value;
                        massAssignForm.appendChild(input);
                    });
                });
            }

            const rowAssignForms = Array.from(document.querySelectorAll('.mentor-cell-form'));
            const askConfirm = function () {
                return new Promise((resolve) => {
                    if (!confirmOverlay || !confirmOk || !confirmCancel) {
                        resolve(true);
                        return;
                    }

                    confirmOverlay.classList.add('show');
                    confirmOverlay.setAttribute('aria-hidden', 'false');

                    const close = function (accepted) {
                        confirmOverlay.classList.remove('show');
                        confirmOverlay.setAttribute('aria-hidden', 'true');
                        confirmOk.removeEventListener('click', onOk);
                        confirmCancel.removeEventListener('click', onCancel);
                        confirmOverlay.removeEventListener('click', onBackdrop);
                        document.removeEventListener('keydown', onEsc);
                        resolve(accepted);
                    };
                    const onOk = function () { close(true); };
                    const onCancel = function () { close(false); };
                    const onBackdrop = function (event) {
                        if (event.target === confirmOverlay) close(false);
                    };
                    const onEsc = function (event) {
                        if (event.key === 'Escape') close(false);
                    };

                    confirmOk.addEventListener('click', onOk);
                    confirmCancel.addEventListener('click', onCancel);
                    confirmOverlay.addEventListener('click', onBackdrop);
                    document.addEventListener('keydown', onEsc);
                });
            };

            rowAssignForms.forEach((form) => {
                const select = form.querySelector('select[name="mentor_user_id"]');
                const statusEl = form.querySelector('.mentor-save-state');
                if (!select) return;
                select.dataset.prevValue = select.value || '';

                const saveForm = async function () {
                    const formData = new FormData(form);
                    select.disabled = true;
                    let ok = false;
                    if (statusEl) {
                        statusEl.classList.remove('ok', 'err');
                        statusEl.textContent = 'Menyimpan...';
                    }

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: formData,
                            credentials: 'same-origin',
                        });

                        if (!response.ok) {
                            let message = 'Gagal menyimpan.';
                            try {
                                const body = await response.json();
                                if (body?.message) message = body.message;
                            } catch (e) {}
                            throw new Error(message);
                        }

                        if (statusEl) {
                            statusEl.classList.remove('err');
                            statusEl.classList.add('ok');
                            statusEl.textContent = 'Tersimpan';
                        }
                        ok = true;
                    } catch (error) {
                        if (statusEl) {
                            statusEl.classList.remove('ok');
                            statusEl.classList.add('err');
                            statusEl.textContent = (error && error.message) ? error.message : 'Gagal menyimpan.';
                        }
                    } finally {
                        select.disabled = false;
                    }
                    return ok;
                };

                select.addEventListener('change', function () {
                    const prev = select.dataset.prevValue || '';
                    const next = select.value || '';
                    if (prev === next) return;

                    askConfirm().then((confirmed) => {
                        if (!confirmed) {
                            select.value = prev;
                            return;
                        }

                        saveForm().then((ok) => {
                            if (ok) {
                                select.dataset.prevValue = next;
                                return;
                            }
                            select.value = prev;
                        }).catch(() => {
                            select.value = prev;
                        });
                    });
                });
            });
        })();
    </script>
@endsection
