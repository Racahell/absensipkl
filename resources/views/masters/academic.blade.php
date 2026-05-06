@extends('layouts.app', ['title' => $title])

@section('content')
<style>
    .master-control-input,
    .master-control-select,
    .master-control-btn {
        height: 40px;
        box-sizing: border-box;
    }

    .master-tabs-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }
    .master-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 0;
    }
    .master-tab-btn {
        border: 1px solid var(--line);
        background: var(--accent-soft);
        color: var(--accent-text);
        border-radius: 999px;
        padding: 8px 14px;
        cursor: pointer;
        font-weight: 600;
        line-height: 1;
        min-height: 34px;
    }
    .master-tab-btn.active {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }
    .master-state-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 0;
    }
    .master-state-tab {
        display: inline-flex;
        align-items: center;
        border: 1px solid var(--line);
        background: var(--accent-soft);
        color: var(--accent-text);
        border-radius: 999px;
        padding: 8px 14px;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
        line-height: 1;
        min-height: 34px;
    }
    .master-state-tab.active {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }
    .master-tab-panel { display: none; }
    .master-tab-panel.active { display: block; }
    .master-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 2000;
        background: rgba(17, 24, 39, 0.55);
        align-items: center;
        justify-content: center;
        padding: 14px;
    }
    .master-modal-box {
        width: min(560px, 100%);
        background: #fff;
        border: 1px solid #fdba74;
        border-radius: 12px;
        padding: 14px;
    }
    .master-modal-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        margin-top: 10px;
    }
</style>

