# Rekrutmen, Kepegawaian, dan Exit Clearance Lifecycle

## Tujuan

Dokumen ini menjelaskan:

- cara kerja plugin `rekrutmen`
- cara kerja plugin `kepegawaian`
- cara kerja plugin `exit-clearance`
- cara menyatukan ketiganya menjadi lifecycle yang profesional
- root edges yang harus diantisipasi sejak awal
- plan implementasi bertahap agar hasil akhirnya tidak naif, tahan banting, dan minim issue

Dokumen ini sengaja dibuat sebagai panduan arsitektur dan delivery, bukan sekadar ide produk.

## Prinsip Dasar

Target akhirnya bukan sekadar membuat plugin saling membaca data satu sama lain.

Target akhirnya adalah membangun lifecycle kerja yang jelas:

1. kebutuhan tenaga kerja muncul
2. kandidat direkrut
3. kandidat diangkat menjadi employee
4. employee aktif bekerja
5. employee keluar melalui proses offboarding
6. jika perlu, sistem memicu kebutuhan replacement

Cycle yang sehat:

`Request Man Power -> Job Posting -> Job Application -> Hire Decision -> Employee -> Exit Clearance -> Exit Finalization -> Replacement Request`

## Status Implementasi 19 Juli 2026

Fondasi lifecycle operasional sudah diimplementasikan di plugin `kepegawaian`:

- template workflow HR yang dapat diubah oleh HR
- workflow per employee dengan status, urutan aktivitas, PIC, tenggat, catatan, dan bukti privat
- enam template bawaan yang diturunkan dari `DATABASE KARYAWAN.xlsx`:
  - onboarding karyawan: 30 aktivitas
  - offboarding karyawan: 14 aktivitas
  - offering: 6 aktivitas
  - perpanjangan kontrak: 7 aktivitas
  - surat peringatan: 7 aktivitas
  - training: 7 aktivitas
- metadata operasional berupa nomor referensi, tanggal efektif, tanggal berakhir, dan catatan
- halaman daftar workflow, konfigurasi template, dan tab aktivitas HR pada profil employee
- permission terpisah untuk melihat, memulai, membatalkan, dan mengelola tugas
- event bridge dari kandidat `HIRED` ke employee dan onboarding
- event bridge dari Exit Clearance `Approved` ke offboarding
- deactivation employee hanya dilakukan setelah semua aktivitas offboarding selesai
- source key dan external identifier untuk mencegah proses lifecycle yang sama dibuat berulang

Alur penggunaan HR:

1. buka profil employee
2. buka relasi **Aktivitas HR**
3. pilih **Mulai aktivitas HR**
4. pilih template dan isi referensi/tanggal jika diperlukan
5. tetapkan PIC per aktivitas
6. PIC memulai, mengunggah bukti jika diwajibkan, lalu menyelesaikan aktivitas
7. sistem menutup workflow otomatis saat seluruh aktivitas wajib selesai

Bridge lintas plugin sengaja memakai event dan service boundary. Rekrutmen dan Exit Clearance tidak mengakses tabel internal Kepegawaian secara langsung. Kegagalan bridge dicatat di log tanpa membatalkan keputusan hiring atau approval yang sudah sah.

Cycle yang tidak sehat:

- kandidat dianggap employee hanya karena status berubah ke `HIRED`
- exit clearance dianggap bagian dari rekrutmen
- replacement request otomatis dibuat untuk semua exit
- integrasi antar plugin memakai `name` atau `email` sebagai identity utama

## Ringkasan Peran Plugin

### 1. Rekrutmen

Plugin ini bertanggung jawab atas kebutuhan manpower dan proses kandidat.

Komponen utama:

- [`plugins/cesa/rekrutmen/src/Models/RequestManPower.php`](plugins/cesa/rekrutmen/src/Models/RequestManPower.php)
- [`plugins/cesa/rekrutmen/src/Models/JobPosting.php`](plugins/cesa/rekrutmen/src/Models/JobPosting.php)
- [`plugins/cesa/rekrutmen/src/Models/JobApplication.php`](plugins/cesa/rekrutmen/src/Models/JobApplication.php)
- [`plugins/cesa/rekrutmen/src/Models/JobApplicationHistory.php`](plugins/cesa/rekrutmen/src/Models/JobApplicationHistory.php)
- [`plugins/cesa/rekrutmen/src/Filament/Resources/RequestManPowerResource.php`](plugins/cesa/rekrutmen/src/Filament/Resources/RequestManPowerResource.php)
- [`plugins/cesa/rekrutmen/src/Filament/Resources/JobApplicationResource.php`](plugins/cesa/rekrutmen/src/Filament/Resources/JobApplicationResource.php)

