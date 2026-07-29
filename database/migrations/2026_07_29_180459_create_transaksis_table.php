<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('jenis', ['peminjaman', 'kunjungan', 'pembayaran_denda'])->default('peminjaman');
            $table->foreignId('diproses_oleh')->nullable()->constrained('users');
            $table->dateTime('tanggal');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
