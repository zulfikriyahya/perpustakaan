<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Dipecah jadi beberapa Schema::table() terpisah + dropUnique()
    // eksplisit sebelum dropColumn() - pada SQLite (dipakai test suite
    // via RefreshDatabase), rebuild tabel yang dipicu dropColumn() gagal
    // mencoba menyalin ulang unique index kolom yang sedang didrop dalam
    // command yang sama ("no such column: barcode" walau kolom itu masih
    // ada saat command dimulai). Drop index-nya lebih dulu secara terpisah
    // menghindari race ini. Hasil akhir skema TETAP SAMA baik di MySQL
    // maupun SQLite - murni pengelompokan command, bukan perubahan skema.
    // Migration ini sudah "Ran" di production MySQL (tidak dieksekusi
    // ulang di sana) - perubahan ini hanya berdampak pada fresh install
    // baru (termasuk test suite).
    public function up(): void
    {
        Schema::table('bukus', function (Blueprint $table) {
            $table->dropForeign(['rak_id']);
        });

        Schema::table('bukus', function (Blueprint $table) {
            $table->dropUnique(['barcode']);
        });

        Schema::table('bukus', function (Blueprint $table) {
            $table->dropColumn(['barcode', 'rak_id', 'stok']);
        });
    }

    public function down(): void
    {
        Schema::table('bukus', function (Blueprint $table) {
            $table->string('barcode')->unique()->nullable();
            $table->foreignUuid('rak_id')->nullable()->constrained('raks');
            $table->integer('stok')->default(1);
        });
    }
};
