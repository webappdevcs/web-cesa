# Legacy Sync

Plugin ini dipakai untuk sinkronisasi data legacy SQL ke schema `web-cesa` yang sekarang.

## Ruang Lingkup

Command `legacy:sync` saat ini menangani:

- `form-transfer`
- `exit-clearance`
- `presensi`

Namun pada setup CESA saat ini, sumber datanya bisa berasal dari database yang berbeda:

- `app_old` untuk `form-transfer` dan `exit-clearance`
- `app_presensi` untuk `presensi`

Karena `legacy:sync` hanya membaca satu source database per eksekusi, skenario dua database dijalankan dengan dua command terpisah.

## File Penting

- Command sync: `plugins/cesa/legacy-sync/src/Console/Commands/SyncLegacySqlData.php`
- Config koneksi legacy: `plugins/cesa/legacy-sync/config/legacy-sync.php`
- Mapping sinkronisasi: `legacy_sync_mappings`
- Dump SQL referensi: `plugins/cesa/legacy-sync/database/sql`

## Cara Kerja

`legacy:sync` membaca data langsung dari koneksi database legacy, bukan dari file `.sql`.

File `.sql` di folder `database/sql` hanya dipakai jika Anda perlu:

- import dump ke database baru
- rebuild database legacy untuk keperluan migrasi
- menyiapkan data lokal untuk testing/manual sync

## Koneksi Legacy

Secara default plugin menyediakan koneksi `legacy_sync` lewat config:

```php
config('legacy-sync.connections.legacy_sync')
```

Anda bisa menjalankan sync tanpa menulis config tambahan jika cukup mengirim override lewat CLI:

```bash
php artisan legacy:sync --database=app_old --host=127.0.0.1 --port=3306 --username=root
```

## Setup CESA Saat Ini

### 1. Sync Form Transfer dan Exit Clearance

Gunakan database `app_old`:

```bash
php artisan legacy:sync \
  --module=form-transfer,exit-clearance \
  --database=app_old \
  --host=127.0.0.1 \
  --port=3306 \
  --username=root
```

### 2. Sync Presensi

Untuk database `app_presensi`, gunakan command plugin presensi:

```bash
php artisan presensi:migrate-data \
  --database=app_presensi \
  --host=127.0.0.1 \
  --port=3306 \
  --username=root
```

## Jika Sumber Data Berasal Dari File SQL

Import dulu file SQL ke database MySQL, baru jalankan sync.

Contoh:

```bash
mysql -h127.0.0.1 -P3306 -uroot app_old < plugins/cesa/legacy-sync/database/sql/form-transfer.sql
mysql -h127.0.0.1 -P3306 -uroot app_old < plugins/cesa/legacy-sync/database/sql/exit-clearance.sql
mysql -h127.0.0.1 -P3306 -uroot app_presensi < plugins/cesa/legacy-sync/database/sql/presensi.sql
```

Setelah itu:

```bash
php artisan legacy:sync --module=form-transfer,exit-clearance --database=app_old --host=127.0.0.1 --port=3306 --username=root
php artisan presensi:migrate-data --database=app_presensi --host=127.0.0.1 --port=3306 --username=root
```

## Opsi Penting `legacy:sync`

- `--module=` untuk memilih modul tertentu
- `--database=` untuk memilih source database legacy
- `--host=`, `--port=`, `--username=`, `--password=` untuk override koneksi
- `--truncate` untuk mengosongkan tabel target sebelum sync
- `--chunk=` untuk ukuran chunk proses import
- `--skip-missing-users` untuk mencegah auto-create user legacy yang belum ada
- `--trust-legacy-user-ids` untuk memakai ID user legacy jika ID target memang sebaris
- `--trust-legacy-company-ids` untuk memakai ID company legacy jika ID target memang sebaris

## Auto Create User

Secara default, jika user legacy tidak bisa dipetakan ke user target berdasarkan email, command akan membuat user baru.

Contoh output:

```text
Created missing user [developer@rizqis.com] from legacy user ID [1].
```

Itu bukan error. Itu berarti user legacy tidak ditemukan pada tabel `users` target dan command membuat user baru agar relasi data tetap bisa disimpan.

Jika perilaku ini tidak diinginkan, jalankan dengan:

```bash
php artisan legacy:sync --skip-missing-users ...
```

## Troubleshooting

### Modul Di-skip Karena Tabel Tidak Ada

Contoh:

```text
Skipping module [form-transfer]. Missing legacy table(s): form_transfer_banks, form_transfers, ...
```

Artinya Anda mengarah ke source database yang salah.

Contoh kasus:

- Anda menjalankan `legacy:sync --module=form-transfer,exit-clearance --database=app_presensi`
- padahal tabel `form_transfer_*` dan `ec_*` sebenarnya ada di `app_old`

Solusinya:

- gunakan `app_old` untuk `form-transfer` dan `exit-clearance`
- gunakan `app_presensi` untuk `presensi`

### Progress Bar Tercampur Dengan Pesan User

Output seperti ini:

```text
80/133 [...] Created missing user [...]
```

Itu hanya efek visual karena progress bar dan output info ditulis pada saat yang sama. Bukan error proses.

## Catatan

- Command `legacy:sync` menyimpan mapping ke tabel `legacy_sync_mappings`
- Sync berjalan dalam mode upsert secara default
- Jika Anda ingin sync berulang dari source yang sama, mode default ini aman karena record yang sudah ada akan di-update
