<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            // Lapisan kedua di DB selain validasi unik-per-hari di device (lihat Logic
            // Module bagian 6). SoftDeletes tidak diikutsertakan di index ini secara
            // sengaja - lihat TODO: GAP-SPEC di bawah.
            $table->unique(['user_id', 'tanggal'], 'kunjungans_user_tanggal_unique');
        });
    }

    public function down(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->dropUnique('kunjungans_user_tanggal_unique');
        });
    }
};