Cara kerja saat ini:

- user membuat `RequestManPower`
- approver dapat `approve` atau `reject`
- saat `approve`, `RequestManPower` dapat membuat `JobPosting`
- kandidat masuk sebagai `JobApplication`
- kandidat bergerak antar stage pipeline
- status aplikasi bisa menjadi `HIRED`, `REJECTED`, `WITHDRAWN`, atau `IN_PROGRESS`

Fakta penting dari codebase:

- `RequestManPower` masih menyimpan banyak data sebagai string:
  - `divisi`
  - `badan_usaha`
  - `posisi_dibutuhkan`
  - `lokasi_penempatan`
- `JobPosting` belum terhubung ke master HR seperti department, company, job position, atau work location
- `JobApplication` tidak memiliki foreign key langsung ke employee; hasil hiring dihubungkan melalui event dan canonical external identifier

Kesimpulan:

- `rekrutmen` sudah cukup matang sebagai upstream kebutuhan tenaga kerja dan kandidat
- `rekrutmen` belum cukup matang untuk dijadikan employee master

### 2. Kepegawaian

Plugin ini adalah pusat data employee dan organisasi kerja.

Komponen utama:

- [`plugins/cesa/kepegawaian/src/Models/Employee.php`](plugins/cesa/kepegawaian/src/Models/Employee.php)
- [`plugins/cesa/kepegawaian/src/Models/Department.php`](plugins/cesa/kepegawaian/src/Models/Department.php)
- [`plugins/cesa/kepegawaian/src/Models/EmployeeJobPosition.php`](plugins/cesa/kepegawaian/src/Models/EmployeeJobPosition.php)
- [`plugins/cesa/kepegawaian/src/Models/EmploymentType.php`](plugins/cesa/kepegawaian/src/Models/EmploymentType.php)
- [`plugins/cesa/kepegawaian/src/Models/WorkLocation.php`](plugins/cesa/kepegawaian/src/Models/WorkLocation.php)
- [`plugins/cesa/kepegawaian/src/Models/DepartureReason.php`](plugins/cesa/kepegawaian/src/Models/DepartureReason.php)
- [`plugins/cesa/kepegawaian/src/Filament/Resources/EmployeeResource.php`](plugins/cesa/kepegawaian/src/Filament/Resources/EmployeeResource.php)

Cara kerja saat ini:

- employee dibuat dan dikelola di plugin ini
- employee memegang relasi utama:
  - company
  - department
  - job position
  - user
  - work location
  - departure reason
- employee juga memegang status kerja aktif dan data keluar kerja

Fakta penting dari codebase:

- `Employee` adalah entitas paling dekat dengan "hubungan kerja resmi"
- `Employee` sudah punya field offboarding seperti `departure_date` dan `departure_reason_id`
- saat save, employee akan membuat atau mengupdate `Partner`
- bridge hasil hiring ke employee dan onboarding sudah tersedia serta idempotent

Kesimpulan:

- `kepegawaian` harus menjadi source of truth untuk employee lifecycle
- plugin ini adalah pusat yang paling layak untuk menjadi anchor arsitektur lintas plugin

### 3. Exit Clearance

Plugin ini bertanggung jawab pada workflow clearance dan offboarding approval.

Komponen utama:

- [`plugins/cesa/exit-clearance/src/Models/Request.php`](plugins/cesa/exit-clearance/src/Models/Request.php)
- [`plugins/cesa/exit-clearance/src/Models/Department.php`](plugins/cesa/exit-clearance/src/Models/Department.php)
- [`plugins/cesa/exit-clearance/src/Models/Approver.php`](plugins/cesa/exit-clearance/src/Models/Approver.php)
- [`plugins/cesa/exit-clearance/src/Services/ExitClearanceRequestService.php`](plugins/cesa/exit-clearance/src/Services/ExitClearanceRequestService.php)
- [`plugins/cesa/exit-clearance/README.md`](plugins/cesa/exit-clearance/README.md)

Cara kerja saat ini:

