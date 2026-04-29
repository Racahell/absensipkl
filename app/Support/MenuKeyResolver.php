<?php

namespace App\Support;

use Illuminate\Http\Request;

class MenuKeyResolver
{
    public static function resolve(Request $request): ?string
    {
        if ($request->is('dashboard') || $request->is('dashboard/*')) {
            return 'dashboard';
        }

        if ($request->is('profil') || $request->is('profil/*')) {
            return 'profil';
        }

        if ($request->is('absensi') || $request->is('absensi/*')) {
            return 'absensi';
        }

        if ($request->is('pengajuan') || $request->is('pengajuan/*')) {
            return 'pengajuan';
        }

        if ($request->is('riwayat-catatan') || $request->is('riwayat-catatan/*')) {
            return 'riwayat-catatan';
        }

        if ($request->is('validasi-laporan') || $request->is('validasi-laporan/*') ||
            $request->is('review/laporan') || $request->is('review/laporan/*')) {
            return 'validasi-laporan';
        }

        if ($request->is('chatbot') || $request->is('chatbot/*')) {
            return 'chatbot';
        }

        if ($request->is('validasi-pengajuan') || $request->is('validasi-pengajuan/*')) {
            return 'validasi-pengajuan';
        }

        if ($request->is('validasi') || $request->is('validasi/*') ||
            $request->is('validasi/absensi') || $request->is('validasi/absensi/*')) {
            return 'validasi';
        }

        if ($request->is('fitur/manajemen-pengguna') || $request->is('fitur/manajemen-pengguna/*') ||
            $request->is('fitur-admin/manajemen-pengguna') || $request->is('fitur-admin/manajemen-pengguna/*')) {
            return 'fitur/manajemen-pengguna';
        }

        if ($request->is('kajur/siswa') || $request->is('kajur/siswa/*')) {
            return 'kajur/siswa';
        }

        if ($request->is('fitur/hak-akses-menu') || $request->is('fitur/hak-akses-menu/*') ||
            $request->is('fitur-admin/hak-akses-menu') || $request->is('fitur-admin/hak-akses-menu/*')) {
            return 'fitur/hak-akses-menu';
        }

        if ($request->is('fitur/setting-web') || $request->is('fitur/setting-web/*') ||
            $request->is('fitur-admin/setting-web') || $request->is('fitur-admin/setting-web/*')) {
            return 'fitur/setting-web';
        }

        if ($request->is('fitur/backup-restore') || $request->is('fitur/backup-restore/*') ||
            $request->is('fitur-admin/backup-restore') || $request->is('fitur-admin/backup-restore/*')) {
            return 'fitur/backup-restore';
        }

        if ($request->is('fitur/import-export') || $request->is('fitur/import-export/*') ||
            $request->is('fitur-admin/import-export') || $request->is('fitur-admin/import-export/*')) {
            return 'fitur/import-export';
        }

        if ($request->is('fitur/audit-log') || $request->is('fitur/audit-log/*') ||
            $request->is('fitur-shared/audit-log') || $request->is('fitur-shared/audit-log/*') ||
            $request->is('fitur-admin/audit-log') || $request->is('fitur-admin/audit-log/*')) {
            return 'fitur/audit-log';
        }

        if ($request->is('fitur/laporan-grafik') || $request->is('fitur/laporan-grafik/*') ||
            $request->is('fitur-shared/laporan-grafik') || $request->is('fitur-shared/laporan-grafik/*') ||
            $request->is('fitur-admin/laporan-grafik') || $request->is('fitur-admin/laporan-grafik/*')) {
            return 'fitur-shared/laporan-grafik';
        }

        if ($request->is('summary-report/analisis') || $request->is('summary-report/analisis/*')) {
            return 'summary-report/analisis';
        }

        if ($request->is('summary-report/rekap') || $request->is('summary-report/rekap/*')) {
            return 'summary-report/rekap';
        }

        if ($request->is('laporan/export/*') || $request->is('laporan/print')) {
            return 'fitur-shared/laporan-grafik';
        }

        if ($request->is('summary-report') || $request->is('summary-report/*') ||
            $request->is('summary') || $request->is('summary/*') ||
            $request->is('validasi-mingguan') || $request->is('validasi-mingguan/*')) {
            return 'summary-report';
        }

        $routeName = (string) optional($request->route())->getName();
        $byRouteName = [
            'validasi.laporan.index' => 'validasi-laporan',
            'validasi.laporan.approve' => 'validasi-laporan',
            'validasi.laporan.revisi' => 'validasi-laporan',
            'review.laporan' => 'validasi-laporan',
        ];
        if (isset($byRouteName[$routeName])) {
            return $byRouteName[$routeName];
        }

        $segments = $request->segments();
        if (count($segments) >= 2) {
            if ($segments[0] === 'fitur') {
                return 'fitur/'.$segments[1];
            }

            if ($segments[0] === 'fitur-admin') {
                return 'fitur/'.$segments[1];
            }

            if ($segments[0] === 'fitur-shared') {
                return 'fitur-shared/'.$segments[1];
            }
        }

        return null;
    }
}
