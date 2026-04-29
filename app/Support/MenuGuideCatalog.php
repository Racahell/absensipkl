<?php

namespace App\Support;

class MenuGuideCatalog
{
    /**
     * @return array{
     *  purpose_id:string,
     *  purpose_en:string,
     *  steps_id:array<int, string>,
     *  steps_en:array<int, string>
     * }|null
     */
    public static function get(string $menuKey): ?array
    {
        $map = self::all();
        return $map[$menuKey] ?? null;
    }

    /**
     * @return array<string, array{
     *  purpose_id:string,
     *  purpose_en:string,
     *  steps_id:array<int, string>,
     *  steps_en:array<int, string>
     * }>
     */
    public static function all(): array
    {
        return [
            'dashboard' => [
                'purpose_id' => 'Melihat ringkasan data utama sesuai role Anda, termasuk status pending dan shortcut.',
                'purpose_en' => 'View role-based main summary, including pending statuses and shortcuts.',
                'steps_id' => ['Buka Dashboard.', 'Gunakan kartu/ringkasan untuk masuk ke menu tindak lanjut.'],
                'steps_en' => ['Open Dashboard.', 'Use summary cards to jump into follow-up menus.'],
            ],
            'profil' => [
                'purpose_id' => 'Mengelola data akun pribadi, email, password, dan foto profil.',
                'purpose_en' => 'Manage personal account data, email, password, and profile photo.',
                'steps_id' => ['Buka Profil Saya.', 'Ubah data yang diperlukan.', 'Klik simpan dan cek notifikasi.'],
                'steps_en' => ['Open My Profile.', 'Update required fields.', 'Click save and check notification.'],
            ],
            'absensi' => [
                'purpose_id' => 'Mencatat check-in/check-out harian siswa sesuai aturan waktu/lokasi.',
                'purpose_en' => 'Record student daily check-in/check-out with time/location rules.',
                'steps_id' => ['Check-in di jam masuk.', 'Check-out + isi laporan harian.', 'Pantau status validasi.'],
                'steps_en' => ['Check in during allowed time.', 'Check out + fill daily report.', 'Monitor validation status.'],
            ],
            'pengajuan' => [
                'purpose_id' => 'Mengajukan izin/sakit beserta alasan dan bukti.',
                'purpose_en' => 'Submit leave/sick requests with reason and evidence.',
                'steps_id' => ['Buka Pengajuan.', 'Isi tanggal/jenis/alasan + bukti.', 'Kirim dan pantau status.'],
                'steps_en' => ['Open Requests.', 'Fill date/type/reason + evidence.', 'Submit and monitor status.'],
            ],
            'riwayat-catatan' => [
                'purpose_id' => 'Melihat catatan dari pembimbing/instruktur/validator terkait data Anda.',
                'purpose_en' => 'View notes from supervisors/instructors/validators related to your data.',
                'steps_id' => ['Buka Riwayat Catatan.', 'Pilih item untuk melihat detail catatan.'],
                'steps_en' => ['Open Notes History.', 'Open an item to view note details.'],
            ],
            'validasi' => [
                'purpose_id' => 'Memvalidasi absensi check-in dan check-out secara terpisah.',
                'purpose_en' => 'Validate attendance check-in and check-out separately.',
                'steps_id' => ['Pilih tab pending check-in/check-out.', 'Buka detail.', 'Approve/Reject lalu cek status.'],
                'steps_en' => ['Choose pending check-in/check-out tab.', 'Open details.', 'Approve/Reject and verify status.'],
            ],
            'validasi-pengajuan' => [
                'purpose_id' => 'Memvalidasi pengajuan izin/sakit sesuai alur status.',
                'purpose_en' => 'Validate leave/sick requests according to workflow status.',
                'steps_id' => ['Buka Validasi Pengajuan.', 'Periksa bukti dan alasan.', 'Approve/Reject dengan catatan jika perlu.'],
                'steps_en' => ['Open Request Validation.', 'Review evidence and reason.', 'Approve/Reject with notes if needed.'],
            ],
            'summary-report' => [
                'purpose_id' => 'Mengisi catatan mingguan dan meninjau ringkasan evaluasi periodik.',
                'purpose_en' => 'Write weekly notes and review periodic evaluation summary.',
                'steps_id' => ['Pilih periode minggu.', 'Tinjau ringkasan dan daftar siswa.', 'Klik Tambah Catatan lalu simpan melalui pop-up.'],
                'steps_en' => ['Select week period.', 'Review summary and student list.', 'Click Add Note and save through the pop-up.'],
            ],
            'summary-report/rekap' => [
                'purpose_id' => 'Melihat rekap mingguan (absensi, indikator, riwayat validasi).',
                'purpose_en' => 'View weekly recap (attendance, indicators, validation history).',
                'steps_id' => ['Pilih filter periode/jurusan/kelas.', 'Tinjau tabel rekap dan histori.'],
                'steps_en' => ['Select period/department/class filters.', 'Review recap tables and history.'],
            ],
            'summary-report/analisis' => [
                'purpose_id' => 'Melihat analisis tren dan insight siswa/kelas/jurusan.',
                'purpose_en' => 'View trend analysis and student/class/department insights.',
                'steps_id' => ['Buka Analisis Mingguan.', 'Gunakan filter.', 'Tinjau insight dan prioritas tindak lanjut.'],
                'steps_en' => ['Open Weekly Analysis.', 'Use filters.', 'Review insights and follow-up priorities.'],
            ],
            'fitur/manajemen-pengguna' => [
                'purpose_id' => 'Mengelola data user, role, status, dan assignment lokasi.',
                'purpose_en' => 'Manage users, roles, statuses, and location assignment.',
                'steps_id' => ['Buka Manajemen Pengguna.', 'Tambah/edit user.', 'Simpan perubahan.'],
                'steps_en' => ['Open User Management.', 'Create/edit user.', 'Save changes.'],
            ],
            'fitur/hak-akses-menu' => [
                'purpose_id' => 'Mengatur menu apa saja yang boleh diakses setiap role.',
                'purpose_en' => 'Configure which menus each role can access.',
                'steps_id' => ['Buka Hak Akses Menu.', 'Centang role pada baris menu.', 'Klik Simpan Hak Akses.'],
                'steps_en' => ['Open Menu Permissions.', 'Check role cells per menu row.', 'Click Save Permissions.'],
            ],
            'fitur/setting-web' => [
                'purpose_id' => 'Mengatur identitas website, kontak/footer, dan konfigurasi absensi.',
                'purpose_en' => 'Configure website identity, contact/footer, and attendance settings.',
                'steps_id' => ['Buka Setting Website.', 'Ubah data yang diperlukan.', 'Klik Simpan lalu verifikasi perubahan.'],
                'steps_en' => ['Open Website Settings.', 'Update required values.', 'Click Save and verify changes.'],
            ],
            'fitur/lokasi-pkl' => [
                'purpose_id' => 'Mengelola lokasi PKL, koordinat, radius, dan penanggung jawab.',
                'purpose_en' => 'Manage internship locations, coordinates, radius, and supervisors.',
                'steps_id' => ['Buka Lokasi PKL.', 'Tambah/edit data lokasi.', 'Simpan lalu cek di daftar lokasi.'],
                'steps_en' => ['Open Internship Locations.', 'Create/edit location data.', 'Save and verify on list.'],
            ],
            'fitur/audit-log' => [
                'purpose_id' => 'Memantau aktivitas user dan jejak aksi sistem.',
                'purpose_en' => 'Monitor user activity and system action trail.',
                'steps_id' => ['Buka Log Activity.', 'Gunakan filter pencarian.', 'Tinjau detail log.'],
                'steps_en' => ['Open Activity Log.', 'Use search filters.', 'Review log details.'],
            ],
            'fitur/backup-restore' => [
                'purpose_id' => 'Backup dan restore data sistem.',
                'purpose_en' => 'Backup and restore system data.',
                'steps_id' => ['Buka Backup & Restore.', 'Buat backup atau pilih restore.', 'Konfirmasi proses.'],
                'steps_en' => ['Open Backup & Restore.', 'Create backup or choose restore.', 'Confirm process.'],
            ],
            'fitur/import-export' => [
                'purpose_id' => 'Import/export data user secara massal.',
                'purpose_en' => 'Bulk import/export user data.',
                'steps_id' => ['Buka Import & Export User.', 'Pilih file/template sesuai format.', 'Jalankan import/export dan cek hasil.'],
                'steps_en' => ['Open Import & Export User.', 'Use file/template format.', 'Run import/export and verify results.'],
            ],
            'fitur-shared/laporan-grafik' => [
                'purpose_id' => 'Melihat laporan dalam bentuk grafik dan ringkasan visual.',
                'purpose_en' => 'View reports in charts and visual summaries.',
                'steps_id' => ['Buka menu Laporan.', 'Pilih periode/filter.', 'Tinjau grafik dan ringkasan.'],
                'steps_en' => ['Open Reports menu.', 'Choose period/filters.', 'Review charts and summaries.'],
            ],
            'chatbot' => [
                'purpose_id' => 'Asisten tanya jawab penggunaan sistem sesuai role akun.',
                'purpose_en' => 'Assistant for system usage Q&A based on account role.',
                'steps_id' => ['Klik tombol Chatbot Asisten.', 'Tanya kendala Anda.', 'Ikuti panduan yang diberikan bot.'],
                'steps_en' => ['Click Assistant Chatbot button.', 'Ask your issue.', 'Follow the bot guidance.'],
            ],
        ];
    }
}
