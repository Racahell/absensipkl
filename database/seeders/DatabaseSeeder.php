<?php

namespace Database\Seeders;

use App\Models\PklLocation;
use App\Models\User;
use App\Models\Menu;
use App\Models\MenuPermission;
use App\Models\AppSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'nis' => '10000001',
                'nuptk' => '20000001',
                'role' => 'superadmin',
                'class_name' => null,
                'department_name' => null,
                'email' => 'superadmin@local.test',
                'phone' => '628111000001',
                'password' => 'superadmin',
            ],
            [
                'name' => 'Admin Sekolah',
                'nis' => '10000002',
                'nuptk' => '20000002',
                'role' => 'admin_sekolah',
                'class_name' => null,
                'department_name' => null,
                'email' => 'adminsekolah@local.test',
                'phone' => '628111000002',
                'password' => 'admin_sekolah',
            ],
            [
                'name' => 'Siswa PKL',
                'nis' => '10000003',
                'nuptk' => null,
                'role' => 'siswa',
                'class_name' => 'XII RPL 1',
                'department_name' => 'RPL',
                'email' => 'siswa@local.test',
                'phone' => '628111000003',
                'password' => 'siswa',
            ],
            [
                'name' => 'Pembimbing PKL',
                'nis' => '10000004',
                'nuptk' => '20000004',
                'role' => 'pembimbing_pkl',
                'class_name' => null,
                'department_name' => null,
                'email' => 'pembimbing@local.test',
                'phone' => '628111000004',
                'password' => 'pembimbing_pkl',
            ],
            [
                'name' => 'Instruktur Sekolah',
                'nis' => '10000005',
                'nuptk' => '20000005',
                'role' => 'instruktur',
                'class_name' => null,
                'department_name' => null,
                'email' => 'instruktur@local.test',
                'phone' => '628111000005',
                'password' => 'instruktur',
            ],
            [
                'name' => 'Ketua Jurusan',
                'nis' => '10000006',
                'nuptk' => '20000006',
                'role' => 'kajur',
                'class_name' => null,
                'department_name' => 'RPL',
                'email' => 'kajur@local.test',
                'phone' => '628111000006',
                'password' => 'kajur',
            ],
            [
                'name' => 'Wali Kelas',
                'nis' => '10000007',
                'nuptk' => '20000007',
                'role' => 'wali_kelas',
                'class_name' => 'XII RPL 1',
                'department_name' => 'RPL',
                'email' => 'walikelas@local.test',
                'phone' => '628111000007',
                'password' => 'wali_kelas',
            ],
            [
                'name' => 'Kesiswaan',
                'nis' => '10000008',
                'nuptk' => '20000008',
                'role' => 'kesiswaan',
                'class_name' => null,
                'department_name' => null,
                'email' => 'kesiswaan@local.test',
                'phone' => '628111000008',
                'password' => 'kesiswaan',
            ],
            [
                'name' => 'Kepala Sekolah',
                'nis' => '10000009',
                'nuptk' => '20000009',
                'role' => 'kepsek',
                'class_name' => null,
                'department_name' => null,
                'email' => 'kepsek@local.test',
                'phone' => '628111000009',
                'password' => 'kepsek',
            ],
        ];

        foreach ($users as $account) {
            User::updateOrCreate(
                ['nis' => $account['nis']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'nuptk' => $account['nuptk'],
                    'class_name' => $account['class_name'],
                    'department_name' => $account['department_name'],
                    'email' => $account['email'],
                    'phone' => $account['phone'],
                    'password' => Hash::make($account['password']),
                    'email_verified_at' => now(),
                ]
            );
        }

        $location = PklLocation::updateOrCreate(
            ['name' => 'PT Permata Harapan Digital'],
            [
                'address' => 'Jl. Contoh Industri No. 10, Jakarta',
                'latitude' => -6.2000000,
                'longitude' => 106.8166660,
                'radius_meters' => 100,
                'ip_reference' => '36.85.120.10',
                'pembimbing_user_id' => User::where('nis', '10000004')->value('id'),
                'instruktur_user_id' => User::where('nis', '10000005')->value('id'),
                'kajur_user_id' => User::where('nis', '10000006')->value('id'),
            ]
        );

        User::whereIn('nis', ['10000003', '10000004', '10000005', '10000006'])
            ->update(['pkl_location_id' => $location->id]);

        $menus = [
            ['name' => 'Dashboard', 'url' => '/dashboard'],
            ['name' => 'Profil Saya', 'url' => '/profil'],
            ['name' => 'Absensi Harian', 'url' => '/absensi'],
            ['name' => 'Pengajuan Izin/Sakit', 'url' => '/pengajuan'],
            ['name' => 'Validasi', 'url' => '/validasi'],
            ['name' => 'Validasi Laporan', 'url' => '/validasi-laporan'],
            ['name' => 'Validasi Pengajuan', 'url' => '/validasi-pengajuan'],
            ['name' => 'Manajemen Pengguna', 'url' => '/fitur/manajemen-pengguna'],
            ['name' => 'Tab Deleted (Global)', 'url' => '/tab/deleted'],
            ['name' => 'Hak Akses Menu', 'url' => '/fitur/hak-akses-menu'],
            ['name' => 'Setting Website', 'url' => '/fitur/setting-web'],
            ['name' => 'Lokasi PKL', 'url' => '/fitur/lokasi-pkl'],
            ['name' => 'Log Activity', 'url' => '/fitur/audit-log'],
            ['name' => 'Exception Monitoring', 'url' => '/fitur/exception-monitoring'],
            ['name' => 'Laporan', 'url' => '/fitur-shared/laporan-grafik'],
            ['name' => 'Laporan Superadmin', 'url' => '/fitur/laporan-grafik'],
            ['name' => 'Backup Restore', 'url' => '/fitur/backup-restore'],
            ['name' => 'Import & Export Siswa', 'url' => '/fitur/import-export'],
            ['name' => 'Lupa Password', 'url' => '/fitur-shared/lupa-password'],
            ['name' => 'Log Activity Shared', 'url' => '/fitur-shared/audit-log'],
            ['name' => 'Exception Monitoring Shared', 'url' => '/fitur-shared/exception-monitoring'],
            ['name' => 'Admin Manajemen Pengguna', 'url' => '/fitur-admin/manajemen-pengguna'],
            ['name' => 'Admin Hak Akses Menu', 'url' => '/fitur-admin/hak-akses-menu'],
            ['name' => 'Admin Setting Website', 'url' => '/fitur-admin/setting-web'],
            ['name' => 'Admin Lokasi PKL', 'url' => '/fitur-admin/lokasi-pkl'],
            ['name' => 'Admin Laporan', 'url' => '/fitur-admin/laporan-grafik'],
            ['name' => 'Admin Backup Restore', 'url' => '/fitur-admin/backup-restore'],
            ['name' => 'Admin Import & Export Siswa', 'url' => '/fitur-admin/import-export'],
            ['name' => 'Admin Log Activity', 'url' => '/fitur-admin/audit-log'],
            ['name' => 'Admin Exception Monitoring', 'url' => '/fitur-admin/exception-monitoring'],
            ['name' => 'Import Export Siswa Real', 'url' => '/fitur/import-export/users/export'],
            ['name' => 'Laporan Shared Real', 'url' => '/fitur-shared/laporan-grafik'],
        ];

        $roles = ['superadmin', 'admin_sekolah', 'siswa', 'pembimbing_pkl', 'instruktur', 'kajur', 'wali_kelas', 'kesiswaan', 'kepsek'];

        foreach ($menus as $menuItem) {
            $menu = Menu::updateOrCreate(
                ['key' => ltrim($menuItem['url'], '/')],
                ['name' => $menuItem['name'], 'url' => $menuItem['url']]
            );

            foreach ($roles as $role) {
                MenuPermission::updateOrCreate(
                    ['menu_id' => $menu->id, 'role' => $role],
                    ['is_allowed' => true]
                );
            }
        }

        $deletedTabMenuId = Menu::query()->where('key', 'tab/deleted')->value('id');
        if ($deletedTabMenuId) {
            foreach ($roles as $role) {
                MenuPermission::query()->updateOrCreate(
                    ['menu_id' => $deletedTabMenuId, 'role' => $role],
                    ['is_allowed' => $role === 'superadmin']
                );
            }
        }

        $defaultSettings = [
            'app_name' => 'Permata Harapan',
            'app_tagline' => 'Absensi & Monitoring PKL',
            'school_address' => 'Jl. Contoh Industri No. 10, Jakarta',
            'school_manager' => 'Kepala Sekolah',
            'school_contact' => '0812-0000-0000',
            'app_logo' => 'image/download.png',
            'app_favicon' => 'image/download.png',
            'footer_text' => 'Permata Harapan',
            'footer_link_1_label' => 'Privacy',
            'footer_link_1_url' => '#',
            'footer_link_2_label' => 'Terms',
            'footer_link_2_url' => '#',
            'footer_link_3_label' => 'Support',
            'footer_link_3_url' => '#',
            'discord_webhook_url' => null,
            'attendance_timezone' => 'Asia/Jakarta',
            'attendance_checkin_start' => '06:00',
            'attendance_checkin_end' => '10:00',
            'attendance_checkout_start' => '12:00',
            'attendance_checkout_end' => '23:59',
        ];

        foreach ($defaultSettings as $key => $value) {
            AppSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
