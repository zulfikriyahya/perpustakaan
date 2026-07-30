<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index tambahan sesuai Logic Module §11 checklist - kolom yang sering
 * di-query: filter status Peminjaman aktif/terlambat (cron harian, cek
 * limit peminjaman), sort/filter jatuh tempo (cron reminder), filter Denda
 * belum lunas (DendaObserver, halaman "denda saya"), dan unique-per-hari
 * Kunjungan (RFID). Additive only - tidak mengubah data/kolom existing,
 * aman rollback via dropIndex().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->index('status');
            $table->index('tanggal_jatuh_tempo');
        });

        Schema::table('dendas', function (Blueprint $table) {
            $table->index('status_lunas');
        });

        Schema::table('kunjungans', function (Blueprint $table) {
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['tanggal_jatuh_tempo']);
        });

        Schema::table('dendas', function (Blueprint $table) {
            $table->dropIndex(['status_lunas']);
        });

        Schema::table('kunjungans', function (Blueprint $table) {
            $table->dropIndex(['tanggal']);
        });
    }
};
