# Aturan

## Konteks Proyek
- Aplikasi Laravel + Filament "Sistem Perpustakaan" (Siswa, Pegawai, Pustakawan, Admin), menggunakan `spatie/laravel-permission` (via `filament-shield`), kemungkinan `spatie/laravel-activitylog`, Excel Import-Export (`filament-excel`), integrasi WhatsApp Gateway, serta device RFID Visitor Counter (tap kartu, sinkronisasi berkala via endpoint API khusus). Struktur folder eksisting (lihat tree project) adalah acuan konvensi penamaan - **jangan** memperkenalkan pola folder baru (mis. `app/Http/Resources`, `app/Http/Services`) tanpa alasan eksplisit.
- Dokumen **Logic Module Perpustakaan v1.0** (state machine Peminjaman, sistem Point/Badge/Reward/Punishment, Denda, RFID Visitor Counter, blueprint model) adalah acuan desain utama. Jika project ini digabung ke codebase "Sistem perpustakaan RFID" yang sudah ada, kode existing di sana (Model, Migration, Policy, Job) ikut menjadi sumber kebenaran tambahan dan wajib diselaraskan, bukan ditimpa diam-diam.

## 1. Acuan Utama
Semua implementasi mengikuti struktur dan konvensi kode existing secara ketat (satu Resource = satu domain: Pages + Widgets/RelationManagers di subfolder sesuai pola yang sudah ada). Jika ada bagian ambigu (mis. kapan `StatusPeminjaman` berubah, formula pasti `Denda.tipe = kerusakan`, threshold `Punishment` yang tumpang tindih), **jangan menebak diam-diam** - tandai eksplisit dengan `// TODO: ASUMSI - ...` beserta alasan.

## 2. Hindari Emoticon
Kode, komentar, pesan commit, notifikasi (termasuk pesan WhatsApp di `WhatsappService`/Job pengirim notifikasi) tetap formal.

## 3. Prinsip DRY - Satu Sumber Kebenaran
- Status/enum (`StatusPeminjaman`, `TipeDenda`, `KondisiBuku`, `JenisTransaksi`, `EventTypePoint`) **hanya** didefinisikan di `app/Enums` - jangan gunakan string literal (`'aktif'`, `'rusak'`, `'kehilangan'`, dst.) langsung di Resource/Job/Export lain.
- Logika perhitungan/penentuan status Peminjaman (Aktif → Terlambat/Selesai/Hilang) dan perhitungan Denda per tipe terpusat di `PeminjamanService` - Job/Cron (reminder H-3/H-1, transisi Terlambat, dsb.) memanggil service ini, bukan menduplikasi logika kalkulasi di masing-masing Job.
- Logika Point→akumulasi→cek threshold Badge/Reward/Punishment terpusat di `PointService` - setiap event (kunjungan, peminjaman, pengembalian, kerusakan, kehilangan) memanggil service ini, bukan menghitung akumulasi manual di Observer/Controller berbeda-beda.
- Logika update `User.status_suspend` **hanya** lewat Observer/Job terpusat berbasis status lunas `Denda` - field ini tidak boleh diedit manual dari Filament form.
- Logika pengiriman WhatsApp terpusat di `WhatsappService`/Job pengirim - jangan buat pemanggilan API WhatsApp langsung di tempat lain (Controller, Observer, dsb.).
- Validasi kartu RFID (identifikasi user saat tap, baik untuk Peminjaman maupun Kunjungan) terpusat via satu trait/service - jangan menulis ulang logika matching kartu-ke-user di banyak tempat, apalagi jika device fisik yang sama juga dipakai project perpustakaan.
- Sebelum menambah Resource/Job/Policy/Enum/Service baru, cek dulu apakah domain serupa sudah ada (mis. jangan buat `WhatsappService` kedua atau trait validasi kartu kedua jika sudah ada versi terpusat di project perpustakaan yang digabung).

## 4. Komentar Singkat
Komentar hanya sebagai penanda ringkas (mis. `// dihitung PeminjamanService`, `// trigger PointService event kerusakan`, `// di-guard oleh PeminjamanPolicy`), bukan narasi panjang.

