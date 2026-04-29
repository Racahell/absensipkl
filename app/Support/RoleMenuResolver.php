<?php

namespace App\Support;

use App\Models\Menu;
use App\Models\MenuPermission;
use Illuminate\Support\Collection;

class RoleMenuResolver
{
    /**
     * @return array<int, array{key:string,name:string,url:string}>
     */
    public function forRole(string $role): array
    {
        $normalizedRole = $this->normalizeRole($role);

        $query = Menu::query()->select(['key', 'name', 'url']);
        if ($normalizedRole !== 'superadmin') {
            $query->whereIn('id', function ($subQuery) use ($normalizedRole): void {
                $subQuery->from((new MenuPermission())->getTable())
                    ->select('menu_id')
                    ->where('role', $normalizedRole)
                    ->where('is_allowed', true);
            });
        }

        $menus = $query->get()
            ->map(function (Menu $menu) use ($normalizedRole): array {
                $canonicalKey = $this->canonicalKey((string) $menu->key);
                return [
                    'key' => $canonicalKey,
                    'name' => $this->displayName($canonicalKey, $normalizedRole, (string) $menu->name),
                    'url' => '/'.$canonicalKey,
                ];
            })
            ->reject(fn (array $item): bool => in_array($item['key'], $this->hiddenKeys(), true))
            ->unique('key')
            ->values();

        return $menus
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * @param array<int, array{key:string,name:string,url:string}> $menus
     * @return array{key:string,name:string,url:string}|null
     */
    public function findByQuestion(array $menus, string $normalizedQuestion): ?array
    {
        $normalizedQuestion = strtolower(trim($normalizedQuestion));
        if ($normalizedQuestion === '') {
            return null;
        }

        foreach ($menus as $menu) {
            $name = strtolower(trim((string) $menu['name']));
            $key = strtolower(trim((string) $menu['key']));
            if (($name !== '' && str_contains($normalizedQuestion, $name))
                || ($key !== '' && str_contains($normalizedQuestion, $key))) {
                return $menu;
            }
        }

        return null;
    }

    private function normalizeRole(string $role): string
    {
        return match ($role) {
            'owner' => 'kepsek',
            'operator' => 'admin_sekolah',
            default => $role,
        };
    }

    private function canonicalKey(string $key): string
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
            default => $key,
        };
    }

    private function displayName(string $key, string $role, string $fallback): string
    {
        if ($role === 'instruktur' && $key === 'summary-report/analisis') {
            return 'Monitoring Progres';
        }

        $map = [
            'dashboard' => 'Dashboard',
            'profil' => 'Profil Saya',
            'absensi' => 'Absensi Harian',
            'pengajuan' => 'Pengajuan Izin/Sakit',
            'riwayat-catatan' => 'Riwayat Catatan',
            'validasi' => 'Validasi Absensi',
            'validasi-pengajuan' => 'Validasi Pengajuan',
            'validasi-laporan' => 'Validasi Laporan',
            'chatbot' => 'Chatbot Asisten',
            'summary-report' => 'Validasi Mingguan',
            'summary-report/rekap' => 'Rekap Mingguan',
            'summary-report/analisis' => 'Analisis Mingguan',
            'fitur/manajemen-pengguna' => 'Manajemen Pengguna',
            'fitur/hak-akses-menu' => 'Hak Akses Menu',
            'fitur/setting-web' => 'Setting Website',
            'fitur/audit-log' => 'Log Activity',
            'fitur/backup-restore' => 'Backup & Restore',
            'fitur/import-export' => 'Import & Export User',
            'fitur-shared/laporan-grafik' => 'Laporan',
            'tab/deleted' => 'Tab Deleted (Global)',
        ];

        return $map[$key] ?? $fallback;
    }

    /**
     * @return array<int, string>
     */
    private function hiddenKeys(): array
    {
        return [
            'fitur/error-template',
            'fitur/lupa-password',
            'fitur-shared/lupa-password',
            'fitur/verifikasi-email',
            'fitur/captcha-setting',
            'export/rekap.csv',
        ];
    }
}
