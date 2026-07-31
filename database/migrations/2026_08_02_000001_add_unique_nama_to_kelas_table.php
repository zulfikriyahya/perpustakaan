<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MENGUBAH SKEMA kelas - dampak ke data existing (Aturan poin 16).
 * 'nama' dibuat unik secara global (dikonfirmasi) - sebelumnya dua Kelas
 * beda Jurusan boleh punya nama sama (mis. "X-1" di IPA dan "X-1" di
 * IPS). VERIFIKASI SEBELUM MIGRATE: dicek via tinker pada tanggal
 * pembuatan migration ini, hasil 0 baris nama Kelas duplikat - AMAN
 * dijalankan saat itu. Jika ada Kelas baru ditambahkan antara saat
 * verifikasi dan saat migration ini benar-benar dijalankan di
 * production, migration akan GAGAL (bukan menghapus/mengubah data
 * diam-diam) - cek ulang duplikat sebelum migrate jika jeda waktunya
 * lama.
 *
 * TODO: GAP-SPEC - unique index standar TIDAK soft-delete aware (mirip
 * kasus yang sudah ditangani untuk Kunjungan di migration
 * 2026_07_30_000002_fix_unique_kunjungan_softdelete_aware.php). Artinya
 * Kelas yang sudah di-soft-delete tetap "menahan" nama-nya - admin
 * tidak akan bisa membuat Kelas baru dengan nama yang sama sampai
 * Kelas lama di-restore atau di-force-delete. BELUM dikonfirmasi apakah
 * perilaku ini bisa diterima atau perlu unique index partial/composite
 * dengan deleted_at seperti pola Kunjungan - dibiarkan standar dulu
 * sampai ada keputusan eksplisit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->unique('nama', 'kelas_nama_unique');
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropUnique('kelas_nama_unique');
        });
    }
};
