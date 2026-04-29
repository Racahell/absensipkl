@extends('layouts.app', ['title' => $title])

@section('content')
    <style>
        .form-grid {
            display: grid;
            gap: 10px;
            max-width: 760px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .form-grid .full { grid-column: 1 / -1; }
        .input-ui, .select-ui {
            width: 100%;
            border: 1px solid #fdba74;
            border-radius: 10px;
            padding: 10px 12px;
            outline: none;
            background: #fff;
        }
        .input-ui:focus, .select-ui:focus {
            border-color: #ea580c;
            box-shadow: 0 0 0 3px #ffedd5;
        }
        .btn-ui {
            border: 1px solid #ea580c;
            background: #ea580c;
            color: #fff;
            padding: 8px 12px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-ui-light {
            border: 1px solid #fdba74;
            background: #fff7ed;
            color: #9a3412;
            padding: 8px 12px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }
        .tab-link {
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid #fdba74;
            color: #9a3412;
            background: #fff7ed;
            font-size: 13px;
        }
        .tab-link.active {
            background: #ea580c;
            color: #fff;
            border-color: #ea580c;
        }
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(17, 24, 39, 0.55);
            align-items: center;
            justify-content: center;
            padding: 14px;
        }
        .modal-box {
            width: min(620px, 100%);
            max-height: 90vh;
            overflow: auto;
            background: #fff;
            border: 1px solid #fdba74;
            border-radius: 14px;
            padding: 14px;
        }
        .bulk-actions-wrap {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            min-width: 360px;
            flex-wrap: nowrap;
        }
        .bulk-select-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            color: #7c2d12;
            margin: 0;
            line-height: 1;
        }
        .bulk-select-label input[type="checkbox"] {
            margin: 0;
        }
        .bulk-selected-count {
            display: inline-flex;
            align-items: center;
            line-height: 1;
        }
        .users-toolbar {
            display: flex;
            flex-wrap: nowrap;
            gap: 12px;
            align-items: end;
            overflow-x: auto;
        }
        .users-toolbar > form {
            margin: 0;
        }
        .filters-wrap {
            display: flex;
            flex-wrap: nowrap;
            gap: 10px;
            align-items: end;
            flex: 1 1 auto;
            min-width: 720px;
        }
        .bulk-meta-row {
            display: flex;
            flex-wrap: nowrap;
            gap: 10px;
            align-items: center;
            justify-content: flex-start;
        }
        .bulk-meta-left {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex-wrap: nowrap;
        }
        .selected-count-chip {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid #fdba74;
            background: #fff7ed;
            color: #9a3412;
            font-size: 13px;
            font-weight: 600;
            line-height: 1;
        }
        .bulk-action-row {
            display: flex;
            flex-wrap: nowrap;
            gap: 8px;
            align-items: center;
        }
        .bulk-action-row.right {
            justify-content: flex-end;
        }
        .bulk-location-select {
            min-width: 260px;
            flex: 1 1 260px;
        }
        @media (max-width: 900px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .users-toolbar {
                flex-wrap: wrap;
                overflow-x: visible;
            }
            .bulk-actions-wrap {
                min-width: 100%;
                flex-wrap: wrap;
            }
            .filters-wrap {
                min-width: 100%;
                flex-wrap: wrap;
            }
            .bulk-meta-row,
            .bulk-meta-left,
            .bulk-action-row {
                flex-wrap: wrap;
            }
        }
    </style>

    <div class="card mb-16">
        <h3 class="mt-0 text-primary">Tambah User</h3>
        @if (session('success'))
            <div class="alert alert-success mb-10">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-error mb-10">{{ session('error') }}</div>
        @endif
        @if ($errors->any() && old('form_context') !== 'create_staff')
            <div class="alert alert-error mb-10">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="flex wrap gap-10">
            @if (! $isKajur)
                <button type="button" class="btn-ui" id="open-create-student-modal">Tambah Siswa</button>
            @endif
            <button type="button" class="btn-ui-light" id="open-create-staff-modal">Tambah Guru/Staff</button>
        </div>
    </div>

    <datalist id="class-options-list">
        @foreach (($classOptions ?? []) as $classOption)
            <option value="{{ $classOption }}"></option>
        @endforeach
    </datalist>
    <datalist id="department-options-list">
        @foreach (($departmentOptions ?? []) as $departmentOption)
            <option value="{{ $departmentOption }}"></option>
        @endforeach
    </datalist>

    <div class="card">
        <div class="flex items-center justify-between wrap gap-10 mb-10">
            <h3 class="mt-0 text-primary">Daftar User</h3>
            <div class="flex gap-8">
                <a href="{{ route('fitur.manajemen-pengguna', ['tab' => 'active']) }}" class="tab-link {{ $tab === 'active' ? 'active' : '' }}">
                    Aktif ({{ $activeCount }})
                </a>
                @if ($hasDeletedTabAccess)
                    <a href="{{ route('fitur.manajemen-pengguna', ['tab' => 'deleted']) }}" class="tab-link {{ $tab === 'deleted' ? 'active' : '' }}">
                        Deleted ({{ $deletedCount }})
                    </a>
                @endif
            </div>
        </div>
        <div class="users-toolbar {{ $tab === 'deleted' ? 'deleted-mode' : '' }} mb-10">
            <form id="users-filter-form" class="filters-wrap" method="GET" action="{{ route('fitur.manajemen-pengguna') }}">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div style="min-width:220px; max-width:320px; flex:0 1 320px;">
                    <label for="user-search">Cari User</label>
                    <input id="user-search" name="q" class="input-ui" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="Ketik nama, NIS, NUPTK, email...">
                </div>
                <div style="min-width:170px; max-width:220px; flex:0 1 190px;">
                    <label for="role-filter">Filter Role</label>
                    <select id="role-filter" name="role" class="select-ui">
                        <option value="all">Semua Role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" {{ ($filters['role'] ?? 'all') === $role ? 'selected' : '' }}>
                                {{ $roleLabels[$role] ?? $role }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div style="min-width:130px; max-width:170px; flex:0 1 140px;">
                    <label for="per-page-select">Tampilkan</label>
                    <select id="per-page-select" name="per_page" class="select-ui">
                        @foreach (($perPageOptions ?? [10, 20, 50, 100]) as $opt)
                            <option value="{{ $opt }}" {{ (int) ($perPage ?? 20) === (int) $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
            <form id="bulk-action-form" method="POST" action="{{ route('users.bulk-action') }}" class="bulk-actions-wrap {{ $tab === 'deleted' ? 'deleted-mode' : '' }}">
                @csrf
                <input type="hidden" name="action" id="bulk-action-input">
                <div id="bulk-selected-ids"></div>
                <div class="bulk-meta-row">
                    <div class="bulk-meta-left">
                        <label class="bulk-select-label">
                            <input type="checkbox" id="select-all-users">
                            Select All
                        </label>
                        <span id="selected-count" class="selected-count-chip">0 dipilih</span>
                    </div>
                </div>
                <div class="bulk-action-row {{ $tab === 'deleted' ? 'right' : '' }}">
                    @if ($tab === 'active')
                    <button type="button" class="btn-danger bulk-action-btn" data-action="delete">Delete Dipilih</button>
                    @elseif ($isSuperadmin)
                    <button type="button" class="btn-success bulk-action-btn" data-action="restore">Restore Dipilih</button>
                    <button type="button" class="btn-danger bulk-action-btn" data-action="force_delete">Delete Permanent Dipilih</button>
                    @endif
                </div>
            </form>
        </div>
        <div class="table-wrap">
            <table class="w-full">
                <thead>
                    <tr>
                        <th style="width:42px;">Pilih</th>
                        <th>Nama</th>
                        <th>NIS</th>
                        <th>NUPTK</th>
                        <th>Role</th>
                        <th>Kelas</th>
                        <th>Jurusan</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $item)
                        <tr
                            class="user-row"
                            data-user-id="{{ $item->id }}"
                            data-name="{{ strtolower($item->name) }}"
                            data-nis="{{ strtolower($item->nis ?? '') }}"
                            data-nuptk="{{ strtolower($item->nuptk ?? '') }}"
                            data-email="{{ strtolower($item->email ?? '') }}"
                            data-role="{{ strtolower($item->role) }}"
                            data-class-name="{{ strtolower($item->class_name ?? '') }}"
                            data-department-name="{{ strtolower($item->department_name ?? '') }}"
                        >
                            <td style="text-align:center;">
                                <input type="checkbox" class="user-select" value="{{ $item->id }}">
                            </td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->nis }}</td>
                            <td>{{ $item->nuptk ?? '-' }}</td>
                            <td>{{ $roleLabels[$item->role] ?? $item->role }}</td>
                            <td>{{ $item->class_name ?? '-' }}</td>
                            <td>{{ $item->department_name ?? '-' }}</td>
                            <td>{{ $item->phone ?? '-' }}</td>
                            <td>{{ $item->trashed() ? 'Deleted' : 'Aktif' }}</td>
                            <td style="white-space:nowrap;">
                                @if ($tab === 'active')
                                    <button
                                        type="button"
                                        class="btn-ui-light open-user-detail"
                                        data-name="{{ $item->name }}"
                                        data-nis="{{ $item->nis }}"
                                        data-nuptk="{{ $item->nuptk ?? '-' }}"
                                        data-email="{{ $item->email }}"
                                        data-phone="{{ $item->phone ?? '-' }}"
                                        data-role="{{ $item->role }}"
                                        data-class-name="{{ $item->class_name ?? '-' }}"
                                        data-department-name="{{ $item->department_name ?? '-' }}"
                                        data-update-url="{{ route('users.update', $item) }}"
                                        data-delete-url="{{ route('users.destroy', $item) }}"
                                    >
                                        Detail
                                    </button>
                                @else
                                    @if ($isSuperadmin)
                                        <form method="POST" action="{{ route('users.restore', $item->id) }}" style="display:inline;">
                                            @csrf
                                            <button class="btn-success" type="submit">Restore</button>
                                        </form>
                                        <form method="POST" action="{{ route('users.force-delete', $item->id) }}" style="display:inline;" class="js-force-delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn-danger" type="submit">Delete Permanent</button>
                                        </form>
                                    @else
                                        <span class="text-muted">Lihat saja</span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-primary" style="text-align:center;">
                                Tidak ada data user.
                            </td>
                        </tr>
                    @endforelse
                    <tr id="empty-search-row" style="display:none;">
                        <td colspan="10" class="text-primary" style="text-align:center;">
                            Tidak ada data yang cocok dengan pencarian.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-10">{{ $users->links() }}</div>
    </div>

    <div id="user-detail-modal" class="modal-overlay">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-10">
                <h4 class="mt-0 text-primary">Detail User</h4>
                <button type="button" class="btn-ui-light close-modal">Tutup</button>
            </div>
            <form id="user-detail-form" method="POST" class="grid gap-8">
                @csrf
                @method('PUT')
                <div>
                    <label for="detail-name">Nama</label>
                    <input class="input-ui" name="name" id="detail-name" required>
                </div>
                <div>
                    <label for="detail-nis">NIS (wajib untuk siswa)</label>
                    <input class="input-ui" name="nis" id="detail-nis" placeholder="NIS (wajib untuk siswa)">
                </div>
                <div>
                    <label for="detail-nuptk">NUPTK (opsional)</label>
                    <input class="input-ui" name="nuptk" id="detail-nuptk" placeholder="NUPTK (opsional)">
                </div>
                <div>
                    <label for="detail-class-name">Kelas (khusus siswa/wali kelas)</label>
                    <input class="input-ui" name="class_name" id="detail-class-name" list="class-options-list" placeholder="Kelas (khusus siswa/wali kelas)">
                </div>
                <div>
                    <label for="detail-department-name">Jurusan (contoh: RPL)</label>
                    <input class="input-ui" name="department_name" id="detail-department-name" list="department-options-list" placeholder="Jurusan (contoh: RPL)" {{ $isKajur ? 'readonly' : '' }}>
                </div>
                <div>
                    <label for="detail-email">Email</label>
                    <input class="input-ui" name="email" id="detail-email" type="email" required>
                </div>
                <div>
                    <label for="detail-phone">No WA (opsional)</label>
                    <input class="input-ui" name="phone" id="detail-phone" placeholder="No WA (opsional)">
                </div>
                <div>
                    <label for="detail-role">Role</label>
                    <select class="select-ui" name="role" id="detail-role" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}">{{ $roleLabels[$role] ?? $role }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="detail-password">Password baru (opsional)</label>
                    <input class="input-ui" id="detail-password" name="password" type="password" placeholder="Password baru (opsional)">
                </div>
                <div class="flex gap-8 wrap" style="justify-content:flex-end;">
                    <button class="btn-success" type="submit">Edit</button>
                    <button type="button" class="btn-danger" id="detail-delete-btn">Delete</button>
                    <button type="button" class="btn-ui-light close-modal">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <div id="user-delete-modal" class="modal-overlay">
        <div class="modal-box" style="width:min(460px,100%);">
            <h4 class="mt-0 text-primary">Konfirmasi Delete</h4>
            <p id="delete-confirm-text" class="mb-14 text-primary">Yakin ingin menghapus user ini?</p>
            <form id="user-delete-form" method="POST" class="flex gap-8 wrap" style="justify-content:flex-end;">
                @csrf
                @method('DELETE')
                <button class="btn-danger" type="submit">Delete</button>
                <button type="button" class="btn-ui-light close-modal">Batal</button>
            </form>
        </div>
    </div>

    @if (! $isKajur)
    <div id="create-student-modal" class="modal-overlay">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-10">
                <h4 class="mt-0 text-primary">Tambah Siswa</h4>
                <button type="button" class="btn-ui-light close-modal">Tutup</button>
            </div>
            <form method="POST" action="{{ route('users.store') }}" class="form-grid" style="max-width:none;">
                @csrf
                <input type="hidden" name="form_context" value="create_student">
                <input type="hidden" name="role" value="siswa">
                <input class="input-ui" name="name" placeholder="Nama Siswa" value="{{ old('role') === 'siswa' ? old('name') : '' }}" required>
                <input class="input-ui" name="nis" placeholder="NIS" value="{{ old('role') === 'siswa' ? old('nis') : '' }}" required>
                <input class="input-ui" name="class_name" list="class-options-list" placeholder="Kelas (contoh: XII RPL 1)" value="{{ old('role') === 'siswa' ? old('class_name') : '' }}">
                <input class="input-ui" name="department_name" list="department-options-list" placeholder="Jurusan (contoh: RPL)" value="{{ old('role') === 'siswa' ? old('department_name') : '' }}">
                <input class="input-ui" name="email" type="email" placeholder="Email" value="{{ old('role') === 'siswa' ? old('email') : '' }}" required>
                <input class="input-ui" name="phone" placeholder="No WA (opsional)" value="{{ old('role') === 'siswa' ? old('phone') : '' }}">
                <input class="input-ui" value="Role: siswa (otomatis)" readonly>
                <input class="input-ui" name="password" type="password" placeholder="Password" required>
                <div class="full flex gap-8 wrap" style="justify-content:flex-end;">
                    <button class="btn-ui" type="submit">Save Siswa</button>
                    <button type="button" class="btn-ui-light close-modal">Batal</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <div id="create-staff-modal" class="modal-overlay">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-10">
                <h4 class="mt-0 text-primary">Tambah Guru/Staff</h4>
                <button type="button" class="btn-ui-light close-modal">Tutup</button>
            </div>
            @if ($errors->any() && old('form_context') === 'create_staff')
                <div class="alert alert-error mb-10">
                    {{ $errors->first() }}
                </div>
            @endif
            @if (session('error') && old('form_context') === 'create_staff')
                <div class="alert alert-error mb-10">
                    {{ session('error') }}
                </div>
            @endif
            <form method="POST" action="{{ route('users.store') }}" class="form-grid" style="max-width:none;">
                @csrf
                <input type="hidden" name="form_context" value="create_staff">
                <input class="input-ui" name="name" placeholder="Nama Guru/Staff" value="{{ old('role') !== 'siswa' ? old('name') : '' }}" required>
                <input class="input-ui" name="nuptk" placeholder="NUPTK (opsional)" value="{{ old('role') !== 'siswa' ? old('nuptk') : '' }}">
                <input class="input-ui" name="class_name" list="class-options-list" placeholder="Kelas Binaan (khusus wali kelas)" value="{{ old('role') !== 'siswa' ? old('class_name') : '' }}">
                @if ($isKajur)
                    <input class="input-ui" name="department_name" value="{{ $actorDepartmentName ?? '' }}" readonly>
                @else
                    <input class="input-ui" name="department_name" list="department-options-list" placeholder="Jurusan (opsional)" value="{{ old('role') !== 'siswa' ? old('department_name') : '' }}">
                @endif
                <input class="input-ui" name="email" type="email" placeholder="Email" value="{{ old('role') !== 'siswa' ? old('email') : '' }}" required>
                <input class="input-ui" name="phone" placeholder="No WA (opsional)" value="{{ old('role') !== 'siswa' ? old('phone') : '' }}">
                <select class="select-ui full" name="role" required>
                    @foreach ($staffRoles as $role)
                        <option value="{{ $role }}" {{ old('role') === $role ? 'selected' : '' }}>{{ $roleLabels[$role] ?? $role }}</option>
                    @endforeach
                </select>
                <input class="input-ui full" name="password" type="password" placeholder="Password" required>
                <div class="full flex gap-8 wrap" style="justify-content:flex-end;">
                    <button class="btn-ui" type="submit">Save Guru/Staff</button>
                    <button type="button" class="btn-ui-light close-modal">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const lang = window.localStorage.getItem('ui_lang') || 'id';
            const uiText = lang === 'en'
                ? {
                    processing: 'Processing...',
                    selectedCount: 'selected',
                    pickOne: 'Select at least one user first.',
                    confirmDelete: 'Are you sure you want to delete selected users?',
                    confirmRestore: 'Are you sure you want to restore selected users?',
                    confirmForceDelete: 'Are you sure you want to permanently delete selected users?',
                    confirmDeleteOne: 'Are you sure you want to delete user',
                }
                : {
                    processing: 'Memproses...',
                    selectedCount: 'dipilih',
                    pickOne: 'Pilih minimal satu user terlebih dahulu.',
                    confirmDelete: 'Yakin delete user yang dipilih?',
                    confirmRestore: 'Yakin restore user yang dipilih?',
                    confirmForceDelete: 'Yakin delete permanent user yang dipilih?',
                    confirmDeleteOne: 'Yakin ingin menghapus user',
                };

            const detailModal = document.getElementById('user-detail-modal');
            const deleteModal = document.getElementById('user-delete-modal');
            const createStudentModal = document.getElementById('create-student-modal');
            const createStaffModal = document.getElementById('create-staff-modal');
            const allModals = [detailModal, deleteModal, createStudentModal, createStaffModal].filter(Boolean);
            const openCreateStudentModalBtn = document.getElementById('open-create-student-modal');
            const openCreateStaffModalBtn = document.getElementById('open-create-staff-modal');

            const detailForm = document.getElementById('user-detail-form');
            const deleteForm = document.getElementById('user-delete-form');
            const deleteText = document.getElementById('delete-confirm-text');
            const deleteSubmitBtn = deleteForm ? deleteForm.querySelector('button[type="submit"]') : null;
            const usersFilterForm = document.getElementById('users-filter-form');
            const searchInput = document.getElementById('user-search');
            const roleFilter = document.getElementById('role-filter');
            const userRows = Array.from(document.querySelectorAll('.user-row'));
            const userSelectBoxes = Array.from(document.querySelectorAll('.user-select'));
            const selectAllUsers = document.getElementById('select-all-users');
            const selectedCount = document.getElementById('selected-count');
            const bulkActionForm = document.getElementById('bulk-action-form');
            const bulkActionInput = document.getElementById('bulk-action-input');
            const bulkSelectedIds = document.getElementById('bulk-selected-ids');
            const bulkActionButtons = Array.from(document.querySelectorAll('.bulk-action-btn'));
            const perPageSelect = document.getElementById('per-page-select');

            const deleteBtn = document.getElementById('detail-delete-btn');

            let activeUser = null;

            function closeAllModals() {
                allModals.forEach((modal) => {
                    modal.style.display = 'none';
                });
                document.body.style.overflow = '';
            }

            function openModal(modal) {
                if (!modal) return;
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }

            if (openCreateStudentModalBtn) {
                openCreateStudentModalBtn.addEventListener('click', function () {
                    openModal(createStudentModal);
                });
            }

            if (openCreateStaffModalBtn) {
                openCreateStaffModalBtn.addEventListener('click', function () {
                    openModal(createStaffModal);
                });
            }

            document.querySelectorAll('.open-user-detail').forEach((button) => {
                button.addEventListener('click', function () {
                    activeUser = {
                        name: this.dataset.name,
                        role: this.dataset.role,
                        nis: this.dataset.nis,
                        nuptk: this.dataset.nuptk,
                        className: this.dataset.className,
                        departmentName: this.dataset.departmentName,
                        email: this.dataset.email,
                        phone: this.dataset.phone,
                        updateUrl: this.dataset.updateUrl,
                        deleteUrl: this.dataset.deleteUrl,
                    };

                    detailForm.action = activeUser.updateUrl;
                    document.getElementById('detail-name').value = activeUser.name;
                    document.getElementById('detail-role').value = activeUser.role;
                    document.getElementById('detail-nis').value = activeUser.nis === '-' ? '' : activeUser.nis;
                    document.getElementById('detail-nuptk').value = activeUser.nuptk === '-' ? '' : activeUser.nuptk;
                    document.getElementById('detail-class-name').value = activeUser.className === '-' ? '' : activeUser.className;
                    document.getElementById('detail-department-name').value = activeUser.departmentName === '-' ? '' : activeUser.departmentName;
                    document.getElementById('detail-email').value = activeUser.email;
                    document.getElementById('detail-phone').value = activeUser.phone === '-' ? '' : activeUser.phone;

                    openModal(detailModal);
                });
            });

            deleteBtn.addEventListener('click', function () {
                if (!activeUser) {
                    return;
                }
                deleteForm.action = activeUser.deleteUrl;
                deleteText.textContent = uiText.confirmDeleteOne + ' "' + activeUser.name + '"?';
                detailModal.style.display = 'none';
                openModal(deleteModal);
            });

            document.querySelectorAll('.close-modal').forEach((button) => {
                button.addEventListener('click', closeAllModals);
            });

            allModals.forEach((modal) => {
                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        closeAllModals();
                    }
                });
            });

            window.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeAllModals();
                }
            });

            if (deleteForm) {
                deleteForm.addEventListener('submit', function () {
                    if (deleteSubmitBtn) {
                        deleteSubmitBtn.disabled = true;
                        deleteSubmitBtn.textContent = uiText.processing;
                    }
                });
            }

            function getVisibleUserRows() {
                return userRows;
            }

            function syncBulkSelectionUI() {
                const visibleRows = getVisibleUserRows();
                const visibleCheckboxes = visibleRows
                    .map((row) => row.querySelector('.user-select'))
                    .filter(Boolean);
                const checkedVisible = visibleCheckboxes.filter((cb) => cb.checked).length;

                if (selectAllUsers) {
                    selectAllUsers.checked = visibleCheckboxes.length > 0 && checkedVisible === visibleCheckboxes.length;
                    selectAllUsers.indeterminate = checkedVisible > 0 && checkedVisible < visibleCheckboxes.length;
                }

                const selectedTotal = userSelectBoxes.filter((cb) => cb.checked).length;
                if (selectedCount) {
                    selectedCount.textContent = selectedTotal + ' ' + uiText.selectedCount;
                }
            }

            function submitUsersFilter(delay = 0) {
                if (!usersFilterForm) return;
                window.clearTimeout(submitUsersFilter._timer);
                submitUsersFilter._timer = window.setTimeout(() => {
                    usersFilterForm.submit();
                }, delay);
            }

            if (selectAllUsers) {
                selectAllUsers.addEventListener('change', function () {
                    getVisibleUserRows().forEach((row) => {
                        const checkbox = row.querySelector('.user-select');
                        if (checkbox) {
                            checkbox.checked = selectAllUsers.checked;
                        }
                    });
                    syncBulkSelectionUI();
                });
            }

            userSelectBoxes.forEach((checkbox) => {
                checkbox.addEventListener('change', syncBulkSelectionUI);
            });

            bulkActionButtons.forEach((button) => {
                button.addEventListener('click', async function () {
                    const action = this.dataset.action;
                    const selected = userSelectBoxes.filter((cb) => cb.checked).map((cb) => cb.value);

                    if (!action || selected.length === 0 || !bulkActionForm || !bulkActionInput || !bulkSelectedIds) {
                        window.AppDialog.alert(uiText.pickOne);
                        return;
                    }

                    const confirmText = action === 'delete'
                        ? uiText.confirmDelete
                        : (action === 'restore'
                            ? uiText.confirmRestore
                            : uiText.confirmForceDelete);

                    const confirmed = await window.AppDialog.confirm(confirmText);
                    if (!confirmed) {
                        return;
                    }

                    bulkActionInput.value = action;
                    bulkSelectedIds.innerHTML = selected
                        .map((id) => '<input type="hidden" name="selected_ids[]" value="' + id + '">')
                        .join('');
                    bulkActionForm.submit();
                });
            });
            document.querySelectorAll('.js-force-delete-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const isEn = (window.localStorage.getItem('ui_lang') || 'id') === 'en';
                    const confirmed = await window.AppDialog.confirm(
                        isEn
                            ? 'Permanently delete this user? Data cannot be restored.'
                            : 'Hapus permanen user ini? Data tidak bisa dikembalikan.'
                    );
                    if (!confirmed) return;
                    form.submit();
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    submitUsersFilter(650);
                });
            }
            if (roleFilter) {
                roleFilter.addEventListener('change', function () {
                    submitUsersFilter(0);
                });
            }
            if (perPageSelect) {
                perPageSelect.addEventListener('change', function () {
                    submitUsersFilter(0);
                });
            }
            syncBulkSelectionUI();

            @if ($errors->any() && old('form_context') === 'create_student')
                openModal(createStudentModal);
            @elseif ($errors->any() && old('form_context') === 'create_staff')
                openModal(createStaffModal);
            @endif
        })();
    </script>
@endsection
