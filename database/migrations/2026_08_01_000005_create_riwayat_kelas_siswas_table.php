<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_kelas_siswas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('kelas_tahun_pelajaran_id');
            $table->foreign('kelas_tahun_pelajaran_id', 'rks_ktp_fk')
                ->references('id')->on('kelas_tahun_pelajarans')->cascadeOnDelete();
            $table->enum('status', ['aktif', 'naik', 'tinggal', 'lulus', 'keluar'])->default('aktif');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['user_id', 'kelas_tahun_pelajaran_id'], 'rks_user_ktp_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_kelas_siswas');
    }
};
