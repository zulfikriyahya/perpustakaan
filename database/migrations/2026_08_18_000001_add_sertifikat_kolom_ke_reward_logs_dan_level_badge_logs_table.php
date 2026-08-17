<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom sertifikat ke reward_logs dan level_badge_logs.
 * Non-destruktif - kedua kolom nullable, tidak mengubah/menghapus data
 * eksisting. Baris lama (sebelum migration ini) akan punya
 * sertifikat_path = null sampai suatu saat di-backfill manual (di luar
 * cakupan gap ini - tidak ada mekanisme backfill otomatis).
 *
 * Rollback aman: down() hanya drop kolom yang ditambahkan up() ini,
 * tidak menyentuh data/kolom lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reward_logs', function (Blueprint $table) {
            $table->string('sertifikat_path')->nullable()->after('tanggal_didapat');
            $table->string('nomor_sertifikat')->nullable()->after('sertifikat_path');
        });

        Schema::table('level_badge_logs', function (Blueprint $table) {
            $table->string('sertifikat_path')->nullable()->after('tanggal_didapat');
            $table->string('nomor_sertifikat')->nullable()->after('sertifikat_path');
        });
    }

    public function down(): void
    {
        Schema::table('reward_logs', function (Blueprint $table) {
            $table->dropColumn(['sertifikat_path', 'nomor_sertifikat']);
        });

        Schema::table('level_badge_logs', function (Blueprint $table) {
            $table->dropColumn(['sertifikat_path', 'nomor_sertifikat']);
        });
    }
};
