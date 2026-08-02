<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index tambahan berdasarkan AUDIT QUERY AKTUAL (bukan tebakan dari nama
 * kolom migration) - lihat DendaObserver::updated(),
 * PeminjamanService::bisaMeminjam(), PerpustakaanDeviceController
 * (syncBulk/kirimLangsung - HOT PATH tap RFID), PeminjamanJatuhTempoWidget,
 * DendaTerbaruWidget, DendaStatsWidget.
 *
 * Additive only - tidak mengubah data/kolom existing. Index tunggal yang
 * jadi FULLY REDUNDANT setelah composite ditambahkan (di-drop untuk
 * menghindari beban write index ganda tanpa manfaat baca tambahan):
 * - dendas.status_lunas (single) -> tercakup composite (status_lunas, created_at)
 * - peminjamans.status (single) -> tercakup composite (status, tanggal_jatuh_tempo)
 *
 * peminjamans.tanggal_jatuh_tempo (single) SENGAJA DIPERTAHANKAN - tidak
 * ada query saat ini yang filter kolom ini sendirian tanpa status, tapi
 * risiko menyimpannya rendah dan berguna untuk kebutuhan sort/report masa
 * depan (mis. laporan jatuh tempo lintas status).
 *
 * kunjungans: index user_id/tanggal single YANG SUDAH ADA TIDAK DISENTUH
 * (bagian dari setup generated-column unique yang sensitif - lihat
 * migration fix_unique_kunjungan_softdelete_aware) - composite baru di
 * sini murni tambahan untuk mempercepat query duplikat harian yang
 * dipanggil setiap tap device RFID.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->index(['user_id', 'tanggal'], 'kunjungans_user_id_tanggal_idx');
        });

        Schema::table('dendas', function (Blueprint $table) {
            $table->dropIndex(['status_lunas']);
            $table->index(['user_id', 'status_lunas'], 'dendas_user_id_status_lunas_idx');
            $table->index(['status_lunas', 'created_at'], 'dendas_status_lunas_created_at_idx');
        });

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->index(['user_id', 'status'], 'peminjamans_user_id_status_idx');
            $table->index(['status', 'tanggal_jatuh_tempo'], 'peminjamans_status_tanggal_jatuh_tempo_idx');
        });
    }

    public function down(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropIndex('peminjamans_status_tanggal_jatuh_tempo_idx');
            $table->dropIndex('peminjamans_user_id_status_idx');
            $table->index('status');
        });

        Schema::table('dendas', function (Blueprint $table) {
            $table->dropIndex('dendas_status_lunas_created_at_idx');
            $table->dropIndex('dendas_user_id_status_lunas_idx');
            $table->index('status_lunas');
        });

        Schema::table('kunjungans', function (Blueprint $table) {
            $table->dropIndex('kunjungans_user_id_tanggal_idx');
        });
    }
};
