<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PERUBAHAN SKEMA - riwayat_kelas_siswas (Aturan poin 16).
 *
 * Constraint unik lama ('rks_user_ktp_unique' pada [user_id,
 * kelas_tahun_pelajaran_id]) hanya mengizinkan SATU baris riwayat
 * sepanjang masa untuk kombinasi siswa+KTP tertentu. Ini keliru secara
 * desain: RiwayatKelasSiswa adalah log historis, bukan status tunggal -
 * siswa yang keluar dari suatu KTP lalu di-assign kembali ke KTP yang
 * sama di kemudian hari (dikonfirmasi sebagai skenario valid) butuh
 * baris riwayat baru untuk pasangan yang sama.
 *
 * Constraint unik DIHAPUS TOTAL (dikonfirmasi: cukup mengandalkan logic
 * aplikasi) - KenaikanKelasService::tutupRiwayatAktif() tetap menjadi
 * satu-satunya penjaga agar tidak ada dua baris status='aktif' untuk
 * user yang sama secara bersamaan (Aturan poin 3, DRY - satu sumber
 * kebenaran di service, bukan di constraint DB).
 *
 * PENTING - urutan operasi: index baru dibuat TERLEBIH DAHULU sebelum
 * index lama di-drop. Index 'rks_user_ktp_unique' (dimulai dari kolom
 * user_id) dipakai MySQL sebagai index pendukung foreign key user_id
 * (foreignId('user_id')->constrained('users')) - men-drop-nya tanpa ada
 * index pengganti akan gagal dengan error 1553 "needed in a foreign key
 * constraint". Index baru (user_id, status) dimulai dari kolom yang
 * sama sehingga bisa langsung menggantikan peran tersebut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riwayat_kelas_siswas', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'rks_user_status_idx');
        });

        Schema::table('riwayat_kelas_siswas', function (Blueprint $table) {
            $table->dropUnique('rks_user_ktp_unique');
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_kelas_siswas', function (Blueprint $table) {
            $table->unique(['user_id', 'kelas_tahun_pelajaran_id'], 'rks_user_ktp_unique');
        });

        Schema::table('riwayat_kelas_siswas', function (Blueprint $table) {
            $table->dropIndex('rks_user_status_idx');
        });
    }
};
