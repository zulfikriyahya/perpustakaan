<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengembalians', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('peminjaman_id')->constrained('peminjamans');
            $table->date('tanggal_kembali');
            $table->enum('kondisi', ['baik', 'rusak', 'hilang'])->default('baik');
            $table->text('catatan')->nullable();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengembalians');
    }
};
