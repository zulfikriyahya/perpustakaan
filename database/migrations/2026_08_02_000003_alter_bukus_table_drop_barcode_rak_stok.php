<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // TODO: verifikasi driver DB (sqlite dev vs mysql prod) sebelum jalan -
    // dropColumn multi-kolom + foreign key butuh doctrine/dbal di SQLite
    // pada beberapa versi Laravel. Cek composer.json dulu.
    public function up(): void
    {
        Schema::table('bukus', function (Blueprint $table) {
            $table->dropForeign(['rak_id']);
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