- request bisa dibuat dari public form
- request juga bisa dibuat dari admin panel
- system attach approver berdasarkan department plugin `exit-clearance`
- status request bergerak sesuai persetujuan approver

Fakta penting dari codebase:

- request exit clearance belum punya `employee_id`; bridge awal hanya berjalan bila email cocok tepat ke satu employee, kemudian workflow memakai source key yang stabil
- data request masih berbasis:
  - `name`
  - `email`
  - `department_id`
  - `position`
  - `placement`
- `exit_clearance_departments` adalah domain approval routing sendiri
- plugin ini punya public path dengan rule akses yang memang berbeda

Kesimpulan:

- `exit-clearance` saat ini adalah workflow offboarding berbasis form
- approval final plugin ini kini memicu workflow offboarding Kepegawaian, tetapi canonical `employee_id` pada request masih menjadi hardening lanjutan

## Source of Truth yang Benar

Supaya lifecycle tidak naif, source of truth harus dibagi jelas:

- `rekrutmen`
  - kebutuhan headcount
  - posting
  - kandidat
  - status hiring decision

- `kepegawaian`
  - employee resmi
  - struktur organisasi kerja
  - relasi user dan partner
  - status aktif / nonaktif
  - data keluar kerja

- `exit-clearance`
  - workflow clearance
  - approval chain
  - checklist offboarding
  - hasil final clearance

Aturan penting:

- candidate bukan employee
- request manpower bukan employee
- exit clearance bukan employee master
- employee adalah anchor lifecycle

## Kenapa Integrasi Langsung Sekarang Akan Naif

Kalau ketiga plugin ini langsung dijahit sekarang tanpa guardrail, masalahnya:

1. `rekrutmen` masih pakai banyak field string untuk organisasi
2. `kepegawaian` belum tahu asal employee dari job application mana
3. `exit-clearance` belum tahu employee mana yang sedang offboarding
4. identity masih rawan drift jika mengandalkan email atau nama
5. approval department di `exit-clearance` belum tentu identik dengan org department di `kepegawaian`

Hasil buruk yang mungkin muncul:

- employee masuk ke department yang salah karena mapping string
- hire ganda untuk candidate yang sama
- exit clearance dibuat untuk email yang sama tapi bukan employee record yang aktif
- replacement request dibuat tanpa referensi employee yang keluar
- laporan lintas plugin tidak konsisten

## Root Edges

Berikut root edges yang harus dianggap sebagai bagian inti desain, bukan edge kecil.

### 1. Identity Edge

Empat identitas berbeda harus dipisah jelas:

- candidate
- user
- partner
- employee

Rule:

- jangan pakai `email` sebagai primary join antar plugin
- pakai FK eksplisit
- email tetap penting sebagai snapshot dan contact channel

### 2. Organization Mapping Edge

`rekrutmen` masih menyimpan org data dalam bentuk string.

Rule:

- jangan langsung buang string existing
- tambahkan FK ke master HR
- simpan snapshot string untuk histori

Contoh arah aman:

- `request_man_powers.company_id` + `badan_usaha`
- `request_man_powers.department_id` + `divisi`
- `request_man_powers.job_position_id` + `posisi_dibutuhkan`
- `request_man_powers.work_location_id` + `lokasi_penempatan`

### 3. Hiring State Edge

Status `HIRED` belum otomatis berarti employee resmi.

Rule:

- `HIRED` harus dianggap "siap di-onboard"
- employee baru resmi ada setelah onboarding action selesai

### 4. Offboarding State Edge

Exit clearance final approved belum otomatis berarti employee selesai di-offboard.

Rule:

- perlu service finalization yang eksplisit
- baru setelah itu employee ditandai keluar

### 5. Replacement Edge

Tidak semua exit butuh replacement.

Rule:

- replacement harus berbasis keputusan bisnis
- jangan auto-create `RequestManPower` untuk semua exit

### 6. Multiplicity Edge

Satu kebutuhan bisa punya banyak kandidat dan banyak hire.

Rule:

- `RequestManPower` tidak boleh dianggap selesai hanya karena ada 1 `HIRED`
- harus ada konsep `filled_count` vs `required_count` bila nanti dibutuhkan

### 7. Snapshot Edge

Data historis tidak boleh ikut berubah saat master data berubah.

Rule:

- lifecycle lintas plugin harus punya dual storage:
  - FK ke master
  - snapshot display value

