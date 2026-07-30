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
│   │   ├── StatusPeminjaman.php
│   │   ├── StatusRefund.php
│   │   └── TipeDenda.php
│   ├── Exceptions
│   │   └── WhatsappGatewayException.php
│   ├── Filament
│   │   ├── Pages
│   │   │   ├── Auth
│   │   │   │   ├── Login.php
│   │   │   │   ├── RequestPasswordReset.php
│   │   │   │   └── ResetPassword.php
│   │   │   └── TransaksiCepat.php
│   │   └── Resources
│   │       ├── BukuResource
│   │       │   └── Pages
│   │       │       ├── CreateBuku.php
│   │       │       ├── EditBuku.php
│   │       │       └── ListBukus.php
│   │       ├── KategoriResource
│   │       │   └── Pages
│   │       │       ├── CreateKategori.php
│   │       │       ├── EditKategori.php
│   │       │       └── ListKategoris.php
│   │       ├── PeminjamanResource
│   │       │   └── Pages
│   │       │       ├── CreatePeminjaman.php
│   │       │       └── ListPeminjamans.php
│   │       ├── PengembalianResource
│   │       │   └── Pages
│   │       │       └── ListPengembalians.php
│   │       ├── RakResource
│   │       │   ├── Pages
│   │       │   │   ├── CreateRak.php
│   │       │   │   ├── EditRak.php
│   │       │   │   └── ListRaks.php
│   │       │   └── RelationManagers
│   │       │       └── BukusRelationManager.php
│   │       ├── BukuResource.php
│   │       ├── KategoriResource.php
│   │       ├── PeminjamanResource.php
│   │       ├── PengembalianResource.php
│   │       └── RakResource.php
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
│   │   ├── Kategori.php
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
│   │   ├── Setting.php
│   │   ├── Transaksi.php
│   │   └── User.php
│   ├── Observers
│   │   ├── DendaObserver.php
│   │   ├── SettingObserver.php
│   │   └── UserObserver.php
│   ├── Policies
│   │   ├── BukuPolicy.php
│   │   ├── KategoriPolicy.php
│   │   ├── PeminjamanPolicy.php
│   │   ├── PengembalianPolicy.php
│   │   ├── RakPolicy.php
│   │   └── RolePolicy.php
│   ├── Providers
│   │   ├── Filament
│   │   │   └── DashboardPanelProvider.php
│   │   └── AppServiceProvider.php
│   ├── Rules
│   │   └── FormatKartuRfid.php
│   └── Services
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
│   │   └── 2026_07_30_000008_add_status_refund_to_dendas_table.php
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
│       │       └── transaksi-cepat.blade.php
│       └── welcome.blade.php
├── routes
│   ├── api.php
│   ├── console.php
│   └── web.php
├── tests
│   └── Unit
│       └── PeminjamanServiceHariTelatTest.php
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
├── phpunit.xml
├── README.md
├── vite.config.js
└── yarn.lock

52 directories, 169 files
