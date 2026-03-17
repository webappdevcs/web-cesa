# Shelf Migration Simplification Report

Tanggal analisis: 2026-03-17  
Scope: `plugins/cesa/shelf` dan dependensi langsung `plugins/cesa/legacy-sync`

## Backup

Backup file sebelum edit disimpan di:

- `plugins/cesa/shelf/backups/2026-03-17-migration-cleanup/`

## Peta Kompleksitas

### Rendah

- `2024_07_08_133816_create_categories_table`
- `2024_07_10_141644_create_brands_table`
- `2024_07_10_142706_create_asset_locations_table`
- `2024_10_21_135802_create_vendors_table`
- `2026_03_17_010000_create_company_document_settings_table`

Karakteristik:

- Master data sederhana.
- Dependensi terbatas ke resource, relation, dan foreign key perusahaan.

### Sedang

- `2024_11_14_101451_create_custom_asset_attributes_table`
- `2024_11_14_101453_create_asset_attributes_table`
- `2024_10_21_135803_create_tasks_table`
- `2024_11_12_111750_create_vehicle_checksheets_table`

Karakteristik:

- Dipakai oleh resource Filament yang cukup aktif.
- Memiliki query filter/list yang sensitif terhadap index.
- Sebagian field historis masih ada, tetapi tidak semuanya aman untuk dihapus.

### Tinggi

- `2024_07_10_143140_create_assets_table`
- `2024_07_10_155351_create_asset_transfers_table`
- `2024_07_11_104706_create_asset_transfer_details_table`
- `2024_12_10_092658_add_company_foreign_keys_to_shelf_tables`
- `2026_03_17_065802_add_resource_permission_support_to_shelf_tables`

Karakteristik:

- Menjadi graph inti `assets -> transfers/details -> checksheets/tasks`.
- Dipakai oleh model validation, permission scope, PDF generation, dan legacy sync.
- Sensitif terhadap perubahan foreign key dan creator scoping.

### Tinggi Sekali

- `2026_03_09_000001_create_asset_requests_table`
- `2026_03_09_234746_create_approval_levels_table`
- `2026_03_09_234746_create_request_approvals_table`

Karakteristik:

- Data lokal saat analisis kosong, tetapi flow fitur aktif penuh.
- Bergantung pada controller, service, Livewire, mail, resource, dan legacy sync.
- Tidak aman dihapus hanya berdasarkan row count.

## Bukti Data Aktual

Snapshot database lokal `cesa` sebelum cleanup:

- `shelf_assets`: 1213 row
- `shelf_asset_transfers`: 1161 row
- `shelf_asset_transfer_details`: 1372 row
- `shelf_tasks`: 214 row
- `shelf_vehicle_checksheets`: 1530 row
- `shelf_custom_asset_attributes`: 6 row
- `shelf_asset_requests`: 0 row
- `shelf_approval_levels`: 0 row
- `shelf_request_approvals`: 0 row

Temuan utama dari data hidup:

- `creator_id` ada di tabel Shelf, tetapi sebelum repair migration seluruh sampling utama masih `NULL`.
- `shelf_vehicle_checksheets.asset_id` tetap 0 terisi pada 1530 row, sehingga belum aman di-drop karena model permission scope masih mengacu ke relasi ini.
- Filter operasional paling aktif pada checksheet memakai `license_plate`, `pic`, dan `location`, tetapi index-nya belum ada.

## Perubahan yang Diterapkan

### Migration Layer

- Menghapus migration `2026_03_17_070000_add_creator_indexes_to_permission_scoped_shelf_tables.php`.
  Alasan: index `creator_id` sudah tercakup oleh foreign key index MySQL, sehingga migration tambahan hanya akan menambah index redundant.

- Menyederhanakan `2026_03_17_020000_add_performance_indexes_to_shelf_tables.php`.
  Perubahan:
  - menghapus target index yang tidak relevan untuk workload aktif,
  - mengarahkan migration ke index checksheet yang benar-benar dipakai query:
    - `license_plate`
    - `pic`
    - `location`

- Menambahkan `2026_03_17_160000_cleanup_redundant_shelf_indexes.php`.
  Fungsi:
  - memastikan index operasional checksheet tersedia pada database yang sudah terlanjur bermigrasi lama.

- Menambahkan `2026_03_17_161000_backfill_missing_creator_ids_on_shelf_tables.php`.
  Fungsi:
  - mengisi `creator_id` yang kosong berdasarkan kolom owner/assignee/parent table yang aktual.

- Mengekstrak logika backfill creator ke helper bersama:
  - `src/Support/InteractsWithShelfCreatorBackfill.php`

- Menyederhanakan `2026_03_17_065802_add_resource_permission_support_to_shelf_tables.php` agar memakai helper backfill bersama.

