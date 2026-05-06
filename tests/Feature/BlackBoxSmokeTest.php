<?php

namespace Tests\Feature;

use Tests\TestCase;

class BlackBoxSmokeTest extends TestCase
{
    public function test_public_entrypoints_are_reachable_or_redirect_safely(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
        $this->get('/login')->assertStatus(200);
        $this->get('/register')->assertRedirect('/login');
        $this->get('/forgot-password')->assertStatus(200);
        $this->get('/reset-password-whatsapp')->assertStatus(200);
        $this->get('/login/otp')->assertStatus(200);
        $this->get('/login/otp/email')->assertStatus(200);
    }

    public function test_guest_is_redirected_from_protected_pages(): void
    {
        $protectedGetPaths = [
            '/dashboard',
            '/profil',
            '/absensi',
            '/absensi/check-in',
            '/absensi/check-out',
            '/pengajuan',
            '/riwayat-catatan',
            '/catatan-bimbingan',
            '/validasi',
            '/validasi-pengajuan',
            '/validasi-laporan',
            '/summary-report',
            '/summary-report/rekap',
            '/summary-report/analisis',
            '/laporan/export/excel',
            '/laporan/export/pdf',
            '/laporan/print',
            '/fitur/manajemen-pengguna',
            '/fitur/setting-web',
            '/fitur/lokasi-pkl',
            '/fitur/backup-restore',
            '/fitur/import-export',
            '/fitur/master-akademik',
            '/kajur/siswa',
            '/fitur-shared/audit-log',
            '/fitur-shared/laporan-grafik',
            '/chatbot/history',
            '/wakil-kepsek/validasi-kehadiran',
        ];

        foreach ($protectedGetPaths as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_guest_post_to_protected_actions_is_blocked(): void
    {
        $this->post('/logout')->assertRedirect('/login');
        $this->post('/absensi/check-in')->assertRedirect('/login');
        $this->post('/absensi/check-out')->assertRedirect('/login');
        $this->post('/pengajuan')->assertRedirect('/login');
        $this->post('/chatbot/message')->assertRedirect('/login');
        $this->post('/summary-report/approve')->assertRedirect('/login');
    }
}

