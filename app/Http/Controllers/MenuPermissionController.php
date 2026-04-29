<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\MenuPermission;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuPermissionController extends Controller
{
    private array $hiddenPermissionKeys = [
        'fitur/error-template',
        'fitur/lupa-password',
        'fitur-shared/lupa-password',
        'fitur/verifikasi-email',
        'fitur/captcha-setting',
        'fitur/notif-discord',
        'export/rekap.csv',
        'instruktur/siswa-bimbingan',
        'instruktur/catatan-akademik',
    ];

    private array $roles = [
        'superadmin',
        'admin_sekolah',
        'siswa',
        'pembimbing_pkl',
        'instruktur',
        'kajur',
        'wali_kelas',
        'kesiswaan',
        'kepsek',
    ];

    public function index(): View
    {
        $this->syncRequiredMenuPermissions();
        $roles = $this->displayRoles();

        $rawMenus = Menu::query()
            ->orderBy('id')
            ->get()
            ->reject(fn (Menu $menu) => $this->isHiddenPermissionKey($menu->key))
            ->values();

        [$menus, $groupedMenuIds] = $this->buildCanonicalMenus($rawMenus);
        $this->ensureSuperadminAlwaysAllowed($groupedMenuIds);

        $matrix = MenuPermission::query()->get()->groupBy(fn ($item) => $item->menu_id.'|'.$item->role);

        return view('menu-permissions.index', [
            'title' => 'Hak Akses Menu',
            'menus' => $menus,
            'roles' => $roles,
            'roleLabels' => $this->roleLabels(),
            'matrix' => $matrix,
            'groupedMenuIds' => $groupedMenuIds,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->syncRequiredMenuPermissions();
        $roles = $this->displayRoles();

        $allowed = $request->input('allowed', []);

        $rawMenus = Menu::query()
            ->get()
            ->reject(fn (Menu $menu) => $this->isHiddenPermissionKey($menu->key))
            ->values();

        [$menus, $groupedMenuIds] = $this->buildCanonicalMenus($rawMenus);

        foreach ($menus as $menu) {
            $primaryId = $menu->id;
            $menuIds = $groupedMenuIds[$primaryId] ?? collect([$primaryId]);
            foreach ($roles as $role) {
                $isAllowed = $role === 'superadmin'
                    ? true
                    : isset($allowed[$primaryId][$role]);
                foreach ($menuIds as $menuId) {
                    MenuPermission::query()->updateOrCreate(
                        ['menu_id' => $menuId, 'role' => $role],
                        ['is_allowed' => $isAllowed],
                    );
                }
            }
        }

        return back()->with('success', 'Hak akses menu berhasil disimpan.');
    }

    private function isDashboardKey(string $key): bool
    {
        return $key === 'dashboard' || str_starts_with($key, 'dashboard/');
    }

    private function isHiddenPermissionKey(string $key): bool
    {
        return in_array($key, $this->hiddenPermissionKeys, true);
    }

    /**
     * @return array{0:Collection<int, Menu>,1:array<int, Collection<int, int>>}
     */
    private function buildCanonicalMenus(Collection $menus): array
    {
        $groups = [];
        foreach ($menus as $menu) {
            $canonical = $this->canonicalMenuKey($menu->key);
            $groups[$canonical][] = $menu;
        }

        $result = collect();
        $groupedIds = [];

        foreach ($groups as $canonical => $items) {
            $primary = $items[0];
            $menuIds = collect($items)->pluck('id')->values();

            $primary->key = $canonical;
            $primary->name = $this->canonicalMenuName($canonical);
            $primary->url = '/'.$canonical;

            $result->push($primary);
            $groupedIds[$primary->id] = $menuIds;
        }

        return [$result->sortBy('name')->values(), $groupedIds];
    }

    private function canonicalMenuKey(string $key): string
    {
        return match (true) {
            $key === 'dashboard' || str_starts_with($key, 'dashboard/') => 'dashboard',
            $key === 'summary-report' => 'summary-report',
            $key === 'summary-report/rekap' => 'summary-report/rekap',
            $key === 'summary-report/analisis' => 'summary-report/analisis',
            in_array($key, ['fitur/audit-log', 'fitur-shared/audit-log', 'fitur-admin/audit-log'], true) => 'fitur/audit-log',
            in_array($key, ['fitur/backup-restore', 'fitur-admin/backup-restore'], true) => 'fitur/backup-restore',
            in_array($key, ['fitur/hak-akses-menu', 'fitur-admin/hak-akses-menu'], true) => 'fitur/hak-akses-menu',
            str_starts_with($key, 'fitur/import-export') || str_starts_with($key, 'fitur-admin/import-export') => 'fitur/import-export',
            in_array($key, ['fitur/laporan-grafik', 'fitur-shared/laporan-grafik', 'fitur-admin/laporan-grafik'], true) => 'fitur-shared/laporan-grafik',
            in_array($key, ['fitur/manajemen-pengguna', 'fitur-admin/manajemen-pengguna'], true) => 'fitur/manajemen-pengguna',
            in_array($key, ['fitur/setting-web', 'fitur-admin/setting-web'], true) => 'fitur/setting-web',
            $key === 'kajur/siswa' || str_starts_with($key, 'kajur/siswa/') => 'kajur/siswa',
            default => $key,
        };
    }

    private function canonicalMenuName(string $key): string
    {
        return match ($key) {
            'dashboard' => 'Dashboard',
            'summary-report' => 'Validasi Mingguan',
            'summary-report/rekap' => 'Rekap Mingguan',
            'summary-report/analisis' => 'Analisis Mingguan',
            'fitur/audit-log' => 'Log Activity',
            'fitur/backup-restore' => 'Backup & Restore',
            'fitur/hak-akses-menu' => 'Hak Akses Menu',
            'fitur/import-export' => 'Import & Export User',
            'fitur/notif-discord' => 'Notif Discord',
            'fitur-shared/laporan-grafik' => 'Laporan',
            'fitur/manajemen-pengguna' => 'Manajemen Pengguna',
            'tab/deleted' => 'Tab Deleted (Global)',
            'fitur/setting-web' => 'Setting Website',
            'kajur/siswa' => 'Monitoring Siswa',
            'absensi' => 'Absensi Harian',
            'pengajuan' => 'Pengajuan Izin/Sakit',
            'validasi' => 'Validasi Absensi',
            'validasi-pengajuan' => 'Validasi Pengajuan',
            'validasi-laporan' => 'Validasi Laporan',
            'chatbot' => 'Chatbot Asisten',
            'riwayat-catatan' => 'Riwayat Catatan',
            'profil' => 'Profil Saya',
            default => ucwords(str_replace(['-', '/'], [' ', ' '], $key)),
        };
    }

    private function roleLabels(): array
    {
        return [
            'admin_sekolah' => 'Admin Sekolah',
            'siswa' => 'Siswa',
            'pembimbing_pkl' => 'Pembimbing',
            'instruktur' => 'Instruktur PKL',
            'kajur' => 'Kajur',
            'wali_kelas' => 'Wali Kelas',
            'kesiswaan' => 'Kesiswaan',
            'kepsek' => 'Kepsek',
        ];
    }

    /**
     * @param array<int, Collection<int, int>> $groupedMenuIds
     */
    private function ensureSuperadminAlwaysAllowed(array $groupedMenuIds): void
    {
        foreach ($groupedMenuIds as $menuIds) {
            foreach ($menuIds as $menuId) {
                MenuPermission::query()->updateOrCreate(
                    ['menu_id' => $menuId, 'role' => 'superadmin'],
                    ['is_allowed' => true],
                );
            }
        }
    }

    private function syncRequiredMenuPermissions(): void
    {
        $requiredMenus = [
            ['name' => 'Dashboard', 'url' => '/dashboard', 'key' => 'dashboard'],
            ['name' => 'Profil Saya', 'url' => '/profil', 'key' => 'profil'],
            ['name' => 'Absensi Harian', 'url' => '/absensi', 'key' => 'absensi'],
            ['name' => 'Pengajuan Izin/Sakit', 'url' => '/pengajuan', 'key' => 'pengajuan'],
            ['name' => 'Riwayat Catatan', 'url' => '/riwayat-catatan', 'key' => 'riwayat-catatan'],
            ['name' => 'Validasi Absensi', 'url' => '/validasi', 'key' => 'validasi'],
            ['name' => 'Validasi Pengajuan', 'url' => '/validasi-pengajuan', 'key' => 'validasi-pengajuan'],
            ['name' => 'Validasi Laporan', 'url' => '/validasi-laporan', 'key' => 'validasi-laporan'],
            ['name' => 'Chatbot Asisten', 'url' => '/chatbot', 'key' => 'chatbot'],
            ['name' => 'Validasi Mingguan', 'url' => '/summary-report', 'key' => 'summary-report'],
            ['name' => 'Rekap Mingguan', 'url' => '/summary-report/rekap', 'key' => 'summary-report/rekap'],
            ['name' => 'Analisis Mingguan', 'url' => '/summary-report/analisis', 'key' => 'summary-report/analisis'],
            ['name' => 'Manajemen Pengguna', 'url' => '/fitur/manajemen-pengguna', 'key' => 'fitur/manajemen-pengguna'],
            ['name' => 'Tab Deleted (Global)', 'url' => '/tab/deleted', 'key' => 'tab/deleted'],
            ['name' => 'Hak Akses Menu', 'url' => '/fitur/hak-akses-menu', 'key' => 'fitur/hak-akses-menu'],
            ['name' => 'Setting Website', 'url' => '/fitur/setting-web', 'key' => 'fitur/setting-web'],
            ['name' => 'Log Activity', 'url' => '/fitur/audit-log', 'key' => 'fitur/audit-log'],
            ['name' => 'Laporan', 'url' => '/fitur-shared/laporan-grafik', 'key' => 'fitur-shared/laporan-grafik'],
            ['name' => 'Backup & Restore', 'url' => '/fitur/backup-restore', 'key' => 'fitur/backup-restore'],
            ['name' => 'Import & Export User', 'url' => '/fitur/import-export', 'key' => 'fitur/import-export'],
            ['name' => 'Monitoring Siswa', 'url' => '/kajur/siswa', 'key' => 'kajur/siswa'],
        ];

        DB::transaction(function () use ($requiredMenus): void {
            $obsoleteKeys = [
                'instruktur/siswa-bimbingan',
                'instruktur/catatan-akademik',
                'kajur/penempatan-pkl',
                'fitur/lokasi-pkl',
                'fitur-admin/lokasi-pkl',
                'fitur/notif-discord',
                'fitur/exception-monitoring',
                'fitur-shared/exception-monitoring',
                'fitur-admin/exception-monitoring',
            ];

            $obsoleteMenuIds = Menu::query()
                ->whereIn('key', $obsoleteKeys)
                ->pluck('id');

            if ($obsoleteMenuIds->isNotEmpty()) {
                MenuPermission::query()->whereIn('menu_id', $obsoleteMenuIds)->delete();
                Menu::query()->whereIn('id', $obsoleteMenuIds)->delete();
            }

            foreach ($requiredMenus as $menuData) {
                $menu = Menu::query()->updateOrCreate(
                    ['key' => $menuData['key']],
                    ['name' => $menuData['name'], 'url' => $menuData['url']]
                );

                foreach ($this->managedRoles() as $role) {
                    $existingValue = MenuPermission::query()
                        ->where('menu_id', $menu->id)
                        ->where('role', $role)
                        ->value('is_allowed');
                    MenuPermission::query()->updateOrCreate(
                        ['menu_id' => $menu->id, 'role' => $role],
                        ['is_allowed' => $role === 'superadmin'
                            ? true
                            : (bool) ($existingValue ?? $this->defaultMenuAccess($menuData['key'], $role))]
                    );
                }
            }
        });
    }

    private function defaultMenuAccess(string $menuKey, string $role): bool
    {
        if ($role === 'pembimbing_pkl' && in_array($menuKey, ['summary-report', 'summary-report/rekap'], true)) {
            return true;
        }

        // Secara default, tidak ada akses kecuali diatur manual oleh admin.
        // Superadmin sudah ditangani secara eksplisit di tempat lain sebagai selalu true.
        return false;
    }

    /**
     * @return array<int, string>
     */
    private function managedRoles(): array
    {
        $roles = array_values(array_unique(array_merge(['superadmin'], $this->roles)));
        return $roles;
    }

    /**
     * @return array<int, string>
     */
    private function displayRoles(): array
    {
        return array_values(array_filter(
            $this->roles,
            fn (string $role) => $role !== 'superadmin'
        ));
    }

}