## 5. Tandai Gap dengan TODO
Setiap keputusan sepihak (mis. apakah Denda kerusakan pakai persentase tetap atau dinamis, apakah Reward butuh klaim manual atau otomatis terealisasi, algoritma matching RFID-ke-user) wajib ditandai `// TODO: GAP-SPEC - ...` di lokasi kode terkait.

## 6. Tidak Ada File Placeholder Kosong
Jika menambah Resource/Page/Widget/RelationManager baru, ikuti struktur folder yang sudah ada persis (`XxxResource/Pages/`, `XxxResource/Widgets/`, `XxxResource/RelationManagers/`) - setiap file wajib berisi kode valid, jangan menyerahkan stub kosong yang membuat `composer dump-autoload` gagal.

## 7. Verifikasi API/Package Eksternal
Untuk pemanggilan method dari package eksternal yang dipakai project (Filament, Livewire, `spatie/laravel-permission`, `spatie/laravel-activitylog`, `bezhansalleh/filament-shield`, `maatwebsite/excel`/`filament-excel`, `laravel-shift/blueprint` bila masih dipakai untuk generate ulang, driver WhatsApp Gateway), verifikasi signature terhadap versi di `composer.json`/`composer.lock` **sebelum** menuliskan kode. Jika tidak bisa diverifikasi:
```php
// TODO: verifikasi signature terhadap versi package yang terpasang
```
Berlaku khusus untuk endpoint API device RFID Visitor Counter (routes API, controller terkait, middleware autentikasi device) - verifikasi kontrak request/response (format batch tap, format config `jam_operasional`/`interval_sync`) terhadap firmware yang sudah/akan terpasang di lapangan, jangan berasumsi dari kode lama karena ini memutus komunikasi dengan device fisik jika salah.

## 8. Kembangkan Bertahap, Build Harus Lolos
Setiap iterasi diasumsikan langsung dijalankan lewat `composer install` / `php artisan migrate` / `php artisan serve` (atau sesuai `Makefile` yang berlaku). Jangan menyerahkan kode yang jelas belum lengkap (method dipanggil tapi belum didefinisikan, `use` hilang, migration belum dibuat, job/cron belum di-register di scheduler) tanpa peringatan eksplisit bahwa aplikasi akan error dan alasannya.

## 9. Perbaikan Kecil → Full Fungsi/Method
Jika perubahan hanya sedikit, kirim versi lengkap method/class beserta lokasi file (mis. `app/Filament/Resources/PeminjamanResource.php`, method `table()`).

## 10. Perbaikan Besar → File Penuh
Jika perubahan signifikan (mis. refactor `PeminjamanService`, refactor relasi Buku-Rak-Kategori, restructure Policy per role), kirim keseluruhan file terkait, bukan potongan parsial.

## 11. Telusuri Semua Pemakaian Simbol
Jika nama kolom/method/Enum case diganti (mis. rename status di `StatusPeminjaman`, ganti nama relasi `Buku↔Rak`), telusuri dan perbaiki **semua** titik pemakaian: Model, Resource (table/form), Widget (StatsOverview/Chart), Export, Job/Cron, Policy, dan view Blade (jika ada, mis. struk peminjaman) dalam balasan yang sama.

## 12. Verifikasi Berlapis Sebelum Menyatakan "Selesai"
Jangan menyatakan fitur "final"/"solid" hanya berdasarkan tinjauan statis. Nyatakan status jujur:
- Apakah sudah dijalankan (`php artisan migrate`, `php artisan queue:work`) oleh pengguna?
- Apakah sudah diuji end-to-end dengan device fisik (tap RFID untuk Kunjungan/Peminjaman) atau minimal simulasi request API?
- Apakah cron harian (reminder H-3/H-1, transisi Terlambat, cek threshold Badge/Reward/Punishment) sudah diverifikasi jalan di scheduler/queue worker, bukan cuma didefinisikan tanpa eksekusi?
- Apakah permission/Shield role (Siswa/Pegawai/Pustakawan/Admin) sudah disesuaikan (`ShieldSeeder`) jika ada resource/aksi baru?
- Apa saja asumsi yang masih menunggu konfirmasi?

