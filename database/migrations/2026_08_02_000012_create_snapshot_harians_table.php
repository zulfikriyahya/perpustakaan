<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel baru murni - tidak mengubah tabel/data existing (poin 16 Aturan).
 * Satu baris per tanggal, diisi SnapshotHarianService::catatUntukTanggal()
 * lewat cron harian (hari berjalan) dan command backfill (histori).
 * Menggantikan query agregat berulang di PeminjamanStatsWidget yang
 * sebelumnya dihitung ulang setiap dashboard dibuka (Aturan poin 3/9 -
 * performa untuk skala data besar).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('snapshot_harians', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->unsignedInteger('peminjaman_baru')->default(0);
            $table->unsignedInteger('peminjaman_terlambat')->default(0);
            $table->unsignedInteger('denda_baru')->default(0);
            $table->unsignedInteger('kunjungan')->default(0);
            $table->unsignedInteger('total_judul_buku')->default(0);
            $table->unsignedInteger('total_anggota_aktif')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snapshot_harians');
    }
};
