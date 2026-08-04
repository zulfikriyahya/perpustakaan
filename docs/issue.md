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

- unggah .mp3 atau .wav di daftar audio-book
- unggah .epub atau .pdf di daftar e-book
- fitur baca buku pdf di landing page (kalau bisa menggunakan seperti fliphtml5)

- membuat UI Landing Page
- halaman FAQ
- Halaman Tentang Perpustakaan
- Halaman Authors
- Metadata LLMS.txt | robots.txt
- Install PWA
- SEO Google
- halaman daftar buku pdf / e-book / audio-book

---

Lanjutkan/selesaikan implementasi proyek ini sesuai seluruh aturan di atas. Untuk setiap gap, jika penyelesaiannya memerlukan keputusan desain yang berdampak ke skema database, keamanan/otorisasi (Policy/Shield), atau kompatibilitas device RFID/firmware yang sudah terpasang di lapangan, **tanyakan secara eksplisit sebelum menulis kode** - jangan menebak lalu menyerahkan perubahan yang berisiko merusak data, akses user, atau koneksi device yang sudah berjalan di production.