### 8. Public Workflow Edge

`exit-clearance` public form tetap valid sebagai jalur input, tapi bukan backbone lifecycle internal.

Rule:

- pertahankan public flow sebagai manual/fallback path
- untuk employee internal, jalur utama harus employee-linked

### 9. Permission Edge

Public request `created_by = null` memang global-only.

Rule:

- rule ini jangan dirusak saat integrasi
- employee-linked internal flow boleh beda rule, tapi harus eksplisit

### 10. Plugin Boundary Edge

Ini perubahan lintas plugin, bukan single plugin enhancement.

Rule:

- ownership harus jelas
- orchestration tidak boleh disebar acak di resource/controller

## Arsitektur Target

Target yang saya rekomendasikan:

### Rekrutmen sebagai Upstream

- menghasilkan `RequestManPower`
- menghasilkan `JobPosting`
- menghasilkan `JobApplication`
- menghasilkan `Hiring Decision`

### Kepegawaian sebagai Core Workforce Aggregate

- menerima hasil hiring melalui onboarding
- menyimpan employee resmi
- menjadi pusat data lifecycle tenaga kerja

### Exit Clearance sebagai Offboarding Workflow

- menerima employee-linked exit request
- menjalankan approval dan checklist
- menyerahkan hasil final ke employee lifecycle

### Replacement sebagai Loop Balik ke Rekrutmen

- hanya tercipta bila exit memang menghasilkan backfill need
- tetap melalui `RequestManPower`, bukan jalur khusus yang tersembunyi

## Alur Target yang Profesional

### Phase A: Need Creation

1. user membuat `RequestManPower`
2. approver memutuskan approve atau reject
3. jika approve, `JobPosting` dibuat atau diaktifkan

### Phase B: Candidate Pipeline

1. kandidat masuk `JobApplication`
2. recruiter memindahkan stage
3. recruiter/HR memutuskan `HIRED`

### Phase C: Onboarding

1. HR menjalankan action `Hire & Onboard`
2. wizard finalisasi:
   - company
   - department
   - job position
   - work location
   - join date
   - employment type
   - user account
3. system membuat `Employee`
4. `JobApplication` dan `Employee` saling terhubung

### Phase D: Active Employment

1. employee aktif bekerja
2. employee lifecycle dikelola di `kepegawaian`

### Phase E: Offboarding

1. employee keluar atau resign
2. HR/internal membuat `ExitClearanceRequest`
3. request dihubungkan ke `Employee`
4. approver menyelesaikan clearance

### Phase F: Exit Finalization

Saat clearance final approved:

- update `departure_date`
- update `departure_reason_id`
- set employee nonaktif
- optional deactivate user

### Phase G: Replacement Loop

Jika bisnis perlu replacement:

- buat `RequestManPower` baru
- `status_kebutuhan = Replacement`
- `source_employee_id` terisi
- field awal diprefill dari employee yang keluar

## Anti-Patterns yang Harus Dilarang

- auto-create employee hanya karena status aplikasi jadi `HIRED`
- auto-create replacement request untuk semua offboarding
- cross-plugin join berdasarkan `name` atau `email`
- menjadikan `exit-clearance` public flow sebagai jalur utama employee lifecycle
- memaksa `exit_clearance_departments` identik 1:1 dengan `employees_departments` tanpa definisi
- menyebar logic lifecycle ke resource Filament

## Desain Data yang Aman

### Relasi Minimum

Saya sarankan minimal menambah:

- `employees_employees.job_application_id` nullable
  atau
- `rekrutmen_job_applications.employee_id` nullable

- `exit_clearance_requests.employee_id` nullable

- `rekrutmen_request_man_powers.source_employee_id` nullable

Opsional tapi sangat berguna:

- `rekrutmen_request_man_powers.generated_from_exit_clearance_request_id`
- `exit_clearance_requests.generated_replacement_request_man_power_id`

### Snapshot Fields yang Sebaiknya Dipertahankan

Walau FK ditambah, snapshot sebaiknya tetap ada pada:

- request manpower
- job posting
- exit clearance request

Tujuan:

- histori tetap akurat meski master data berubah
- laporan lama tetap konsisten

## Lokasi Orkestrasi yang Saya Sarankan

Jangan taruh lifecycle orchestration di resource/controller.

Pakai service yang eksplisit:

- `HireJobApplicationService`
- `CreateEmployeeFromJobApplicationService`
- `CreateEmployeeUserAccountService`
- `CreateEmployeeLinkedExitClearanceService`
- `FinalizeExitClearanceService`
- `CreateReplacementRequestManPowerService`

Kalau masih ingin menjaga boundary sederhana, letakkan service orchestration awal di plugin `kepegawaian`, karena di situlah aggregate employee hidup.

Kalau lifecycle nanti berkembang lebih besar, pertimbangkan plugin orchestration khusus:

- `plugins/cesa/workforce-lifecycle`

Itu lebih visioner untuk jangka panjang karena:

- rekrutmen tetap fokus di kandidat
- kepegawaian tetap fokus di employee
- exit-clearance tetap fokus di offboarding
- orchestration lintas domain punya rumah sendiri

## Phased Plan

### Phase 1

Tujuan:

- bridge `rekrutmen -> kepegawaian`

Deliverables:

- relasi `JobApplication <-> Employee`
- action `Hire & Onboard`
- service onboarding
- test hiring lifecycle

### Phase 2

Tujuan:

- align rekrutmen dengan master HR

Deliverables:

- FK tambahan di `RequestManPower`
- FK tambahan di `JobPosting`
- snapshot tetap dipertahankan
- migration + backfill mapping

### Phase 3

Tujuan:

- bridge `kepegawaian -> exit-clearance`

Deliverables:

- `exit_clearance_requests.employee_id`
- jalur internal employee-linked request
- public flow tetap dipertahankan

### Phase 4

Tujuan:

- finalisasi offboarding

Deliverables:

- service update employee saat exit final approved
- optional deactivation user
- departure reason mapping

### Phase 5

Tujuan:

- replacement loop

Deliverables:

- create replacement manpower request
- prefill dari employee yang keluar
- explicit business toggle

### Phase 6

Tujuan:

- observability dan reporting lintas lifecycle

Deliverables:

- timeline per employee
- timeline per manpower request
- reporting:
  - time to hire
  - time to onboard
  - time to clear exit
  - replacement latency

## Test Strategy Sampai Root Case

### Unit / Feature

Per plugin wajib punya:

- creation path
- update path
- policy path
- install-state path
- soft delete / restore path bila relevan

### Cross-Plugin Feature Tests

Wajib tambah integration tests untuk:

1. approve manpower request -> posting dibuat
2. hire application -> employee dibuat
3. employee-linked exit request -> approver chain terbentuk
4. final approved exit -> employee di-offboard
5. replacement enabled -> replacement request dibuat
6. replacement disabled -> tidak ada request baru

### Root Case Tests

Wajib cover:

- kandidat di-hire dua kali
- satu manpower request butuh banyak hire
- company/department/job position sudah dihapus
- candidate email sama tapi orang berbeda
- employee punya user tapi user nonaktif
- exit request public tanpa employee link
- exit final approved tapi employee sudah nonaktif duluan
- replacement request dibuat dua kali untuk exit yang sama
- mapping string lama gagal ditemukan ke master HR

### E2E

Minimal E2E vision:

1. recruitment public/application flow
2. hire & onboard flow
3. employee appears in HR
4. exit clearance internal flow
5. archived and restore behavior
6. replacement request creation flow

## Governance Rules

- jangan merge phase berikutnya sebelum phase sebelumnya punya test stabil
- semua transisi lintas plugin harus lewat service
- semua integration point harus punya owner package yang jelas
- jangan gunakan migration yang merusak histori lama
- semua relasi penting harus nullable saat transisi awal agar rollout aman
- semua data baru yang kritikal harus punya snapshot dan FK

## Recommendation Final

Kalau targetnya benar-benar produk yang visioner dan tahan banting:

- jadikan `Employee` sebagai pusat lifecycle
- pertahankan `rekrutmen` sebagai upstream talent pipeline
- ubah `exit-clearance` menjadi employee-linked workflow untuk jalur internal
- perlakukan public flow sebagai manual/fallback path
- bangun orkestrasi secara eksplisit, bukan implicit

Kalau targetnya ingin cepat tapi tetap aman:

urutan implementasi terbaik adalah:

1. `JobApplication -> Employee`
2. `Employee -> ExitClearance`
3. `ExitClearance -> Replacement RequestManPower`

Itu memberi hasil bisnis nyata tanpa langsung membuat coupling yang berbahaya.