<div class="card">
    <h3 class="mt-0 text-primary">Tambah Jurusan & Kelas</h3>
    @if (session('success'))
        <div class="alert alert-success mb-10">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-error mb-10">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error mb-10">{{ $errors->first() }}</div>
    @endif

    <div class="master-tabs-row">
        <div class="master-tabs">
            <button type="button" class="master-tab-btn active" data-target="tab-department">Tambah Jurusan</button>
            <button type="button" class="master-tab-btn" data-target="tab-class">Tambah Kelas</button>
        </div>
        <div class="master-state-tabs" id="state-tabs-department">
            <a href="{{ route('fitur.master-akademik', array_merge(request()->query(), ['department_tab' => 'active'])) }}" class="master-state-tab {{ ($departmentTab ?? 'active') === 'active' ? 'active' : '' }}">
                Aktif ({{ (int) ($departmentActiveCount ?? 0) }})
            </a>
            @if (($hasDeletedTabAccess ?? false) && ($supportsDeletedTab ?? false))
                <a href="{{ route('fitur.master-akademik', array_merge(request()->query(), ['department_tab' => 'deleted'])) }}" class="master-state-tab {{ ($departmentTab ?? 'active') === 'deleted' ? 'active' : '' }}">
                    Terhapus ({{ (int) ($departmentDeletedCount ?? 0) }})
                </a>
            @endif
        </div>
        <div class="master-state-tabs" id="state-tabs-class" style="display:none;">
            <a href="{{ route('fitur.master-akademik', array_merge(request()->query(), ['class_tab' => 'active'])) }}" class="master-state-tab {{ ($classTab ?? 'active') === 'active' ? 'active' : '' }}">
                Aktif ({{ (int) ($classActiveCount ?? 0) }})
            </a>
            @if (($hasDeletedTabAccess ?? false) && ($supportsDeletedTab ?? false))
                <a href="{{ route('fitur.master-akademik', array_merge(request()->query(), ['class_tab' => 'deleted'])) }}" class="master-state-tab {{ ($classTab ?? 'active') === 'deleted' ? 'active' : '' }}">
                    Terhapus ({{ (int) ($classDeletedCount ?? 0) }})
                </a>
            @endif
        </div>
    </div>

    <div id="tab-department" class="master-tab-panel active">
        <div class="flex gap-8 wrap mb-10" style="align-items:end;">
            <form method="GET" action="{{ route('fitur.master-akademik') }}" class="flex gap-8 wrap" style="align-items:end; margin:0;">
                <input type="hidden" name="class_per_page" value="{{ (int) ($classPerPage ?? 10) }}">
                <input type="hidden" name="department_tab" value="{{ $departmentTab ?? 'active' }}">
                <input type="hidden" name="class_tab" value="{{ $classTab ?? 'active' }}">
                <div style="min-width:170px;">
                    <label for="department-per-page">Tampilkan</label>
                    <select id="department-per-page" name="department_per_page" class="master-control-select" onchange="this.form.submit()">
                        @foreach (($perPageOptions ?? [10,20,50,100]) as $opt)
                            <option value="{{ $opt }}" {{ (int) ($departmentPerPage ?? 10) === (int) $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if (($departmentTab ?? 'active') === 'active')
                <form method="POST" action="{{ route('masters.department.store') }}" class="flex gap-8 wrap" style="align-items:end; margin:0;">
                    @csrf
                    <div style="min-width:260px;">
                        <label for="department-name">Nama Jurusan</label>
                        <input id="department-name" name="name" class="master-control-input" required>
                    </div>
                    <button type="submit" class="master-control-btn">Tambah Jurusan</button>
                </form>
            @endif
        </div>

        <div class="table-wrap mt-10">
            <table class="w-full">
                <thead>
                    <tr>
                        <th style="width:70px;">No</th>
                        <th>Jurusan</th>
                        <th style="width:160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departments as $i => $d)
                        <tr>
                            <td>{{ ($departments->firstItem() ?? 0) + $i }}</td>
                            <td>{{ $d->name }}</td>
                            <td>
                                @if (($departmentTab ?? 'active') === 'active')
                                    <button
                                        type="button"
                                        class="btn btn-ghost js-department-detail"
                                        data-id="{{ $d->id }}"
                                        data-name="{{ $d->name }}"
                                        data-update-url="{{ route('masters.department.update', $d) }}"
                                        data-delete-url="{{ route('masters.department.destroy', $d) }}"
                                    >
                                        Detail
                                    </button>
                                @else
                                    <form method="POST" action="{{ route('masters.department.restore', $d->id) }}" style="display:inline;">
                                        @csrf
                                        <button class="btn-success" type="submit">Restore</button>
                                    </form>
                                    <form method="POST" action="{{ route('masters.department.force-delete', $d->id) }}" style="display:inline;" class="js-force-delete-department">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger" type="submit">Delete Permanent</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3">Belum ada jurusan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($departments, 'links'))
            <div class="mt-10">{{ $departments->links() }}</div>
        @endif
    </div>

    <div id="tab-class" class="master-tab-panel">
        <div class="flex gap-8 wrap mb-10" style="align-items:end;">
            <form method="GET" action="{{ route('fitur.master-akademik') }}" class="flex gap-8 wrap" style="align-items:end; margin:0;">
                <input type="hidden" name="department_per_page" value="{{ (int) ($departmentPerPage ?? 10) }}">
                <input type="hidden" name="department_tab" value="{{ $departmentTab ?? 'active' }}">
                <input type="hidden" name="class_tab" value="{{ $classTab ?? 'active' }}">
                <div style="min-width:170px;">
                    <label for="class-per-page">Tampilkan</label>
                    <select id="class-per-page" name="class_per_page" onchange="this.form.submit()">
                        @foreach (($perPageOptions ?? [10,20,50,100]) as $opt)
                            <option value="{{ $opt }}" {{ (int) ($classPerPage ?? 10) === (int) $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if (($classTab ?? 'active') === 'active')
                <form method="POST" action="{{ route('masters.class.store') }}" class="flex gap-8 wrap" style="align-items:end; margin:0;">
                    @csrf
                    <div style="min-width:260px;">
                        <label for="class-name">Nama Kelas</label>
                        <input id="class-name" name="name" required>
                    </div>
                    <div style="min-width:220px;">
                        <label for="class-department-id">Jurusan</label>
                        <select id="class-department-id" name="department_id">
                            <option value="">-</option>
                            @foreach (($allDepartments ?? $departments) as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit">Tambah Kelas</button>
                </form>
            @endif
        </div>

        <div class="table-wrap mt-10">
            <table class="w-full">
                <thead>
                    <tr>
                        <th style="width:70px;">No</th>
                        <th>Kelas</th>
                        <th>Jurusan</th>
                        <th style="width:160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classes as $i => $c)
                        <tr>
                            <td>{{ ($classes->firstItem() ?? 0) + $i }}</td>
                            <td>{{ $c->name }}</td>
                            <td>{{ $c->department?->name ?? '-' }}</td>
                            <td>
                                @if (($classTab ?? 'active') === 'active')
                                    <button
                                        type="button"
                                        class="btn btn-ghost js-class-detail"
                                        data-id="{{ $c->id }}"
                                        data-name="{{ $c->name }}"
                                        data-department-id="{{ (int) ($c->department_id ?? 0) }}"
                                        data-update-url="{{ route('masters.class.update', $c) }}"
                                        data-delete-url="{{ route('masters.class.destroy', $c) }}"
                                    >
                                        Detail
                                    </button>
                                @else
                                    <form method="POST" action="{{ route('masters.class.restore', $c->id) }}" style="display:inline;">
                                        @csrf
                                        <button class="btn-success" type="submit">Restore</button>
                                    </form>
                                    <form method="POST" action="{{ route('masters.class.force-delete', $c->id) }}" style="display:inline;" class="js-force-delete-class">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger" type="submit">Delete Permanent</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">Belum ada kelas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($classes, 'links'))
            <div class="mt-10">{{ $classes->links() }}</div>
        @endif
    </div>
</div>

<div id="department-detail-modal" class="master-modal-overlay">
    <div class="master-modal-box">
        <h4 class="mt-0 text-primary">Detail Jurusan</h4>
        <form id="department-detail-form" method="POST">
            @csrf
            @method('PUT')
            <label for="department-detail-name">Nama Jurusan</label>
            <input id="department-detail-name" name="name" required>
            <div class="master-modal-actions">
                <button type="submit" class="btn btn-ghost">Edit</button>
                <button type="button" class="btn btn-danger" id="department-delete-btn">Hapus</button>
                <button type="button" class="btn btn-ghost js-close-modal">Batal</button>
            </div>
        </form>
        <form id="department-delete-form" method="POST" style="display:none;">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>

<div id="class-detail-modal" class="master-modal-overlay">
    <div class="master-modal-box">
        <h4 class="mt-0 text-primary">Detail Kelas</h4>
        <form id="class-detail-form" method="POST">
            @csrf
            @method('PUT')
            <label for="class-detail-name">Nama Kelas</label>
            <input id="class-detail-name" name="name" required>
            <label for="class-detail-department-id">Jurusan</label>
            <select id="class-detail-department-id" name="department_id">
                <option value="">-</option>
                @foreach (($allDepartments ?? $departments) as $d)
                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                @endforeach
            </select>
            <div class="master-modal-actions">
                <button type="submit" class="btn btn-ghost">Edit</button>
                <button type="button" class="btn btn-danger" id="class-delete-btn">Hapus</button>
                <button type="button" class="btn btn-ghost js-close-modal">Batal</button>
            </div>
        </form>
        <form id="class-delete-form" method="POST" style="display:none;">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>

<script>
    (function () {
        const tabButtons = Array.from(document.querySelectorAll('.master-tab-btn'));
        const tabPanels = Array.from(document.querySelectorAll('.master-tab-panel'));
        tabButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-target');
                tabButtons.forEach((btn) => btn.classList.toggle('active', btn === button));
                tabPanels.forEach((panel) => panel.classList.toggle('active', panel.id === targetId));
                const deptTabs = document.getElementById('state-tabs-department');
                const classTabs = document.getElementById('state-tabs-class');
                if (deptTabs && classTabs) {
                    const isDept = targetId === 'tab-department';
                    deptTabs.style.display = isDept ? 'flex' : 'none';
                    classTabs.style.display = isDept ? 'none' : 'flex';
                }
            });
        });

        const departmentModal = document.getElementById('department-detail-modal');
        const classModal = document.getElementById('class-detail-modal');
        const departmentForm = document.getElementById('department-detail-form');
        const departmentDeleteForm = document.getElementById('department-delete-form');
        const classForm = document.getElementById('class-detail-form');
        const classDeleteForm = document.getElementById('class-delete-form');
        const departmentNameInput = document.getElementById('department-detail-name');
        const classNameInput = document.getElementById('class-detail-name');
        const classDepartmentSelect = document.getElementById('class-detail-department-id');

        function closeModal(modal) {
            if (!modal) return;
            modal.style.display = 'none';
        }

        document.querySelectorAll('.js-close-modal').forEach((btn) => {
            btn.addEventListener('click', () => {
                closeModal(departmentModal);
                closeModal(classModal);
            });
        });

        if (departmentModal) {
            departmentModal.addEventListener('click', (event) => {
                if (event.target === departmentModal) closeModal(departmentModal);
            });
        }
        if (classModal) {
            classModal.addEventListener('click', (event) => {
                if (event.target === classModal) closeModal(classModal);
            });
        }

        document.querySelectorAll('.js-department-detail').forEach((button) => {
            button.addEventListener('click', () => {
                if (!departmentModal || !departmentForm || !departmentDeleteForm || !departmentNameInput) return;
                departmentForm.action = button.dataset.updateUrl || '';
                departmentDeleteForm.action = button.dataset.deleteUrl || '';
                departmentNameInput.value = button.dataset.name || '';
                departmentModal.style.display = 'flex';
            });
        });

        document.querySelectorAll('.js-class-detail').forEach((button) => {
            button.addEventListener('click', () => {
                if (!classModal || !classForm || !classDeleteForm || !classNameInput || !classDepartmentSelect) return;
                classForm.action = button.dataset.updateUrl || '';
                classDeleteForm.action = button.dataset.deleteUrl || '';
                classNameInput.value = button.dataset.name || '';
                classDepartmentSelect.value = String(button.dataset.departmentId || '');
                classModal.style.display = 'flex';
            });
        });

        const departmentDeleteBtn = document.getElementById('department-delete-btn');
        if (departmentDeleteBtn) {
            departmentDeleteBtn.addEventListener('click', async () => {
                if (!departmentDeleteForm) return;
                const isEn = (window.localStorage.getItem('ui_lang') || 'id') === 'en';
                const confirmed = await window.AppDialog.confirm(
                    isEn ? 'Delete this department?' : 'Hapus jurusan ini?'
                );
                if (!confirmed) return;
                departmentDeleteForm.submit();
            });
        }

        const classDeleteBtn = document.getElementById('class-delete-btn');
        if (classDeleteBtn) {
            classDeleteBtn.addEventListener('click', async () => {
                if (!classDeleteForm) return;
                const isEn = (window.localStorage.getItem('ui_lang') || 'id') === 'en';
                const confirmed = await window.AppDialog.confirm(
                    isEn ? 'Delete this class?' : 'Hapus kelas ini?'
                );
                if (!confirmed) return;
                classDeleteForm.submit();
            });
        }

        document.querySelectorAll('.js-force-delete-department, .js-force-delete-class').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const isEn = (window.localStorage.getItem('ui_lang') || 'id') === 'en';
                const confirmed = await window.AppDialog.confirm(
                    isEn
                        ? 'Permanently delete this data? It cannot be restored.'
                        : 'Hapus permanen data ini? Tidak bisa dikembalikan.'
                );
                if (!confirmed) return;
                form.submit();
            });
        });
    })();
</script>
@endsection
