<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahun_pelajarans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama')->unique(); // mis. "2025/2026"
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->boolean('aktif')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahun_pelajarans');
    }
};
