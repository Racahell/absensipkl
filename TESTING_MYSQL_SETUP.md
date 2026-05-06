# MySQL Testing Setup

Dokumen ini menjelaskan setup test environment agar `php artisan test` memakai MySQL (bukan SQLite memory).

## Yang Sudah Disiapkan

- `phpunit.xml` diarahkan ke:
  - `DB_CONNECTION=mysql`
  - `DB_DATABASE=absensipkl_testing`
  - `DB_USERNAME=root`
  - `DB_PASSWORD=`
- File `.env.testing` sudah dibuat untuk environment test terpisah.

## Menjalankan Setup (sekali saat awal / jika DB test hilang)

1. Pastikan MySQL server aktif.
2. Buat database test:

```powershell
@'
<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
$pdo->exec('CREATE DATABASE IF NOT EXISTS absensipkl_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
echo "db_ready\n";
'@ | php
```

3. Generate key untuk `.env.testing`:

```powershell
php artisan key:generate --env=testing --force
```

4. Migrate schema test:

```powershell
php artisan migrate:fresh --env=testing --force
```

## Menjalankan Test

```powershell
php artisan test
```

## Catatan

- Database `absensipkl_testing` terpisah dari database utama aplikasi.
- Jalankan `migrate:fresh --env=testing` jika ingin reset data test sepenuhnya.