### Runtime / Legacy Sync

- Menambahkan pemanggilan `backfillShelfCreatorIds()` di `plugins/cesa/legacy-sync/src/Console/Commands/SyncLegacySqlData.php` setelah sinkronisasi Shelf selesai.
  Tujuan:
  - mencegah data hasil sync berikutnya kembali menghasilkan `creator_id = NULL`.

### Dead Code Cleanup

- Menghapus relasi model yang tidak punya backing column atau tidak lagi dipakai:
  - `Asset::assetTransfers()`
  - `AssetTransfer::asset()`
  - `CustomAssetAttribute::category()`

- Menghapus jalur auto-suggest transfer type di resource yang tidak lagi dipakai:
  - `AssetTransferResource::syncSuggestedTransferType()`

- Mengembalikan kontrak inferensi transfer type di model untuk kompatibilitas lintas-plugin:
  - `AssetTransfer::inferTransferTypeFromUsers()`
  - `AssetTransfer::inferTransferTypeFromUserIds()`
  - dipakai oleh `legacy-sync` sebagai fallback inference

- Menghapus helper mati dan merapikan query berulang pada `AssetResource`.
  Perubahan:
  - helper cache kategori sekarang benar-benar dipakai,
  - lookup atribut custom tidak lagi melakukan query berulang di setiap closure,
  - opsi repeater membaca state parent yang benar.

- Menghapus blok komentar permanen di resource yang sudah tidak punya fungsi:
  - `VehicleChecksheetResource`
  - `AssetTransfersRelationManager`
  - `TaskResource\Pages\ViewTask`
  - beberapa placeholder `getRelations()` dan `filters([])` yang sebelumnya hanya berisi komentar.

## Perubahan yang Sengaja Tidak Dilakukan

- Tidak menghapus tabel `asset_requests`, `approval_levels`, `request_approvals`.
  Alasan: row count kosong di lokal, tetapi flow fitur aktif dan direferensikan lintas controller/service/livewire/mail/resource.

- Tidak menghapus `shelf_vehicle_checksheets.asset_id`.
  Alasan: data aktual memang belum memakai kolom ini, tetapi permission scope model masih bergantung ke relasi tersebut. Penghapusan butuh refactor policy/scope lebih lanjut.

- Tidak menghapus `2026_03_17_150000_add_soft_deletes_to_shelf_tables.php`.
  Alasan: sekarang no-op untuk fresh install, tetapi tetap berfungsi sebagai layer kompatibilitas untuk skema lama.

- Tidak memaksa drop index lama `shelf_asset_transfer_details_asset_id_index`.
  Alasan: pada MySQL lokal index tersebut masih dipakai oleh foreign key constraint, sehingga drop langsung tidak aman tanpa bongkar-pasang FK.

## Hasil Verifikasi

### Formatter

- `vendor/bin/pint --dirty --format agent`

### Test

- `php artisan test --compact plugins/cesa/shelf/tests/Feature/ShelfPluginSmokeTest.php plugins/cesa/shelf/tests/Feature/ModelBehaviorTest.php`
- Hasil: `34 passed (388 assertions)`

### Migrasi Database Nyata

- `php artisan migrate --path=plugins/cesa/shelf/database/migrations --force --no-interaction`
- Hasil:
  - `2026_03_17_160000_cleanup_redundant_shelf_indexes` ran
  - `2026_03_17_161000_backfill_missing_creator_ids_on_shelf_tables` ran

### Verifikasi Data Pasca-Migrasi

Setelah backfill:

- `shelf_assets.creator_id`: 1213/1213 terisi
- `shelf_asset_transfers.creator_id`: 1161/1161 terisi
- `shelf_asset_transfer_details.creator_id`: 1372/1372 terisi
- `shelf_tasks.creator_id`: 214/214 terisi
- `shelf_vehicle_checksheets.creator_id`: 1530/1530 terisi
- `shelf_custom_asset_attributes.creator_id`: 6/6 terisi
- `shelf_asset_attributes.creator_id`: 1435/1435 terisi
- `shelf_company_document_settings.creator_id`: 14/14 terisi

Index checksheet yang aktif setelah migrasi:

- `shelf_vehicle_checksheets_license_plate_index`
- `shelf_vehicle_checksheets_pic_index`
- `shelf_vehicle_checksheets_location_index`

## Residual Risk

- Bila nanti diputuskan untuk benar-benar menghapus `vehicle_checksheets.asset_id`, perlu refactor lanjutan di:
  - `VehicleChecksheet` permission scope
  - `Asset::vehicleChecksheets()`
  - test relation terkait

- Duplicate index pada `shelf_asset_transfer_details.asset_id` masih perlu pendekatan migration terpisah jika ingin dibersihkan total tanpa merusak foreign key lama.
