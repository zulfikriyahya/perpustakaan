<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_tahun_pelajarans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kelas_id');
            $table->foreign('kelas_id')->references('id')->on('kelas')->cascadeOnDelete();
            $table->uuid('tahun_pelajaran_id');
            $table->foreign('tahun_pelajaran_id')->references('id')->on('tahun_pelajarans')->cascadeOnDelete();
            $table->foreignId('wali_kelas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['kelas_id', 'tahun_pelajaran_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_tahun_pelajarans');
    }
};
