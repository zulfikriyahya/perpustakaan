<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log historis perubahan LevelBadge user (Aturan poin 3 - DRY, mengikuti
 * pola RewardLog/PunishmentLog). Kolom users.level_badge_id TETAP ada dan
 * TETAP jadi sumber snapshot terkini (dipakai PointService::cekBadge()
 * untuk cek cepat tanpa query log) - tabel ini murni tambahan append-only,
 * TIDAK mengubah struktur/data tabel users.
 *
 * TODO: GAP-SPEC - histori baru mulai tercatat sejak migration ini
 * dijalankan (dikonfirmasi user). Badge yang sudah nempel ke user
 * SEBELUM migration ini tidak akan muncul di riwayat karena memang belum
 * pernah ter-log.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('level_badge_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignUuid('level_badge_id')->constrained('level_badges');
            $table->dateTime('tanggal_didapat');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('level_badge_logs');
    }
};