## 13. Target Akhir
Aplikasi stabil, tidak menimbulkan regresi pada mekanisme otomatis (cron reminder, transisi status Peminjaman, perhitungan Denda, update Point/Badge/status_suspend), notifikasi WhatsApp, dan integrasi device RFID Visitor Counter. Jika device fisik dipakai bersama project perpustakaan RFID, perubahan tidak boleh memutus kompatibilitas terhadap device/firmware yang sudah aktif di lapangan.

## 14. Struktur Balasan Konsisten
0. **(Jika file referensi belum ada di sesi)** Daftar perintah `cat` untuk file yang perlu dilihat - hentikan balasan di sini sampai isi file diberikan, jangan lanjut menebak ke poin 1-4 di bawah.
1. Ringkasan singkat perubahan/penambahan.
2. Kode (sesuai poin 9/10).
3. Daftar file/class yang terdampak (termasuk apakah perlu migration baru atau update `ShieldSeeder`).
4. Status verifikasi (poin 12) - termasuk gap mana yang tertutup dan mana yang masih terbuka.

## 15. Jangan Berasumsi Environment
Jika versi Filament, versi Laravel, driver database, atau perilaku WhatsApp Gateway/vendor API device memengaruhi solusi, tanyakan dulu daripada menebak - kecuali sudah dinyatakan sebelumnya dalam sesi. **Termasuk**: apakah project Perpustakaan ini berdiri sendiri atau digabung ke codebase Sistem perpustakaan RFID yang sudah ada (mempengaruhi konvensi `User`, UUID vs bigint, dan apakah device RFID dipakai bersama).

## 16. Perubahan Skema Database Wajib Eksplisit
Jika perbaikan mengubah struktur tabel (terutama tabel `users` bila digabung dengan project perpustakaan yang sudah punya data produksi), harus dinyatakan eksplisit sebagai migration baru dengan strategi rollback aman - karena berdampak ke data peminjaman/denda/point/kartu yang sudah tersimpan dan dipakai sekolah/instansi secara real-time.

## 17. Kontrak Keamanan & Kompatibilitas Device Mengikat
- Setiap perubahan pada middleware autentikasi API device, endpoint sinkronisasi RFID Visitor Counter, atau kontrak data (format batch tap, format config `jam_operasional`/`interval_sync`) harus dinyatakan dampaknya terhadap device yang sudah terpasang di lapangan - device lama tidak boleh tiba-tiba gagal autentikasi/parsing response.
- Setiap perubahan Policy/Permission (Shield) harus dinyatakan dampaknya terhadap role/user existing (mis. Pustakawan kehilangan akses ke Resource tertentu).
- Perubahan skema enkripsi/kredensial WhatsApp harus dinyatakan dampaknya terhadap sesi/koneksi WhatsApp yang sedang aktif.
- Jika device fisik/kartu RFID dipakai bersama project perpustakaan RFID, perubahan kontrak apa pun **mengikat kedua project** dan wajib dinyatakan eksplisit sebelum ditulis.

Penyimpangan dari poin di atas dianggap bug kritis, bukan perbaikan kosmetik.

## 18. Minta Referensi File Sebelum Menjawab Gap
Sebelum mulai menganalisis/menulis kode untuk sebuah issue/gap yang diajukan, **jangan langsung menebak isi file terkait dari nama/struktur folder saja**. Tentukan dulu file-file mana yang relevan untuk dilihat (Model, Policy, Job, Resource, Migration, Service, dsb. yang kemungkinan terkait), lalu minta isinya dalam bentuk perintah `cat` yang bisa langsung dijalankan pengguna, contoh:

```bash
cat app/Models/Peminjaman.php
cat app/Services/PeminjamanService.php
cat app/Filament/Resources/PeminjamanResource.php
cat database/migrations/*_create_peminjamans_table.php
```

