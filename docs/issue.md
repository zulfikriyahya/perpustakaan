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
- Finalkan Fitur Akademik [import dan eksport, assign siswa ke kelas, assign wali kelas dan sebagainya]
- Perbaiki:
```md
# Illuminate\Contracts\Container\BindingResolutionException - Internal Server Error

Unable to resolve dependency [Parameter #0 [ <required> string $ktp ]] in class App\Filament\Pages\ProsesKenaikanKelas

PHP 8.4.22
Laravel 13.23.0
localhost:8000

## Stack Trace

0 - vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php:200
1 - vendor/livewire/livewire/src/ImplicitlyBoundMethod.php:29
2 - vendor/livewire/livewire/src/ImplicitlyBoundMethod.php:15
3 - vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php:36
4 - vendor/laravel/framework/src/Illuminate/Container/Util.php:43
5 - vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php:96
6 - vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php:35
7 - vendor/livewire/livewire/src/Wrapped.php:23
8 - vendor/livewire/livewire/src/Features/SupportLifecycleHooks/SupportLifecycleHooks.php:176
9 - vendor/livewire/livewire/src/Features/SupportLifecycleHooks/SupportLifecycleHooks.php:35
10 - vendor/livewire/livewire/src/ComponentHook.php:19
11 - vendor/livewire/livewire/src/ComponentHookRegistry.php:45
12 - vendor/livewire/livewire/src/EventBus.php:87
13 - vendor/livewire/livewire/src/helpers.php:98
14 - vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php:79
15 - vendor/livewire/livewire/src/LivewireManager.php:102
16 - vendor/livewire/livewire/src/Features/SupportPageComponents/HandlesPageComponents.php:19
17 - vendor/livewire/livewire/src/Features/SupportPageComponents/SupportPageComponents.php:118
18 - vendor/livewire/livewire/src/Features/SupportPageComponents/HandlesPageComponents.php:14
19 - vendor/laravel/framework/src/Illuminate/Routing/ControllerDispatcher.php:46
20 - vendor/laravel/framework/src/Illuminate/Routing/Route.php:276
21 - vendor/laravel/framework/src/Illuminate/Routing/Route.php:216
22 - vendor/laravel/framework/src/Illuminate/Routing/Router.php:822
23 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:180
24 - vendor/filament/filament/src/Http/Middleware/DispatchServingFilamentEvent.php:15
25 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
26 - vendor/filament/filament/src/Http/Middleware/DisableBladeIconComponents.php:14
27 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
28 - vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php:52
29 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
30 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestForgery.php:104
31 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
32 - vendor/laravel/framework/src/Illuminate/Session/Middleware/AuthenticateSession.php:70
33 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
34 - vendor/laravel/framework/src/Illuminate/Auth/Middleware/Authenticate.php:63
35 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
36 - vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php:48
37 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
38 - vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php:120
39 - vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php:63
40 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
41 - vendor/laravel/framework/src/Illuminate/Cookie/Middleware/AddQueuedCookiesToResponse.php:36
42 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
43 - vendor/laravel/framework/src/Illuminate/Cookie/Middleware/EncryptCookies.php:74
44 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
45 - vendor/filament/filament/src/Http/Middleware/SetUpPanel.php:19
46 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
47 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:137
48 - vendor/laravel/framework/src/Illuminate/Routing/Router.php:821
49 - vendor/laravel/framework/src/Illuminate/Routing/Router.php:800
50 - vendor/laravel/framework/src/Illuminate/Routing/Router.php:764
51 - vendor/laravel/framework/src/Illuminate/Routing/Router.php:753
52 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php:200
53 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:180
54 - vendor/livewire/livewire/src/Features/SupportDisablingBackButtonCache/DisableBackButtonCacheMiddleware.php:19
55 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
56 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php:21
57 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php:31
58 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
59 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php:21
60 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php:51
61 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
62 - vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php:27
63 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
64 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php:109
65 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
66 - vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php:61
67 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
68 - vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php:58
69 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
70 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php:22
71 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
72 - vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php:28
73 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
74 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:137
75 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php:175
76 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php:144
77 - vendor/laravel/framework/src/Illuminate/Foundation/Application.php:1227
78 - public/index.php:20
79 - vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php:23


## Request

GET /dashboard/proses-kenaikan-kelas

## Headers

* **host**: localhost:8000
* **user-agent**: Mozilla/5.0 (X11; Linux x86_64; rv:140.0) Gecko/20100101 Firefox/140.0
* **accept**: */*
* **accept-language**: id,en-US;q=0.7,en;q=0.3
* **accept-encoding**: gzip, deflate, br, zstd
* **referer**: http://localhost:8000/dashboard/kelas-tahun-pelajarans
* **x-livewire-navigate**: 1
* **connection**: keep-alive
* **cookie**: _ga=GA1.1.476939021.1778443708; perpustakaan-session=eyJpdiI6InRJRkJ0VnZ6ak1BVWFhaWlNdS8wbHc9PSIsInZhbHVlIjoiQjZGaWNGa1lPT2p6UjB4K0ZlSitiV1JteHpWY2lhZ2lHOTJURlVPbXdwSUVwdmp0d3RYaXg4Mmo3SGN5OWhzb0FLU0N4MzVaclkySS9IbnE1VElXUXVlbTJKckxRZW16d0YrYkF3Y1VId2UvS0FSMzk3TEwyc2lqTkp2dzI4YjAiLCJtYWMiOiI3MWFhN2IzOGU1ZGNhYmNjZmM5MDdiNmIyYzU4NjgxZjkwMDBlODY1N2YxOGQyODVhMDJhNmUwM2Q0ZGQzOGU1IiwidGFnIjoiIn0%3D; XSRF-TOKEN=eyJpdiI6IjRGdHREOXMxc3Z6ekpQRFY4QXpXcGc9PSIsInZhbHVlIjoibXlaQWJDZDluY1pqbmhQVEVrc0g0VFV0a2VWaUlaZklhNFJ5UkRmQ0p3V0p2RXRrQkkrWTFCdmVaZUhUcE0xWTVQUHZMNWVKN09WdlVScnFvNDZHNVRLYjNoK3g1OG1OOVBRSjJyeWsza09pSStVY2t2RmIxSFZTd2FPYWdJK2YiLCJtYWMiOiJjY2VjOGYxYTEyOTY1YTE3YjZmMzgyMTFiMDBhODIzZjY0MGU3NGYwMWZmMTczMTEyNjZhNWYyODVkOWFjMzE0IiwidGFnIjoiIn0%3D
* **sec-fetch-dest**: empty
* **sec-fetch-mode**: cors
* **sec-fetch-site**: same-origin
* **priority**: u=0

## Route Context

controller: App\Filament\Pages\ProsesKenaikanKelas
route name: filament.dashboard.pages.proses-kenaikan-kelas
middleware: panel:dashboard, Illuminate\Cookie\Middleware\EncryptCookies, Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse, Illuminate\Session\Middleware\StartSession, Filament\Http\Middleware\AuthenticateSession, Illuminate\View\Middleware\ShareErrorsFromSession, Illuminate\Foundation\Http\Middleware\PreventRequestForgery, Illuminate\Routing\Middleware\SubstituteBindings, Filament\Http\Middleware\DisableBladeIconComponents, Filament\Http\Middleware\DispatchServingFilamentEvent, Filament\Http\Middleware\Authenticate

## Route Parameters

No route parameter data available.

## Database Queries

* mysql - select * from `users` where `id` = 1 and `users`.`deleted_at` is null limit 1 (0.94 ms)

```

---

Lanjutkan/selesaikan implementasi proyek ini sesuai seluruh aturan di atas. Untuk setiap gap, jika penyelesaiannya memerlukan keputusan desain yang berdampak ke skema database, keamanan/otorisasi (Policy/Shield), atau kompatibilitas device RFID/firmware yang sudah terpasang di lapangan, **tanyakan secara eksplisit sebelum menulis kode** - jangan menebak lalu menyerahkan perubahan yang berisiko merusak data, akses user, atau koneksi device yang sudah berjalan di production.
