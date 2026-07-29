<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('peminjamans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaksi_id')->constrained('transaksis');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignUuid('buku_id')->constrained('bukus');
            $table->date('tanggal_pinjam');
            $table->date('tanggal_jatuh_tempo');
            $table->enum('status', ['aktif', 'terlambat', 'selesai', 'hilang'])->default('aktif');
            $table->foreignId('diproses_oleh')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjamans');
    }
};
