<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MENGUBAH SKEMA kelas (Aturan poin 16) - dikonfirmasi user:
 * 1. jurusan_id WAJIB diisi (NOT NULL) - "semua kelas memiliki jurusan".
 *    Diverifikasi 0 baris jurusan_id NULL (aktif maupun soft-deleted)
 *    sebelum migration ini ditulis - aman dijalankan tanpa migrasi data.
 * 2. FK jurusan_id diubah dari ON DELETE SET NULL -> ON DELETE RESTRICT
 *    (wajib, karena SET NULL kontradiktif dengan NOT NULL). Dikonfirmasi
 *    user. Dampak: Jurusan tidak bisa di-force-delete selama masih ada
 *    Kelas yang mereferensikannya - guard baru ditambahkan di
 *    JurusanResource::ForceDeleteAction (lihat file terpisah).
 * 3. Unique nama Kelas diubah dari GLOBAL -> PER JURUSAN (composite
 *    jurusan_id + nama).
 *
 * TODO: GAP-SPEC - composite unique (jurusan_id, nama) di bawah TIDAK
 * soft-delete-aware (bukan seperti 12 kolom tunggal di migration
 * 2026_08_03_000001). Sudah dicoba STORED dan VIRTUAL generated column -
 * KEDUANYA ditolak MariaDB dengan error 1901 ("cannot be used in the
 * GENERATED ALWAYS AS clause"), meski FK jurusan_id memakai RESTRICT
 * (bukan CASCADE/SET NULL seperti kasus kelas_tahun_pelajarans yang
 * pembatasannya sudah didokumentasikan resmi). MariaDB 11.8.6 di
 * environment ini ternyata menolak generated column berbasis kolom FK
 * APAPUN aksinya - lebih ketat dari dokumentasi resmi MySQL/MariaDB yang
 * hanya menyebut pembatasan untuk CASCADE/SET NULL. Root cause pastinya
 * belum ditelusuri lebih lanjut (kemungkinan versi/build spesifik) -
 * TIDAK dipaksakan lagi supaya tidak iterasi tebak-tebakan berulang.
 *
 * Konsekuensi: kombinasi (jurusan_id, nama) yang sudah di-soft-delete
 * tetap "menahan" slotnya - admin tidak bisa membuat Kelas baru dengan
 * kombinasi sama sampai Kelas lama di-restore atau di-force-delete.
 * Sama seperti kelas_tahun_pelajarans, risiko dinilai rendah untuk
 * konteks aplikasi ini (Aturan poin 16, dikonfirmasi pola sama dapat
 * diterima).
 */
return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        // 1. Lepas FK lama (nullOnDelete)
        Schema::table('kelas', function ($table) {
            $table->dropForeign(['jurusan_id']);
        });

        // 2. jurusan_id -> NOT NULL (MySQL/MariaDB only)
        if (! $isSqlite) {
            DB::statement('ALTER TABLE kelas MODIFY jurusan_id CHAR(36) NOT NULL');
        }

        // 3. FK baru dengan RESTRICT (default Laravel tanpa aksi eksplisit)
        Schema::table('kelas', function ($table) {
            $table->foreign('jurusan_id')->references('id')->on('jurusans');
        });

        // 4. Composite unique PER JURUSAN - STANDAR, bukan soft-delete-aware
        //    (lihat TODO: GAP-SPEC di atas class).
        Schema::table('kelas', function ($table) {
            $table->unique(['jurusan_id', 'nama'], 'kelas_jurusan_id_nama_unique');
        });
    }

    public function down(): void
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        Schema::table('kelas', function ($table) {
            $table->dropUnique('kelas_jurusan_id_nama_unique');
        });

        Schema::table('kelas', function ($table) {
            $table->dropForeign(['jurusan_id']);
        });

        if (! $isSqlite) {
            DB::statement('ALTER TABLE kelas MODIFY jurusan_id CHAR(36) NULL');
        }

        Schema::table('kelas', function ($table) {
            $table->foreign('jurusan_id')->references('id')->on('jurusans')->nullOnDelete();
        });
    }
};
