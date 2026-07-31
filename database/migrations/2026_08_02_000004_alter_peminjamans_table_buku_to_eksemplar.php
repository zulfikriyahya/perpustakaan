<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropForeign(['buku_id']);
            $table->dropColumn('buku_id');
            $table->foreignUuid('eksemplar_id')->after('transaksi_id')->constrained('eksemplars');
        });
    }

    public function down(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropForeign(['eksemplar_id']);
            $table->dropColumn('eksemplar_id');
            $table->foreignUuid('buku_id')->constrained('bukus');
        });
    }
};
