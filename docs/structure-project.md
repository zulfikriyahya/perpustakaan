.
├── app
│   ├── Console
│   │   └── Commands
│   │       ├── BackfillSnapshotHarian.php
│   │       └── ProsesCronHarianPerpustakaan.php
│   ├── Enums
│   │   ├── EventTypePoint.php
│   │   ├── GroupSetting.php
│   │   ├── JenisFileBuku.php
│   │   ├── JenisKelamin.php
│   │   ├── JenisTransaksi.php
│   │   ├── KondisiBuku.php
│   │   ├── RoleUser.php
│   │   ├── SourceKunjungan.php
│   │   ├── StatusAkademik.php
│   │   ├── StatusBulkJob.php
│   │   ├── StatusEksemplar.php
│   │   ├── StatusOtaFirmware.php
│   │   ├── StatusPeminjaman.php
│   │   ├── StatusRefund.php
│   │   ├── StatusRiwayatKelas.php
│   │   ├── TipeBulkJob.php
│   │   └── TipeDenda.php
│   ├── Exceptions
│   │   └── WhatsappGatewayException.php
│   ├── Filament
│   │   ├── Exports
│   │   │   ├── BukuExporter.php
│   │   │   ├── DendaExporter.php
│   │   │   ├── EksemplarExporter.php
│   │   │   ├── FirmwareReleaseExporter.php
│   │   │   ├── JurusanExporter.php
│   │   │   ├── KategoriExporter.php
│   │   │   ├── KelasExporter.php
│   │   │   ├── KelasTahunPelajaranExporter.php
│   │   │   ├── KunjunganExporter.php
│   │   │   ├── LevelBadgeExporter.php
│   │   │   ├── LevelBadgeLogExporter.php
│   │   │   ├── MasterDataExporter.php
│   │   │   ├── PeminjamanExporter.php
│   │   │   ├── PengembalianExporter.php
│   │   │   ├── PunishmentExporter.php
│   │   │   ├── PunishmentLogExporter.php
│   │   │   ├── RakExporter.php
│   │   │   ├── RewardExporter.php
│   │   │   ├── RewardLogExporter.php
│   │   │   ├── RiwayatKelasSiswaExporter.php
│   │   │   ├── TahunPelajaranExporter.php
│   │   │   ├── TransaksiExporter.php
│   │   │   └── UserExporter.php
│   │   ├── Imports
│   │   │   ├── BukuImporter.php
│   │   │   ├── EksemplarImporter.php
│   │   │   ├── JurusanImporter.php
│   │   │   ├── KategoriImporter.php
│   │   │   ├── KelasImporter.php
│   │   │   ├── KelasTahunPelajaranImporter.php
│   │   │   ├── LevelBadgeImporter.php
│   │   │   ├── PunishmentImporter.php
│   │   │   ├── RakImporter.php
│   │   │   ├── RewardImporter.php
│   │   │   ├── TahunPelajaranImporter.php
│   │   │   └── UserImporter.php
│   │   ├── Pages
│   │   │   ├── Auth
│   │   │   │   ├── Login.php
│   │   │   │   ├── RequestPasswordReset.php
│   │   │   │   └── ResetPassword.php
│   │   │   ├── Dashboard.php
│   │   │   ├── ImportExportMaster.php
│   │   │   ├── LaporanBulanan.php
│   │   │   ├── PengaturanSistem.php
│   │   │   ├── ProsesKenaikanKelas.php
│   │   │   └── TransaksiCepat.php
│   │   ├── Resources
│   │   │   ├── AuthorResource
│   │   │   │   └── Pages
│   │   │   │       ├── CreateAuthor.php
│   │   │   │       ├── EditAuthor.php
│   │   │   │       └── ListAuthors.php
│   │   │   ├── BukuResource
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateBuku.php
│   │   │   │   │   ├── EditBuku.php
│   │   │   │   │   └── ListBukus.php
│   │   │   │   ├── RelationManagers
│   │   │   │   │   └── EksemplarsRelationManager.php
│   │   │   │   └── Widgets
│   │   │   │       └── BukuStatsWidget.php
│   │   │   ├── DendaResource
│   │   │   │   ├── Pages
│   │   │   │   │   └── ListDendas.php
│   │   │   │   └── Widgets
│   │   │   │       └── DendaStatsWidget.php
│   │   │   ├── FirmwareResource
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateFirmwareRelease.php
│   │   │   │   │   ├── EditFirmwareRelease.php
│   │   │   │   │   └── ListFirmwareReleases.php
│   │   │   │   └── Widgets
│   │   │   │       └── FirmwareStatsWidget.php
│   │   │   ├── JurusanResource
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateJurusan.php
│   │   │   │   │   ├── EditJurusan.php
│   │   │   │   │   └── ListJurusans.php
│   │   │   │   └── Widgets
│   │   │   │       └── JurusanStatsWidget.php
│   │   │   ├── KategoriResource
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateKategori.php
│   │   │   │   │   ├── EditKategori.php
│   │   │   │   │   └── ListKategoris.php
│   │   │   │   └── Widgets
│   │   │   │       └── KategoriStatsWidget.php
│   │   │   ├── KelasResource
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateKelas.php
│   │   │   │   │   ├── EditKelas.php
│   │   │   │   │   └── ListKelas.php
│   │   │   │   └── Widgets
│   │   │   │       └── KelasStatsWidget.php
│   │   │   ├── KelasTahunPelajaranResource
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateKelasTahunPelajaran.php
│   │   │   │   │   ├── EditKelasTahunPelajaran.php
│   │   │   │   │   └── ListKelasTahunPelajarans.php
│   │   │   │   ├── RelationManagers
│   │   │   │   │   └── SiswaAktifRelationManager.php
│   │   │   │   └── Widgets
│   │   │   │       └── KelasTahunPelajaranStatsWidget.php
│   │   │   ├── KunjunganResource
│   │   │   │   ├── Pages
│   │   │   │   │   └── ListKunjungans.php
│   │   │   │   └── Widgets
│   │   │   │       └── KunjunganStatsWidget.php
│   │   │   ├── LevelBadgeLogResource
│   │   │   │   ├── Pages
│   │   │   │   │   └── ListLevelBadgeLogs.php
│   │   │   │   └── Widgets
│   │   │   │       └── LevelBadgeLogStatsWidget.php
│   │   │   ├── LevelBadgeResource
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateLevelBadge.php
│   │   │   │   │   ├── EditLevelBadge.php
│   │   │   │   │   └── ListLevelBadges.php
│   │   │   │   └── Widgets
│   │   │   │       └── LevelBadgeStatsWidget.php
│   │   │   ├── PeminjamanResource
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreatePeminjaman.php
│   │   │   │   │   └── ListPeminjamans.php
│   │   │   │   └── Widgets
│   │   │   │       └── PeminjamanOverviewWidget.php
│   │   │   ├── PengembalianResource
│   │   │   │   ├── Pages
│   │   │   │   │   └── ListPengembalians.php
│   │   │   │   └── Widgets
│   │   │   │       └── PengembalianStatsWidget.php
│   │   │   ├── PunishmentLogResource
│   │   │   │   ├── Pages
│   │   │   │   │   └── ListPunishmentLogs.php
│   │   │   │   └── Widgets
│   │   │   │       └── PunishmentLogStatsWidget.php
│   │   │   ├── PunishmentResource
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreatePunishment.php
│   │   │   │   │   ├── EditPunishment.php
│   │   │   │   │   └── ListPunishments.php
│   │   │   │   └── Widgets
│   │   │   │       └── PunishmentStatsWidget.php
│   │   │   ├── RakResource
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateRak.php
│   │   │   │   │   ├── EditRak.php
│   │   │   │   │   └── ListRaks.php
│   │   │   │   ├── RelationManagers
│   │   │   │   │   └── EksemplarsRelationManager.php
│   │   │   │   └── Widgets
│   │   │   │       └── RakStatsWidget.php
│   │   │   ├── RewardLogResource
│   │   │   │   ├── Pages
│   │   │   │   │   └── ListRewardLogs.php
│   │   │   │   └── Widgets
│   │   │   │       └── RewardLogStatsWidget.php
│   │   │   ├── RewardResource
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateReward.php
│   │   │   │   │   ├── EditReward.php
│   │   │   │   │   └── ListRewards.php
│   │   │   │   └── Widgets
│   │   │   │       └── RewardStatsWidget.php
│   │   │   ├── RiwayatKelasSiswaResource
│   │   │   │   ├── Pages
│   │   │   │   │   └── ListRiwayatKelasSiswas.php
│   │   │   │   └── Widgets
│   │   │   │       └── RiwayatKelasSiswaStatsWidget.php
│   │   │   ├── TahunPelajaranResource
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateTahunPelajaran.php
│   │   │   │   │   ├── EditTahunPelajaran.php
│   │   │   │   │   └── ListTahunPelajarans.php
│   │   │   │   └── Widgets
│   │   │   │       └── TahunPelajaranStatsWidget.php
│   │   │   ├── TransaksiResource
│   │   │   │   ├── Pages
│   │   │   │   │   ├── ListTransaksis.php
│   │   │   │   │   └── ViewTransaksi.php
│   │   │   │   ├── RelationManagers
│   │   │   │   │   └── PeminjamansRelationManager.php
│   │   │   │   └── Widgets
│   │   │   │       └── TransaksiStatsWidget.php
│   │   │   ├── UserResource
│   │   │   │   ├── Pages
│   │   │   │   │   ├── CreateUser.php
│   │   │   │   │   ├── EditUser.php
│   │   │   │   │   └── ListUsers.php
│   │   │   │   └── Widgets
│   │   │   │       └── UserStatsWidget.php
│   │   │   ├── AuthorResource.php
│   │   │   ├── BukuResource.php
│   │   │   ├── DendaResource.php
│   │   │   ├── FirmwareResource.php
│   │   │   ├── JurusanResource.php
│   │   │   ├── KategoriResource.php
│   │   │   ├── KelasResource.php
│   │   │   ├── KelasTahunPelajaranResource.php
│   │   │   ├── KunjunganResource.php
│   │   │   ├── LevelBadgeLogResource.php
│   │   │   ├── LevelBadgeResource.php
│   │   │   ├── PeminjamanResource.php
│   │   │   ├── PengembalianResource.php
│   │   │   ├── PunishmentLogResource.php
│   │   │   ├── PunishmentResource.php
│   │   │   ├── RakResource.php
│   │   │   ├── RewardLogResource.php
│   │   │   ├── RewardResource.php
│   │   │   ├── RiwayatKelasSiswaResource.php
│   │   │   ├── TahunPelajaranResource.php
│   │   │   ├── TransaksiResource.php
│   │   │   └── UserResource.php
│   │   ├── Support
│   │   │   └── GenericExportSheet.php
│   │   └── Widgets
│   │       ├── BukuPerKategoriWidget.php
│   │       ├── BukuRusakHilangWidget.php
│   │       ├── DendaTerbaruWidget.php
│   │       ├── GamifikasiBulananWidget.php
│   │       ├── PeminjamanJatuhTempoWidget.php
│   │       ├── PeminjamanStatsWidget.php
│   │       ├── PerJenisKelaminWidget.php
│   │       ├── TrenBulananWidget.php
│   │       └── WhatsappLogWidget.php
│   ├── Http
│   │   ├── Controllers
│   │   │   ├── Api
│   │   │   │   └── PerpustakaanDeviceController.php
│   │   │   ├── AuthorPublikController.php
│   │   │   ├── BukuPublikController.php
│   │   │   ├── BulkDataJobDownloadController.php
│   │   │   ├── ChartExportController.php
│   │   │   ├── Controller.php
│   │   │   ├── LandingPageController.php
│   │   │   └── SitemapController.php
│   │   └── Middleware
│   │       └── AuthenticateDeviceApiKey.php
│   ├── Jobs
│   │   ├── GenerateLabelBarcodePdfJob.php
│   │   ├── KirimNotifikasiWhatsapp.php
│   │   ├── ProcessMasterExportJob.php
│   │   └── ProcessMasterImportJob.php
│   ├── Models
│   │   ├── Author.php
│   │   ├── BukuFile.php
│   │   ├── BukuKategori.php
│   │   ├── Buku.php
│   │   ├── BulkDataJob.php
│   │   ├── Denda.php
│   │   ├── DeviceLog.php
│   │   ├── Eksemplar.php
│   │   ├── FirmwareRelease.php
│   │   ├── Jurusan.php
│   │   ├── Kategori.php
│   │   ├── Kelas.php
│   │   ├── KelasTahunPelajaran.php
│   │   ├── Kunjungan.php
│   │   ├── LevelBadgeLog.php
│   │   ├── LevelBadge.php
│   │   ├── LoginOtp.php
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
│   │   ├── SnapshotHarian.php
│   │   ├── TahunPelajaran.php
│   │   ├── Transaksi.php
│   │   ├── User.php
│   │   └── WhatsappLog.php
│   ├── Observers
│   │   ├── DendaObserver.php
│   │   ├── SettingObserver.php
│   │   └── UserObserver.php
│   ├── Policies
│   │   ├── AuthorPolicy.php
│   │   ├── BukuPolicy.php
│   │   ├── DendaPolicy.php
│   │   ├── EksemplarPolicy.php
│   │   ├── FirmwareReleasePolicy.php
│   │   ├── JurusanPolicy.php
│   │   ├── KategoriPolicy.php
│   │   ├── KelasPolicy.php
│   │   ├── KelasTahunPelajaranPolicy.php
│   │   ├── KunjunganPolicy.php
│   │   ├── LevelBadgeLogPolicy.php
│   │   ├── LevelBadgePolicy.php
│   │   ├── PeminjamanPolicy.php
│   │   ├── PengembalianPolicy.php
│   │   ├── PunishmentLogPolicy.php
│   │   ├── PunishmentPolicy.php
│   │   ├── RakPolicy.php
│   │   ├── RewardLogPolicy.php
│   │   ├── RewardPolicy.php
│   │   ├── RiwayatKelasSiswaPolicy.php
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
│   ├── Services
│   │   ├── BukuImportResolverService.php
│   │   ├── KenaikanKelasService.php
│   │   ├── LabelBarcodeService.php
│   │   ├── LaporanBulananService.php
│   │   ├── LoginOtpService.php
│   │   ├── PasswordResetOtpService.php
│   │   ├── PeminjamanService.php
│   │   ├── PointService.php
│   │   ├── RfidResolverService.php
│   │   ├── SnapshotHarianService.php
│   │   ├── UserImportResolverService.php
│   │   └── WhatsappService.php
│   └── Support
│       └── MasterDataRegistry.php
├── bootstrap
│   ├── cache
│   │   ├── filament
│   │   │   └── panels
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
│   ├── filament.php
│   ├── filament-shield.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── octane.php
│   ├── permission.php
│   ├── queue.php
│   ├── services.php
│   └── session.php
├── database
│   ├── factories
│   │   ├── BukuFactory.php
│   │   ├── DendaFactory.php
│   │   ├── EksemplarFactory.php
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
│   │   ├── 2026_08_01_000006_replace_kelas_column_in_users_table.php
│   │   ├── 2026_08_01_000007_drop_unique_riwayat_kelas_siswas.php
│   │   ├── 2026_08_02_000001_add_unique_nama_to_kelas_table.php
│   │   ├── 2026_08_02_000002_create_eksemplars_table.php
│   │   ├── 2026_08_02_000003_alter_bukus_table_drop_barcode_rak_stok.php
│   │   ├── 2026_08_02_000004_alter_peminjamans_table_buku_to_eksemplar.php
│   │   ├── 2026_08_02_000005_add_tahun_terbit_to_bukus_table.php
│   │   ├── 2026_08_02_000006_create_level_badge_logs_table.php
│   │   ├── 2026_08_02_000007_make_eksemplar_id_nullable_in_peminjamans_table.php
│   │   ├── 2026_08_02_000008_add_ota_report_columns_to_device_logs_table.php
│   │   ├── 2026_08_02_000009_create_login_otps_table.php
│   │   ├── 2026_08_02_000010_add_jenis_kelamin_to_users_table.php
│   │   ├── 2026_08_02_000011_create_whatsapp_logs_table.php
│   │   ├── 2026_08_02_000012_create_snapshot_harians_table.php
│   │   ├── 2026_08_03_000001_make_unique_constraints_soft_delete_aware.php
│   │   ├── 2026_08_03_000002_kelas_wajib_jurusan_unique_per_jurusan.php
│   │   ├── 2026_08_03_000003_optimalkan_index_query_panas.php
│   │   ├── 2026_08_04_000001_add_kredensial_ke_settings_table.php
│   │   ├── 2026_08_04_000002_create_bulk_data_jobs_table.php
│   │   ├── 2026_08_05_000001_create_authors_table.php
│   │   ├── 2026_08_05_000002_create_author_buku_table.php
│   │   └── 2026_08_05_000003_create_buku_files_table.php
│   ├── seeders
│   │   ├── DatabaseSeeder.php
│   │   ├── SettingSeeder.php
│   │   └── ShieldSeeder.php
│   └── .gitignore
├── resources
│   ├── css
│   │   └── app.css
│   ├── js
│   │   ├── app.js
│   │   └── chart-export.js
│   └── views
│       ├── buku
│       │   ├── baca-pdf.blade.php
│       │   └── index.blade.php
│       ├── components
│       │   └── layout.blade.php
│       ├── filament
│       │   ├── pages
│       │   │   ├── auth
│       │   │   │   ├── request-password-reset.blade.php
│       │   │   │   └── reset-password.blade.php
│       │   │   ├── import-export-master.blade.php
│       │   │   ├── laporan-bulanan.blade.php
│       │   │   ├── pengaturan-sistem.blade.php
│       │   │   ├── proses-kenaikan-kelas.blade.php
│       │   │   └── transaksi-cepat.blade.php
│       │   └── partials
│       │       ├── app-footer.blade.php
│       │       ├── auth-styles.blade.php
│       │       ├── chart-export-script.blade.php
│       │       ├── global-footer-style.blade.php
│       │       └── global-logo-style.blade.php
│       ├── partials
│       │   ├── public-footer.blade.php
│       │   └── public-nav.blade.php
│       ├── pdf
│       │   ├── chart-export.blade.php
│       │   ├── label-barcode.blade.php
│       │   └── laporan-bulanan.blade.php
│       ├── author-detail.blade.php
│       ├── authors.blade.php
│       ├── faq.blade.php
│       ├── index.blade.php
│       ├── sitemap.blade.php
│       └── tentang.blade.php
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
├── composer.json
├── composer.lock
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

121 directories, 412 files
