<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class FeaturePageController extends Controller
{
    public function show(string $slug): View
    {
        $pages = [
            'manajemen-pengguna' => ['title' => 'Manajemen Pengguna', 'description' => 'Kelola user, level/role tambahan, status aktif, dan proses soft delete/restore.'],
            'hak-akses-menu' => ['title' => 'Hak Akses Menu', 'description' => 'Atur akses menu per role/user dengan skema centang (checklist) berbasis database.'],
            'setting-web' => ['title' => 'Setting Website', 'description' => 'Ubah nama web, logo, alamat, manager, kontak, dan konfigurasi profil sekolah.'],
            'audit-log' => ['title' => 'Log Activity', 'description' => 'Pantau siapa melakukan apa, kapan, dari IP mana, beserta koordinat dan aksi input.'],
            'laporan-grafik' => ['title' => 'Laporan', 'description' => 'Laporan mingguan, bulanan, tahunan dengan pilihan tipe diagram (batang/line/pie).'],
            'backup-restore' => ['title' => 'Backup & Restore Sistem', 'description' => 'Backup incremental/update serta restore database sesuai hak akses.'],
            'import-export' => ['title' => 'Import & Export User', 'description' => 'Import dan export data user oleh admin/superadmin.'],
            'lupa-password' => ['title' => 'Lupa Password', 'description' => 'Reset password melalui email atau WhatsApp OTP/link verifikasi.'],
        ];

        $page = $pages[$slug] ?? [
            'title' => 'Fitur',
            'description' => 'Halaman fitur belum didefinisikan.',
        ];

        return view('features.page', [
            'title' => $page['title'],
            'featureTitle' => $page['title'],
            'featureDescription' => $page['description'],
            'featureSlug' => $slug,
        ]);
    }
}
