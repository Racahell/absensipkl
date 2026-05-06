# Hasil Black Box Testing

Tanggal: 2026-05-02  
Project: `absensipkl`

## Scope Pengujian

- Public entrypoints utama (akses guest).
- Proteksi halaman private (guest harus di-redirect ke `/login`).
- Proteksi action POST private (guest harus diblokir/redirect).
- E2E flow data real:
  - Login password + offline captcha.
  - Check-in siswa lalu approve check-in oleh pembimbing.
  - Pengajuan izin siswa lalu reject oleh pembimbing hingga absensi jadi `alpha`.
- Route coverage otomatis:
  - Semua route `web+auth` diuji sebagai guest (harus terdorong ke login).
  - Semua route `GET` web non-parameter diuji saat login superadmin (tidak boleh error server 5xx).
- Regression check seluruh test suite.

## File Test Ditambahkan

- `tests/Feature/BlackBoxSmokeTest.php`
- `tests/Feature/BlackBoxE2ETest.php`
- `tests/Feature/BlackBoxRouteCoverageTest.php`

## Hasil Eksekusi

### 1) Black-box smoke suite

Perintah:

```bash
php artisan test --filter=BlackBoxSmokeTest
```

Hasil:

- PASS `public_entrypoints_are_reachable_or_redirect_safely`
- PASS `guest_is_redirected_from_protected_pages`
- PASS `guest_post_to_protected_actions_is_blocked`
- Total: **3 test, 77 assertions, 0 gagal**

### 2) Black-box E2E flow data

Perintah:

```bash
php artisan test --filter=BlackBoxE2ETest
```

Hasil:

- PASS `password_login_with_offline_captcha_succeeds`
- PASS `student_checkin_can_be_approved_by_pembimbing`
- PASS `student_leave_rejected_by_pembimbing_becomes_alpha_attendance`
- Total: **3 test, 19 assertions, 0 gagal**

### 3) Black-box route coverage otomatis

Perintah:

```bash
php artisan test --filter=BlackBoxRouteCoverageTest
```

Hasil:

- PASS `guest_is_blocked_from_all_web_auth_routes`
- PASS `authenticated_superadmin_can_reach_non_parameterized_web_get_routes`
- Total: **2 test, 350 assertions, 0 gagal**

Catatan metrik route:

- Route `web+auth` yang tervalidasi guest-block: **seluruhnya** (dinamis dari route list saat test dijalankan).
- Route `GET` web non-parameter yang tervalidasi saat login superadmin: **seluruhnya** (dinamis dari route list saat test dijalankan).

### 4) Seluruh test project

Perintah:

```bash
php artisan test
```

Hasil:

- Unit: PASS
- Feature: PASS (Smoke + E2E + Route Coverage)
- Total: **11 test, 451 assertions, 0 gagal**

## Catatan

- Pengujian ini fokus black-box endpoint behavior dan akses kontrol guest (redirect/deny) dengan cakupan luas.
- E2E berbasis data sudah dijalankan menggunakan MySQL test DB (`absensipkl_testing`), bukan SQLite memory.
- Detail setup environment test MySQL ada di `TESTING_MYSQL_SETUP.md`.
- Klaim “100% absolut” tidak bisa dijamin hanya dari black-box HTTP test, karena masih ada area yang butuh skenario tambahan:
  - semua kombinasi role x menu permission x data edge-case,
  - semua jalur error eksternal (mail/discord/maps/otp provider),
  - semua skenario concurrency dan time-based SLA.
- Namun, untuk cakupan black-box endpoint + flow inti aplikasi saat ini, suite ini sudah sangat luas dan tervalidasi otomatis pada route yang aktif.
