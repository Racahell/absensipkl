<?php

namespace App\Support;

class ChatbotKnowledgeBase
{
    /**
     * @return array<int, array{
     *  key:string,
     *  allowed_roles:array<int, string>,
     *  keywords:array<int, string>,
     *  menu_key?:string,
     *  answer_id:string,
     *  answer_en:string
     * }>
     */
    public static function items(): array
    {
        return [
            [
                'key' => 'global.profile',
                'allowed_roles' => ['*'],
                'keywords' => ['profil', 'profile', 'ganti email', 'ubah email', 'verifikasi email', 'password', 'foto profil'],
                'menu_key' => 'profil',
                'answer_id' => 'Buka menu Profil Saya. Di sana Anda bisa ubah data akun, ganti email (dengan verifikasi ulang), dan update password.',
                'answer_en' => 'Open My Profile. You can update account data, change email (with re-verification), and update password there.',
            ],
            [
                'key' => 'global.permission',
                'allowed_roles' => ['superadmin', 'admin_sekolah'],
                'keywords' => ['hak akses', 'menu permission', 'centang menu', 'akses role'],
                'menu_key' => 'fitur/hak-akses-menu',
                'answer_id' => 'Pengaturan akses ada di Hak Akses Menu. Superadmin centang menu per role, lalu role tersebut bisa melihat dan mengakses menu itu.',
                'answer_en' => 'Access settings are in Menu Permissions. Superadmin checks menus per role, then that role can see and access the menu.',
            ],
            [
                'key' => 'siswa.checkin',
                'allowed_roles' => ['siswa'],
                'keywords' => ['check in', 'checkin', 'absen masuk', 'kirim check in', 'kamera check in', 'lokasi check in'],
                'menu_key' => 'absensi',
                'answer_id' => 'Untuk check-in: buka Absensi Harian, masuk ke Check-in, ambil foto dari kamera, pastikan lokasi aktif, lalu kirim. Status akan masuk pending validasi.',
                'answer_en' => 'For check-in: open Daily Attendance, go to Check-in, capture photo from camera, ensure location is enabled, then submit. Status will be pending validation.',
            ],
            [
                'key' => 'siswa.checkout',
                'allowed_roles' => ['siswa'],
                'keywords' => ['check out', 'checkout', 'absen pulang', 'daily report', 'laporan harian', 'kirim check out'],
                'menu_key' => 'absensi',
                'answer_id' => 'Untuk check-out: buka menu Check-out + Daily Report, isi ringkasan/rencana/realisasi kerja, ambil bukti foto, lalu kirim. Data check-out menunggu validasi.',
                'answer_en' => 'For check-out: open Check-out + Daily Report, fill summary/planned/actual work, capture evidence photo, then submit. Checkout data will wait for validation.',
            ],
            [
                'key' => 'siswa.leave_request',
                'allowed_roles' => ['siswa'],
                'keywords' => ['pengajuan', 'izin', 'sakit', 'submit request', 'bukti pengajuan'],
                'menu_key' => 'pengajuan',
                'answer_id' => 'Pengajuan izin/sakit dibuat dari menu Pengajuan. Isi tanggal, jenis, alasan, dan bukti foto dari kamera, lalu kirim untuk validasi.',
                'answer_en' => 'Leave/sick requests are created from the Request menu. Fill date, type, reason, and camera evidence photo, then submit for validation.',
            ],
            [
                'key' => 'siswa.notes',
                'allowed_roles' => ['siswa'],
                'keywords' => ['riwayat catatan', 'catatan pembimbing', 'catatan instruktur', 'feedback saya'],
                'menu_key' => 'riwayat-catatan',
                'answer_id' => 'Catatan dari validator bisa dilihat di menu Riwayat Catatan.',
                'answer_en' => 'Notes from validators can be seen in the Notes History menu.',
            ],
            [
                'key' => 'pembimbing.attendance_validation',
                'allowed_roles' => ['pembimbing_pkl'],
                'keywords' => ['validasi absensi', 'approve check in', 'approve check out', 'reject absensi', 'pending check in', 'pending check out'],
                'menu_key' => 'validasi',
                'answer_id' => 'Pembimbing memvalidasi kehadiran di menu Validasi Absensi. Check-in dan check-out diproses terpisah sesuai status pending masing-masing.',
                'answer_en' => 'Supervisors validate attendance in Attendance Validation. Check-in and check-out are processed separately based on each pending status.',
            ],
            [
                'key' => 'pembimbing.leave_validation',
                'allowed_roles' => ['pembimbing_pkl'],
                'keywords' => ['validasi pengajuan', 'approve izin', 'reject izin', 'approve sakit', 'reject sakit'],
                'menu_key' => 'validasi-pengajuan',
                'answer_id' => 'Pembimbing memvalidasi pengajuan izin/sakit dari menu Validasi Pengajuan sesuai antrian status.',
                'answer_en' => 'Supervisors validate leave/sick requests from Request Validation based on status queue.',
            ],
            [
                'key' => 'instruktur.review',
                'allowed_roles' => ['instruktur'],
                'keywords' => ['catatan mingguan', 'monitoring progres', 'validasi mingguan instruktur'],
                'menu_key' => 'summary-report',
                'answer_id' => 'Pembimbing mengisi catatan mingguan siswa/jurusan dari menu Validasi Mingguan, bukan validasi absensi harian.',
                'answer_en' => 'Instructors write weekly notes from Weekly Validation menu, not daily attendance validation.',
            ],
            [
                'key' => 'kajur.weekly',
                'allowed_roles' => ['kajur'],
                'keywords' => ['validasi mingguan', 'catatan mingguan kajur', 'tambah catatan', 'rekap mingguan', 'analisis mingguan'],
                'menu_key' => 'summary-report',
                'answer_id' => 'Kajur memakai menu Validasi Mingguan untuk melihat ringkasan dan menambah catatan mingguan lewat tombol Tambah Catatan (pop-up), lalu memantau di Rekap Mingguan dan Analisis Mingguan.',
                'answer_en' => 'Department heads use Weekly Validation to review summary and add weekly notes via the Add Note pop-up, then monitor results in Weekly Recap and Weekly Analysis.',
            ],
            [
                'key' => 'admin.user_management',
                'allowed_roles' => ['superadmin', 'admin_sekolah'],
                'keywords' => ['manajemen pengguna', 'tambah user', 'edit user', 'assign lokasi pkl', 'reset akun'],
                'menu_key' => 'fitur/manajemen-pengguna',
                'answer_id' => 'Manajemen user ada di menu Manajemen Pengguna untuk tambah/edit user, set role, dan assign lokasi PKL siswa.',
                'answer_en' => 'User management is in User Management for create/edit users, set roles, and assign student internship locations.',
            ],
            [
                'key' => 'admin.settings',
                'allowed_roles' => ['superadmin', 'admin_sekolah'],
                'keywords' => ['setting website', 'setting web', 'logo', 'favicon', 'timezone absensi'],
                'menu_key' => 'fitur/setting-web',
                'answer_id' => 'Konfigurasi aplikasi ada di Setting Website: identitas web, kontak/footer, dan pengaturan absensi.',
                'answer_en' => 'Application configuration is in Website Settings: web identity, contact/footer, and attendance settings.',
            ],
            [
                'key' => 'admin.location',
                'allowed_roles' => ['superadmin', 'admin_sekolah'],
                'keywords' => ['lokasi pkl', 'radius absensi', 'pembimbing lokasi', 'instruktur lokasi'],
                'menu_key' => 'fitur/lokasi-pkl',
                'answer_id' => 'Lokasi PKL dikelola di menu Lokasi PKL: atur alamat, koordinat, radius, serta pembimbing/instruktur penanggung jawab.',
                'answer_en' => 'Internship locations are managed in Internship Locations: set address, coordinates, radius, and assigned supervisor/instructor.',
            ],
            [
                'key' => 'global.dashboard',
                'allowed_roles' => ['*'],
                'keywords' => ['dashboard', 'beranda', 'ringkasan', 'kartu statistik'],
                'menu_key' => 'dashboard',
                'answer_id' => 'Dashboard menampilkan ringkasan data sesuai role login Anda, termasuk status pending dan shortcut menu utama.',
                'answer_en' => 'Dashboard shows role-based summary data, including pending statuses and main menu shortcuts.',
            ],
            [
                'key' => 'global.pending_status',
                'allowed_roles' => ['*'],
                'keywords' => [
                    'status pending',
                    'pending status',
                    'pending artinya',
                    'arti pending',
                    'what does pending mean',
                    'pending meaning',
                    'pending weekly notes',
                    'pending di catatan mingguan',
                ],
                'answer_id' => 'Status pending artinya data masih menunggu proses lanjutan sesuai alur menu. Khusus catatan mingguan, pending berarti catatan/periode minggu tersebut belum dituntaskan pada tahap evaluasi berikutnya.',
                'answer_en' => 'Pending means the data is still waiting for the next process in that menu workflow. For weekly notes, pending means that week/note has not been finalized in the next evaluation step yet.',
            ],
            [
                'key' => 'global.error_403',
                'allowed_roles' => ['*'],
                'keywords' => ['403', 'akses ditolak', 'tidak punya akses', 'permission denied'],
                'answer_id' => 'Error 403 berarti role Anda belum diberi akses menu itu. Minta superadmin centang akses di Hak Akses Menu.',
                'answer_en' => '403 means your role has not been granted that menu access yet. Ask superadmin to enable it in Menu Permissions.',
            ],
        ];
    }
}

