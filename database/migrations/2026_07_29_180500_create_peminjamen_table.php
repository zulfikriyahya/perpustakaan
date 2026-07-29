<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('peminjamen', function (Blueprint $table) {
            $table->uuid('id');
            $table->foreignUuid('transaksi_id')->constrained('transakses');
            $table->foreignId('user_id')->constrained('');
            $table->foreignUuid('buku_id')->constrained('bukuses');
            $table->date('tanggal_pinjam');
            $table->date('tanggal_jatuh_tempo');
            $table->enum('status', ["aktif","terlambat","selesai","hilang"])->default('aktif');
            $table->foreignId('diproses_oleh')->nullable()->constrained('users', 'oleh');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peminjamen');
    }
};
