<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GAP-SPEC ditutup (dikonfirmasi user): force-delete Buku/Eksemplar yang
 * punya riwayat Peminjaman DIIZINKAN - eksemplar_id di riwayat Peminjaman
 * jadi null (Opsi B), bukan RESTRICT (penyebab error 1451 sebelumnya).
 * Riwayat Peminjaman/Denda/Point TETAP ada, hanya jejak eksemplar fisik
 * yang hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropForeign(['eksemplar_id']);
        });

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->uuid('eksemplar_id')->nullable()->change();
        });

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->foreign('eksemplar_id')
                ->references('id')->on('eksemplars')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropForeign(['eksemplar_id']);
        });

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->uuid('eksemplar_id')->nullable(false)->change();
        });

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->foreign('eksemplar_id')
                ->references('id')->on('eksemplars');
        });
    }
};
