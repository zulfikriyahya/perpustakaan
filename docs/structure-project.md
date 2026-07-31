.
├── app
│   ├── Console
│   │   └── Commands
│   │       └── ProsesCronHarianPerpustakaan.php
│   ├── Enums
│   │   ├── EventTypePoint.php
│   │   ├── GroupSetting.php
│   │   ├── JenisTransaksi.php
│   │   ├── KondisiBuku.php
│   │   ├── RoleUser.php
│   │   ├── SourceKunjungan.php
│   │   ├── StatusAkademik.php
│   │   ├── StatusPeminjaman.php
│   │   ├── StatusRefund.php
│   │   ├── StatusRiwayatKelas.php
│   │   └── TipeDenda.php
│   ├── Exceptions
│   │   └── WhatsappGatewayException.php
│   ├── Filament
│   │   ├── Exports
│   │   │   ├── BukuExporter.php
│   │   │   ├── KategoriExporter.php
│   │   │   ├── RakExporter.php
│   │   │   └── UserExporter.php
│   │   ├── Imports
│   │   │   ├── BukuImporter.php
│   │   │   ├── KategoriImporter.php
│   │   │   ├── RakImporter.php
│   │   │   └── UserImporter.php
│   │   ├── Pages
│   │   │   ├── Auth
│   │   │   │   ├── Login.php
│   │   │   │   ├── RequestPasswordReset.php
│   │   │   │   └── ResetPassword.php
│   │   │   ├── LaporanBulanan.php
│   │   │   ├── PengaturanSistem.php
│   │   │   ├── ProsesKenaikanKelas.php
│   │   │   └── TransaksiCepat.php
│   │   ├── Resources
│   │   │   ├── BukuResource
│   │   │   │   └── Pages
│   │   │   │       ├── CreateBuku.php
│   │   │   │       ├── EditBuku.php
│   │   │   │       └── ListBukus.php
│   │   │   ├── DendaResource
│   │   │   │   └── Pages
│   │   │   │       └── ListDendas.php
│   │   │   ├── JurusanResource
│   │   │   │   └── Pages
│   │   │   │       ├── CreateJurusan.php
│   │   │   │       ├── EditJurusan.php
│   │   │   │       └── ListJurusans.php
│   │   │   ├── KategoriResource
│   │   │   │   └── Pages
│   │   │   │       ├── CreateKategori.php
│   │   │   │       ├── EditKategori.php
│   │   │   │       └── ListKategoris.php
│   │   │   ├── KelasResource
│   │   │   │   └── Pages
│   │   │   │       ├── CreateKelas.php
│   │   │   │       ├── EditKelas.php
│   │   │   │       └── ListKelas.php
│   │   │   ├── KelasTahunPelajaranResource
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateKelasTahunPelajaran.php
│   │   │   │   │   ├── EditKelasTahunPelajaran.php
│   │   │   │   │   └── ListKelasTahunPelajarans.php
│   │   │   │   └── RelationManagers
│   │   │   │       └── SiswaAktifRelationManager.php
│   │   │   ├── KunjunganResource
│   │   │   │   └── Pages
│   │   │   │       └── ListKunjungans.php
│   │   │   ├── PeminjamanResource
│   │   │   │   └── Pages
│   │   │   │       ├── CreatePeminjaman.php
│   │   │   │       └── ListPeminjamans.php
│   │   │   ├── PengembalianResource
│   │   │   │   └── Pages
│   │   │   │       └── ListPengembalians.php
│   │   │   ├── RakResource
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateRak.php
│   │   │   │   │   ├── EditRak.php
│   │   │   │   │   └── ListRaks.php
│   │   │   │   └── RelationManagers
│   │   │   │       └── BukusRelationManager.php
│   │   │   ├── TahunPelajaranResource
│   │   │   │   └── Pages
│   │   │   │       ├── CreateTahunPelajaran.php
│   │   │   │       ├── EditTahunPelajaran.php
│   │   │   │       └── ListTahunPelajarans.php
│   │   │   ├── TransaksiResource
│   │   │   │   ├── Pages
│   │   │   │   │   ├── ListTransaksis.php
│   │   │   │   │   └── ViewTransaksi.php
│   │   │   │   └── RelationManagers
│   │   │   │       └── PeminjamansRelationManager.php
│   │   │   ├── UserResource
│   │   │   │   └── Pages
│   │   │   │       ├── CreateUser.php
│   │   │   │       ├── EditUser.php
│   │   │   │       └── ListUsers.php
│   │   │   ├── BukuResource.php
│   │   │   ├── DendaResource.php
│   │   │   ├── JurusanResource.php
│   │   │   ├── KategoriResource.php
│   │   │   ├── KelasResource.php
│   │   │   ├── KelasTahunPelajaranResource.php
│   │   │   ├── KunjunganResource.php
│   │   │   ├── PeminjamanResource.php
│   │   │   ├── PengembalianResource.php
│   │   │   ├── RakResource.php
│   │   │   ├── TahunPelajaranResource.php
│   │   │   ├── TransaksiResource.php
│   │   │   └── UserResource.php
│   │   └── Widgets
│   │       ├── DendaTerbaruWidget.php
│   │       ├── PeminjamanJatuhTempoWidget.php
│   │       ├── PeminjamanStatsWidget.php
│   │       └── TrenKunjunganChartWidget.php
│   ├── Http
│   │   ├── Controllers
│   │   │   ├── Api
│   │   │   │   └── PerpustakaanDeviceController.php
│   │   │   └── Controller.php
│   │   └── Middleware
│   │       └── AuthenticateDeviceApiKey.php
│   ├── Jobs
│   │   └── KirimNotifikasiWhatsapp.php
│   ├── Models
│   │   ├── Buku.php
│   │   ├── Denda.php
│   │   ├── DeviceLog.php
│   │   ├── FirmwareRelease.php
│   │   ├── Jurusan.php
│   │   ├── Kategori.php
│   │   ├── Kelas.php
│   │   ├── KelasTahunPelajaran.php
│   │   ├── Kunjungan.php
│   │   ├── LevelBadge.php
│   │   ├── PasswordResetOtp.php
│   │   ├── Peminjaman.php
│   │   ├── Pengembalian.php
│   │   ├── Point.php
│   │   ├── PunishmentLog.php
│   │   ├── Punishment.php
│   │   ├── Rak.php
│   │   ├── RewardLog.php
│   │   ├── Reward.php
│   │   ├── RiwayatKelasSiswa.php
│   │   ├── Setting.php
│   │   ├── TahunPelajaran.php
│   │   ├── Transaksi.php
│   │   └── User.php
│   ├── Observers
│   │   ├── DendaObserver.php
│   │   ├── SettingObserver.php
│   │   └── UserObserver.php
│   ├── Policies
│   │   ├── BukuPolicy.php
│   │   ├── DendaPolicy.php
│   │   ├── JurusanPolicy.php
│   │   ├── KategoriPolicy.php
│   │   ├── KelasPolicy.php
│   │   ├── KelasTahunPelajaranPolicy.php
│   │   ├── KunjunganPolicy.php
│   │   ├── PeminjamanPolicy.php
│   │   ├── PengembalianPolicy.php
│   │   ├── RakPolicy.php
│   │   ├── RolePolicy.php
│   │   ├── TahunPelajaranPolicy.php
│   │   ├── TransaksiPolicy.php
│   │   └── UserPolicy.php
│   ├── Providers
│   │   ├── Filament
│   │   │   └── DashboardPanelProvider.php
│   │   └── AppServiceProvider.php
│   ├── Rules
│   │   └── FormatKartuRfid.php
│   └── Services
│       ├── KenaikanKelasService.php
│       ├── LaporanBulananService.php
│       ├── PasswordResetOtpService.php
│       ├── PeminjamanService.php
│       ├── PointService.php
│       ├── RfidResolverService.php
│       └── WhatsappService.php
├── bootstrap
│   ├── cache
│   │   ├── .gitignore
│   │   ├── packages.php
│   │   └── services.php
│   ├── app.php
│   └── providers.php
├── config
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── database.php
│   ├── filament-shield.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── permission.php
│   ├── queue.php
│   ├── services.php
│   └── session.php
├── database
│   ├── factories
│   │   ├── BukuFactory.php
│   │   ├── DendaFactory.php
│   │   ├── KategoriFactory.php
│   │   ├── KunjunganFactory.php
│   │   ├── LevelBadgeFactory.php
│   │   ├── PeminjamanFactory.php
│   │   ├── PengembalianFactory.php
│   │   ├── PointFactory.php
│   │   ├── PunishmentFactory.php
│   │   ├── PunishmentLogFactory.php
│   │   ├── RakFactory.php
│   │   ├── RewardFactory.php
│   │   ├── RewardLogFactory.php
│   │   ├── SettingFactory.php
│   │   ├── TransaksiFactory.php
│   │   └── UserFactory.php
│   ├── migrations
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2026_07_29_180455_create_users_table.php
│   │   ├── 2026_07_29_180456_create_kategoris_table.php
│   │   ├── 2026_07_29_180457_create_raks_table.php
│   │   ├── 2026_07_29_180458_create_bukus_table.php
│   │   ├── 2026_07_29_180459_create_transaksis_table.php
│   │   ├── 2026_07_29_180500_create_peminjamans_table.php
│   │   ├── 2026_07_29_180501_create_pengembalians_table.php
│   │   ├── 2026_07_29_180502_create_dendas_table.php
│   │   ├── 2026_07_29_180503_create_points_table.php
│   │   ├── 2026_07_29_180504_create_level_badges_table.php
│   │   ├── 2026_07_29_180505_create_rewards_table.php
│   │   ├── 2026_07_29_180506_create_reward_logs_table.php
│   │   ├── 2026_07_29_180507_create_punishments_table.php
│   │   ├── 2026_07_29_180508_create_punishment_logs_table.php
│   │   ├── 2026_07_29_180509_create_kunjungans_table.php
│   │   ├── 2026_07_29_180510_create_settings_table.php
│   │   ├── 2026_07_29_180511_create_buku_kategori_table.php
│   │   ├── 2026_07_29_180512_create_kategori_rak_table.php
│   │   ├── 2026_07_29_181943_add_level_badge_fk_to_users_table.php
│   │   ├── 2026_07_29_222935_create_permission_tables.php
│   │   ├── 2026_07_30_000001_add_unique_user_tanggal_to_kunjungans_table.php
│   │   ├── 2026_07_30_000002_fix_unique_kunjungan_softdelete_aware.php
│   │   ├── 2026_07_30_000003_rename_nis_to_nisn_in_users_table.php
│   │   ├── 2026_07_30_000004_create_device_logs_table.php
│   │   ├── 2026_07_30_000005_create_firmware_releases_table.php
│   │   ├── 2026_07_30_000006_create_password_reset_otps_table.php
│   │   ├── 2026_07_30_000007_add_indexes_untuk_performa_query.php
│   │   ├── 2026_07_30_000008_add_status_refund_to_dendas_table.php
│   │   ├── 2026_07_31_051302_create_imports_table.php
│   │   ├── 2026_07_31_051303_create_exports_table.php
│   │   ├── 2026_07_31_051304_create_failed_import_rows_table.php
│   │   ├── 2026_07_31_052251_create_notifications_table.php
│   │   ├── 2026_08_01_000001_create_jurusans_table.php
│   │   ├── 2026_08_01_000002_create_tahun_pelajarans_table.php
│   │   ├── 2026_08_01_000003_create_kelas_table.php
│   │   ├── 2026_08_01_000004_create_kelas_tahun_pelajarans_table.php
│   │   ├── 2026_08_01_000005_create_riwayat_kelas_siswas_table.php
│   │   └── 2026_08_01_000006_replace_kelas_column_in_users_table.php
│   ├── seeders
│   │   ├── DatabaseSeeder.php
│   │   ├── SettingSeeder.php
│   │   └── ShieldSeeder.php
│   ├── database.sqlite
│   └── .gitignore
├── deploy
│   └── supervisor
│       └── perpustakaan-worker.conf
├── resources
│   ├── css
│   │   └── app.css
│   ├── js
│   │   └── app.js
│   └── views
│       ├── filament
│       │   └── pages
│       │       ├── auth
│       │       │   ├── request-password-reset.blade.php
│       │       │   └── reset-password.blade.php
│       │       ├── laporan-bulanan.blade.php
│       │       ├── pengaturan-sistem.blade.php
│       │       ├── proses-kenaikan-kelas.blade.php
│       │       └── transaksi-cepat.blade.php
│       ├── pdf
│       │   └── laporan-bulanan.blade.php
│       └── welcome.blade.php
├── routes
│   ├── api.php
│   ├── console.php
│   └── web.php
├── tests
│   ├── Feature
│   │   └── UserImporterTest.php
│   ├── Unit
│   │   └── PeminjamanServiceHariTelatTest.php
│   ├── CreatesApplication.php
│   └── TestCase.php
├── artisan
├── .blueprint
├── composer.json
├── composer.lock
├── draft.yaml
├── .editorconfig
├── .env
├── .env.example
├── .gitattributes
├── .gitignore
├── Makefile
├── .npmrc
├── package.json
├── .phpunit.result.cache
├── phpunit.xml
├── README.md
├── vite.config.js
└── yarn.lock

75 directories, 248 files
