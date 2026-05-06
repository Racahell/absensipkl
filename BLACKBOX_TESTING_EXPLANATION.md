# Penjelasan Black-Box Testing pada Aplikasi Ini

## Apa itu Black-Box Testing

Black-box testing adalah metode pengujian yang memeriksa perilaku aplikasi dari sisi luar (input dan output), tanpa bergantung pada detail implementasi kode di dalamnya.

Fokus utamanya:

- Apa yang user kirim (request, form input, file upload, aksi endpoint).
- Apa yang sistem keluarkan (response HTTP, redirect, error validasi, perubahan status data bisnis).

## Tujuan di Proyek `absensipkl`

Tujuan black-box testing pada proyek ini adalah memastikan alur fungsional utama berjalan sesuai aturan bisnis:

- Akses halaman sesuai autentikasi/otorisasi.
- Login berjalan sesuai aturan captcha dan kredensial.
- Alur absensi siswa dan validasi pembimbing menghasilkan status yang benar.
- Alur pengajuan izin/sakit menghasilkan dampak status yang benar.

## Ciri Black-Box yang Diterapkan di Test

Pengujian yang dibuat tidak memvalidasi detail internal seperti algoritme private method, tetapi memvalidasi:

- Response route (`200`, `302`, dsb).
- Redirect target (`/login`, `/dashboard/...`).
- Session error/no error.
- Hasil bisnis akhir (contoh: status absensi menjadi `alpha` setelah reject pengajuan).

## Mapping Skenario ke Test yang Sudah Ada

### 1) Smoke Black-Box Endpoint

File: `tests/Feature/BlackBoxSmokeTest.php`

Menguji:

- Endpoint publik bisa diakses atau redirect aman.
- Guest tidak bisa mengakses halaman private.
- Guest tidak bisa mengeksekusi POST action private.

Makna black-box:

- Dari sudut pandang pengguna anonim, sistem menjaga boundary akses dengan benar.

### 2) E2E Black-Box Flow Data Real

File: `tests/Feature/BlackBoxE2ETest.php`

Menguji:

- Login password + offline captcha berhasil.
- Siswa check-in, lalu pembimbing approve check-in.
- Siswa kirim pengajuan izin, lalu pembimbing reject, dan status absensi menjadi `alpha`.

Makna black-box:

- Dari sudut pandang aktor bisnis (siswa/pembimbing), alur nyata lintas endpoint menghasilkan outcome yang sesuai kebijakan aplikasi.

## Batasan Pengujian Saat Ini

Yang belum dicakup penuh:

- E2E detail check-out + validasi checkout + assessment.
- E2E validasi mingguan (approve/revisi) secara lengkap.
- E2E berjenjang catatan bimbingan mentor -> kajur -> wakil kepsek.

## Kesimpulan

Pengujian yang sudah dibuat termasuk black-box testing karena:

- Berbasis perilaku eksternal sistem.
- Menguji input-output dan aturan bisnis.
- Tidak bergantung pada detail implementasi internal untuk menyatakan lulus/gagal.

Dokumen hasil eksekusi test tersedia di: `BLACKBOX_TEST_RESULT.md`.