Ketentuan:
- Kelompokkan dalam **satu blok bash** agar bisa dijalankan sekali jalan, jangan diminta satu-satu bolak-balik kecuali file lanjutan baru diketahui butuh setelah melihat isi file pertama.
- Prioritaskan file yang **langsung** disebut/terdampak oleh gap, lalu file yang **terhubung** (mis. kalau gap soal `PeminjamanResource`, ikut minta Model, Policy, dan Service terkait - bukan seluruh folder `Filament/Resources`).
- Jika gap juga menyinggung kontrak API device RFID (lihat poin 7/17), ikut minta file route dan controller terkait meski tidak disebut eksplisit dalam issue.
- Jika file yang diminta ternyata tidak ada / pengguna belum bisa memberikan, jangan berasumsi isinya - nyatakan bahwa keputusan/kode masih tertunda sampai isi file tersedia (kecuali kasusnya memang file baru yang akan dibuat dari nol).
- Pengecualian: jika pengguna sudah melampirkan isi file yang relevan di pesan sebelumnya dalam sesi yang sama, tidak perlu meminta ulang - cukup konfirmasi singkat bahwa referensi tersebut masih dipakai.

---

# Fitur/Gap yang ingin ditutup pada iterasi ini
- Finalkan Fitur Point, Reward dan Punishment
- Perbaiki:
```markdown
# Illuminate\Database\Eloquent\RelationNotFoundException - Internal Server Error

Call to undefined relationship [buku] on model [App\Models\Peminjaman].

PHP 8.4.22
Laravel 13.23.0
localhost:8000

## Stack Trace

0 - vendor/laravel/framework/src/Illuminate/Database/Eloquent/RelationNotFoundException.php:35
1 - vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php:975
2 - vendor/laravel/framework/src/Illuminate/Database/Eloquent/Relations/Relation.php:119
3 - vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php:971
4 - vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php:945
5 - vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php:925
6 - vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php:891
7 - vendor/laravel/framework/src/Illuminate/Database/Eloquent/Relations/Relation.php:212
8 - vendor/laravel/framework/src/Illuminate/Database/Eloquent/Relations/Relation.php:175
9 - vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php:956
10 - vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php:925
11 - vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php:891
12 - app/Services/LaporanBulananService.php:77
13 - app/Services/LaporanBulananService.php:35
14 - app/Filament/Pages/LaporanBulanan.php:76
15 - vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php:36
16 - vendor/laravel/framework/src/Illuminate/Container/Util.php:43
17 - vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php:96
18 - vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php:35
19 - vendor/livewire/livewire/src/Wrapped.php:23
20 - vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php:708
21 - vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php:242
22 - vendor/livewire/livewire/src/LivewireManager.php:131
23 - vendor/livewire/livewire/src/Mechanisms/HandleRequests/HandleRequests.php:205
24 - vendor/laravel/framework/src/Illuminate/Routing/ControllerDispatcher.php:46
25 - vendor/laravel/framework/src/Illuminate/Routing/Route.php:276
26 - vendor/laravel/framework/src/Illuminate/Routing/Route.php:216
27 - vendor/laravel/framework/src/Illuminate/Routing/Router.php:822
28 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:180
29 - vendor/livewire/livewire/src/Mechanisms/HandleRequests/RequireLivewireHeaders.php:19
30 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
31 - vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php:52
32 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
33 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestForgery.php:104
34 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
35 - vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php:48
36 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
37 - vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php:120
38 - vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php:63
39 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
40 - vendor/laravel/framework/src/Illuminate/Cookie/Middleware/AddQueuedCookiesToResponse.php:36
41 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
42 - vendor/laravel/framework/src/Illuminate/Cookie/Middleware/EncryptCookies.php:74
43 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
44 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:137
45 - vendor/laravel/framework/src/Illuminate/Routing/Router.php:821
46 - vendor/laravel/framework/src/Illuminate/Routing/Router.php:800
47 - vendor/laravel/framework/src/Illuminate/Routing/Router.php:764
48 - vendor/laravel/framework/src/Illuminate/Routing/Router.php:753
49 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php:200
50 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:180
51 - vendor/livewire/livewire/src/Features/SupportDisablingBackButtonCache/DisableBackButtonCacheMiddleware.php:19
52 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
53 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php:27
54 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
55 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php:47
56 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
57 - vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php:27
58 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
59 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php:109
60 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
61 - vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php:61
62 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
63 - vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php:58
64 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
65 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php:22
66 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
67 - vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php:28
68 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
69 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:137
70 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php:175
71 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php:144
72 - vendor/laravel/framework/src/Illuminate/Foundation/Application.php:1227
73 - public/index.php:20
74 - vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php:23


## Request

POST /livewire-f8c356db/update

## Headers

* **host**: localhost:8000
* **user-agent**: Mozilla/5.0 (X11; Linux x86_64; rv:140.0) Gecko/20100101 Firefox/140.0
* **accept**: */*
* **accept-language**: id,en-US;q=0.7,en;q=0.3
* **accept-encoding**: gzip, deflate, br, zstd
* **content-type**: application/json
* **x-livewire**: 1
* **referer**: http://localhost:8000/dashboard/laporan-bulanan
* **content-length**: 1005
* **origin**: http://localhost:8000
* **connection**: keep-alive
* **cookie**: _ga=GA1.1.476939021.1778443708; perpustakaan-session=eyJpdiI6ImROTTRSa1NjK3RCaDRWakZoRks1enc9PSIsInZhbHVlIjoid0dCVi9qNmdaMzVTR01CenhLeFFNM3FFRHoyWGVxMXZzLzVsaUxJbldVZkU1MTJFOWZtYVJzcVk1Nm1QM2ZjQkN3dGhzL3ArNVJyeElNc3lnWG5kSW5FclhDVkNYejQrb1k3UTdnL2QrL1loL1JqZ3BBTDdJTUcwOTRuVG1kS2oiLCJtYWMiOiI4MTdkMjZiMDFhZDcwZTE5MzU2N2M5NjhiZDY2ZWE4OTVjZWE4NTVjOGM3OGE4MjFiMzY3NDMwZGNiYjBiYmM0IiwidGFnIjoiIn0%3D; XSRF-TOKEN=eyJpdiI6ImdpYjB5djM5TVptS2REZjlONStKM2c9PSIsInZhbHVlIjoibjU1M1VpOFV3d0ZrRTBxeVFvNHlLdmVsMEZDdEZPVXhkbFNCeVdWTit3Qk5BMW0rZGpJVEQvbU96dVh1NklpWGp0Mk0veVRHWE1zMjN5QXpNTExvQ3lKN25iL0ppNUhxdzZ6ZHdidlQvOHdVRWJ6Wlh5cCsyMEpRdXFSVks2bWwiLCJtYWMiOiIzNjQ3ODhmYTIzNWExZWQzODVkYWI2MTRhNGM1MGZiNjFjZTUxMjc3Mjk3ZDFiNjVhYjg4MjZjOGNhOWE3ZTZjIiwidGFnIjoiIn0%3D
* **sec-fetch-dest**: empty
* **sec-fetch-mode**: cors
* **sec-fetch-site**: same-origin
* **priority**: u=4

## Route Context

controller: Livewire\Mechanisms\HandleRequests\HandleRequests@handleUpdate
route name: default-livewire.update
middleware: web, Livewire\Mechanisms\HandleRequests\RequireLivewireHeaders

## Route Parameters

No route parameter data available.

## Database Queries

* mysql - select * from `users` where `id` = 1 and `users`.`deleted_at` is null limit 1 (1.39 ms)
* mysql - select `permissions`.*, `model_has_permissions`.`model_id` as `pivot_model_id`, `model_has_permissions`.`permission_id` as `pivot_permission_id`, `model_has_permissions`.`model_type` as `pivot_model_type` from `permissions` inner join `model_has_permissions` on `permissions`.`id` = `model_has_permissions`.`permission_id` where `model_has_permissions`.`model_id` in (1) and `model_has_permissions`.`model_type` = 'App\Models\User' (0.55 ms)
* mysql - select `roles`.*, `model_has_roles`.`model_id` as `pivot_model_id`, `model_has_roles`.`role_id` as `pivot_role_id`, `model_has_roles`.`model_type` as `pivot_model_type` from `roles` inner join `model_has_roles` on `roles`.`id` = `model_has_roles`.`role_id` where `model_has_roles`.`model_id` in (1) and `model_has_roles`.`model_type` = 'App\Models\User' (0.43 ms)
* mysql - select * from `peminjamans` where `tanggal_pinjam` between '2026-08-01' and '2026-08-31' and `peminjamans`.`deleted_at` is null order by `tanggal_pinjam` asc (0.48 ms)
* mysql - select * from `users` where `users`.`id` in (2) and `users`.`deleted_at` is null (0.34 ms)
* mysql - select * from `eksemplars` where `eksemplars`.`id` in ('019fb90a-a9e8-7029-81b4-65fd5e3de702', '019fb90a-a9ea-71ae-8931-62ea46fbee3f', '019fb90a-a9ec-7096-97e0-d7a58109c652') and `eksemplars`.`deleted_at` is null (0.41 ms)
* mysql - select * from `bukus` where `bukus`.`id` in ('019fb90a-a9de-7221-bb1e-89ba6f40a0f6') and `bukus`.`deleted_at` is null (0.36 ms)
* mysql - select * from `pengembalians` where `tanggal_kembali` between '2026-08-01' and '2026-08-31' and `pengembalians`.`deleted_at` is null order by `tanggal_kembali` asc (0.53 ms)
* mysql - select * from `peminjamans` where `peminjamans`.`id` in ('019fb916-7155-7281-b722-c0add801b695', '019fb916-715f-7229-aded-43f44161a6e9', '019fb928-3a21-7371-a392-7af7fc5874ab', '019fb928-4c02-70d2-be96-ca18870466f8', '019fb928-6123-70e1-bb58-925239f5f1b2', '019fb92c-0d90-7198-aeaf-e3320d127075', '019fb92c-79dd-7045-b518-b12b390f21ba', '019fb93a-dd83-73d0-8ddc-bf3266b45edc') and `peminjamans`.`deleted_at` is null (0.49 ms)
* mysql - select * from `users` where `users`.`id` in (1, 2) and `users`.`deleted_at` is null (0.5 ms)
* mysql - select * from `eksemplars` where `eksemplars`.`id` in ('019fb90a-a9e8-7029-81b4-65fd5e3de702', '019fb90a-a9ea-71ae-8931-62ea46fbee3f', '019fb90a-a9ec-7096-97e0-d7a58109c652') and `eksemplars`.`deleted_at` is null (0.45 ms)
* mysql - select * from `bukus` where `bukus`.`id` in ('019fb90a-a9de-7221-bb1e-89ba6f40a0f6') and `bukus`.`deleted_at` is null (0.55 ms)
* mysql - select * from `dendas` where `created_at` between '2026-08-01 00:00:00' and '2026-08-31 23:59:59' and `dendas`.`deleted_at` is null order by `created_at` asc (0.72 ms)
* mysql - select * from `users` where `users`.`id` in (2) and `users`.`deleted_at` is null (0.48 ms)
* mysql - select * from `peminjamans` where `peminjamans`.`id` in ('019fb94b-5f02-724b-8d3b-8f1d6fc509ae') and `peminjamans`.`deleted_at` is null (0.53 ms)

```

---

Lanjutkan/selesaikan implementasi proyek ini sesuai seluruh aturan di atas. Untuk setiap gap, jika penyelesaiannya memerlukan keputusan desain yang berdampak ke skema database, keamanan/otorisasi (Policy/Shield), atau kompatibilitas device RFID/firmware yang sudah terpasang di lapangan, **tanyakan secara eksplisit sebelum menulis kode** - jangan menebak lalu menyerahkan perubahan yang berisiko merusak data, akses user, atau koneksi device yang sudah berjalan di production.
