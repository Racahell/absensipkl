@extends('layouts.app', ['title' => $title])

@section('content')
    <div class="card">
        <h3 style="margin-top:0; color:#9a3412;">Hak Akses Menu (Checklist)</h3>
        @if (session('success'))
            <div style="padding:10px; border:1px solid #86efac; background:#f0fdf4; color:#166534; border-radius:8px; margin-bottom:10px;">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('menu-permissions.update') }}">
            @csrf
            @method('PUT')
            <div style="display:flex; flex-wrap:wrap; align-items:end; gap:10px; margin-bottom:10px;">
                <div style="min-width:220px;">
                    <label for="permission-search" style="display:block; margin-bottom:4px; color:#9a3412;">Cari Menu</label>
                    <input id="permission-search" type="text" placeholder="Ketik nama menu..." style="width:100%;">
                </div>
                <div style="min-width:220px;">
                    <label for="role-filter" style="display:block; margin-bottom:4px; color:#9a3412;">Filter Role</label>
                    <select id="role-filter" style="width:100%;">
                        <option value="all">Semua Role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}">{{ $roleLabels[$role] ?? $role }}</option>
                        @endforeach
                    </select>
                </div>
                <label style="display:flex; align-items:center; gap:6px; cursor:pointer; color:#7c2d12;">
                    <input type="checkbox" id="select-all-permissions">
                    Pilih Semua
                </label>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:14px;">
                    <thead>
                        <tr style="background:#fff7ed;">
                            <th style="padding:8px; border:1px solid #fdba74;" data-col="menu">Menu</th>
                            @foreach ($roles as $role)
                                <th style="padding:8px; border:1px solid #fdba74;" data-role-col="{{ $role }}">
                                    <div style="display:flex; flex-direction:column; align-items:center; gap:4px;">
                                        <span>{{ $roleLabels[$role] ?? $role }}</span>
                                        <label style="display:flex; align-items:center; gap:4px; font-size:12px; color:#9a3412; cursor:pointer;">
                                            <input type="checkbox" class="select-role" data-role="{{ $role }}">
                                            Semua
                                        </label>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($menus as $menu)
                            <tr data-menu-name="{{ strtolower($menu->name) }}">
                                <td style="padding:8px; border:1px solid #fed7aa;" data-col="menu">{{ $menu->name }}</td>
                                @foreach ($roles as $role)
                                    @php
                                        $ids = $groupedMenuIds[$menu->id] ?? collect([$menu->id]);
                                        $rolePermissions = collect($ids)->map(function ($menuId) use ($matrix, $role) {
                                            $mapKey = $menuId.'|'.$role;
                                            return optional(($matrix[$mapKey] ?? collect())->first())->is_allowed;
                                        })->filter(fn ($value) => $value !== null);

                                        $checked = $rolePermissions->isEmpty()
                                            ? false
                                            : $rolePermissions->contains(fn ($value) => (bool) $value);
                                    @endphp
                                    <td style="text-align:center; padding:8px; border:1px solid #fed7aa;" data-role-col="{{ $role }}">
                                        <input
                                            type="checkbox"
                                            class="perm-checkbox"
                                            data-role="{{ $role }}"
                                            name="allowed[{{ $menu->id }}][{{ $role }}]"
                                            value="1"
                                            {{ $checked ? 'checked' : '' }}
                                        >
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button class="logout-btn" type="submit" style="margin-top:12px;">Simpan Hak Akses</button>
        </form>
    </div>

    <script>
        (function () {
            const allCheckbox = document.getElementById('select-all-permissions');
            const searchInput = document.getElementById('permission-search');
            const roleFilter = document.getElementById('role-filter');
            const roleToggles = Array.from(document.querySelectorAll('.select-role'));
            const permissionCheckboxes = Array.from(document.querySelectorAll('.perm-checkbox'));
            const tableRows = Array.from(document.querySelectorAll('tbody tr'));
            const allRoleColumns = Array.from(document.querySelectorAll('[data-role-col]'));
            const menuColumn = Array.from(document.querySelectorAll('[data-col="menu"]'));

            function getVisiblePermissionCheckboxes() {
                return permissionCheckboxes.filter((item) => item.offsetParent !== null);
            }

            function syncStates() {
                const visiblePermissions = getVisiblePermissionCheckboxes();
                const total = visiblePermissions.length;
                const checkedCount = visiblePermissions.filter((item) => item.checked).length;

                allCheckbox.checked = total > 0 && checkedCount === total;
                allCheckbox.indeterminate = checkedCount > 0 && checkedCount < total;

                roleToggles.forEach((roleToggle) => {
                    const role = roleToggle.dataset.role;
                    const roleItems = visiblePermissions.filter((item) => item.dataset.role === role);
                    const roleCheckedCount = roleItems.filter((item) => item.checked).length;

                    roleToggle.checked = roleItems.length > 0 && roleCheckedCount === roleItems.length;
                    roleToggle.indeterminate = roleCheckedCount > 0 && roleCheckedCount < roleItems.length;
                });
            }

            function applySearchFilter() {
                const keyword = (searchInput.value || '').toLowerCase().trim();
                tableRows.forEach((row) => {
                    const menuName = row.dataset.menuName || '';
                    row.style.display = keyword === '' || menuName.includes(keyword) ? '' : 'none';
                });
            }

            function applyRoleFilter() {
                const selectedRole = roleFilter.value;
                const showAllRoles = selectedRole === 'all';

                allRoleColumns.forEach((column) => {
                    const columnRole = column.getAttribute('data-role-col');
                    const isVisible = showAllRoles || columnRole === selectedRole;
                    column.style.display = isVisible ? '' : 'none';
                });

                menuColumn.forEach((column) => {
                    column.style.display = '';
                });

                roleToggles.forEach((toggle) => {
                    const role = toggle.dataset.role;
                    const wrapper = toggle.closest('label');
                    if (wrapper) {
                        wrapper.style.display = showAllRoles || role === selectedRole ? '' : 'none';
                    }
                });
            }

            allCheckbox.addEventListener('change', function () {
                getVisiblePermissionCheckboxes().forEach((item) => {
                    item.checked = allCheckbox.checked;
                });
                syncStates();
            });

            roleToggles.forEach((roleToggle) => {
                roleToggle.addEventListener('change', function () {
                    const role = roleToggle.dataset.role;
                    permissionCheckboxes.forEach((item) => {
                        if (item.dataset.role === role) {
                            item.checked = roleToggle.checked;
                        }
                    });
                    syncStates();
                });
            });

            permissionCheckboxes.forEach((item) => {
                item.addEventListener('change', syncStates);
            });

            searchInput.addEventListener('input', function () {
                applySearchFilter();
                syncStates();
            });

            roleFilter.addEventListener('change', function () {
                applyRoleFilter();
                syncStates();
            });

            applySearchFilter();
            applyRoleFilter();
            syncStates();
        })();
    </script>
@endsection
