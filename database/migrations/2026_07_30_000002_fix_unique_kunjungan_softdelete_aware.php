<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MariaDB mewajibkan index yang meng-cover kolom FK (user_id) selalu ada.
        // Index unique lama adalah satu-satunya index yang mencakup user_id, jadi
        // tambahkan index biasa dulu untuk user_id sebelum index lama di-drop,
        // supaya FK constraint tetap punya index pendukung.
        Schema::table('kunjungans', function ($table) {
            $table->index('user_id', 'kunjungans_user_id_index');
        });

        Schema::table('kunjungans', function ($table) {
            $table->dropUnique('kunjungans_user_tanggal_unique');
        });

        // Generated column: bernilai 'user_id-tanggal' HANYA jika baris aktif
        // (deleted_at IS NULL), NULL jika sudah di-soft-delete. MariaDB
        // memperbolehkan banyak NULL pada unique index, sehingga baris yang
        // sudah di-soft-delete tidak lagi memblokir insert baru dengan
        // kombinasi user_id+tanggal yang sama.
        // Verified: MariaDB 11.8.6 mendukung generated column STORED + unique index.
        DB::statement("
            ALTER TABLE kunjungans
            ADD COLUMN unik_aktif VARCHAR(300)
                GENERATED ALWAYS AS (
                    CASE WHEN deleted_at IS NULL
                        THEN CONCAT(user_id, '-', tanggal)
                        ELSE NULL
                    END
                ) STORED
        ");

        DB::statement('
            ALTER TABLE kunjungans
            ADD UNIQUE INDEX kunjungans_unik_aktif_unique (unik_aktif)
        ');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE kunjungans DROP INDEX kunjungans_unik_aktif_unique');
        DB::statement('ALTER TABLE kunjungans DROP COLUMN unik_aktif');

        Schema::table('kunjungans', function ($table) {
            $table->unique(['user_id', 'tanggal'], 'kunjungans_user_tanggal_unique');
        });

        Schema::table('kunjungans', function ($table) {
            $table->dropIndex('kunjungans_user_id_index');
        });
    }
};
