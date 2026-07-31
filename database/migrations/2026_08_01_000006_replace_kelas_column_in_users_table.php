<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MENGUBAH SKEMA users - dampak ke data existing (Aturan poin 16).
 * Kolom 'kelas' (string bebas) DIHAPUS, diganti relasi
 * kelas_tahun_pelajaran_id + status_akademik. Data lama di kolom
 * 'kelas' string TIDAK dimigrasikan otomatis ke KTP (karena tidak ada
 * mapping otomatis nama-string -> Kelas/TahunPelajaran yang valid) -
 * WAJIB assignment ulang manual oleh Admin setelah migrasi ini jalan.
 *
 * TODO: GAP-SPEC - pertimbangkan backup nilai 'kelas' lama (mis. ke
 * kolom 'kelas_lama_arsip') sebelum drop, supaya Admin punya rujukan
 * saat assignment ulang manual. BELUM diimplementasikan di migration
 * ini - konfirmasi dulu apakah diperlukan sebelum dijalankan ke
 * production yang sudah ada data siswa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('kelas');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('kelas_tahun_pelajaran_id')->nullable()->after('jabatan');
            $table->foreign('kelas_tahun_pelajaran_id', 'users_ktp_fk')
                ->references('id')->on('kelas_tahun_pelajarans')->nullOnDelete();
            $table->string('status_akademik')->default('aktif')->after('kelas_tahun_pelajaran_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_ktp_fk');
            $table->dropColumn(['kelas_tahun_pelajaran_id', 'status_akademik']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('kelas')->nullable();
        });
    }
};
