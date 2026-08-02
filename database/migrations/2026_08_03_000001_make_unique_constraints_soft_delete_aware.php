<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat SEMUA unique constraint yang masih "polos" jadi soft-delete
 * aware, mengikuti pola persis migration fix_unique_kunjungan_softdelete_aware
 * (generated column STORED di MariaDB, partial unique index di SQLite
 * testing). Dikonfirmasi user: SEMUA tabel di bawah termasuk cakupan
 * (Aturan poin 16 - additive, tidak menghapus/mengubah kolom asli, rollback
 * aman lewat down()).
 *
 * Setelah migration ini: keunikan dijaga oleh kolom bayangan *_aktif
 * (bernilai NULL saat baris di-soft-delete). Validasi form Filament WAJIB
 * diberi modifyRuleUsing(whereNull('deleted_at')) di Resource terkait -
 * lihat file Resource yang diubah bersamaan iterasi ini - supaya validasi
 * form tidak lebih ketat daripada constraint DB yang sebenarnya.
 *
 * PENGECUALIAN: kelas_tahun_pelajarans (lihat TODO: GAP-SPEC di
 * buatUniqueAktifKomposit) - TIDAK dibuat soft-delete-aware, tetap pakai
 * unique constraint standar dari create_kelas_tahun_pelajarans_table
 * (dikonfirmasi user, opsi B).
 */
return new class extends Migration
{
    protected array $kolomTunggal = [
        ['table' => 'users', 'column' => 'nisn'],
        ['table' => 'users', 'column' => 'nip'],
        ['table' => 'users', 'column' => 'no_telepon'],
        ['table' => 'users', 'column' => 'no_kartu_rfid'],
        ['table' => 'bukus', 'column' => 'isbn'],
        ['table' => 'eksemplars', 'column' => 'barcode'],
        ['table' => 'jurusans', 'column' => 'kode'],
        ['table' => 'level_badges', 'column' => 'nama_badge'],
        ['table' => 'punishments', 'column' => 'nama'],
        ['table' => 'rewards', 'column' => 'nama'],
        ['table' => 'tahun_pelajarans', 'column' => 'nama'],
        // 'kelas'/'nama' DIPINDAH ke migration 2026_08_03_000002 - sekarang
        // unique PER JURUSAN (composite jurusan_id+nama), bukan global lagi.
    ];

    /**
     * Nama index unique asli tidak selalu mengikuti konvensi
     * "{table}_{column}_unique". Kasus diketahui: users.nisn dulunya
     * bernama users.nis saat unique() pertama kali dibuat - MariaDB
     * TIDAK ikut me-rename index saat renameColumn() dijalankan
     * (lihat migration rename_nis_to_nisn_in_users_table), sehingga
     * index sebenarnya masih bernama users_nis_unique meski kolomnya
     * sudah bernama nisn.
     *
     * Diverifikasi via: SHOW INDEX FROM users WHERE Column_name = 'nisn'
     * -> Key_name = users_nis_unique.
     *
     * Key: "{table}.{column}", Value: nama index asli di DB.
     */
    protected array $overrideIndexLama = [
        'users.nisn' => 'users_nis_unique',
    ];

    public function up(): void
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        foreach ($this->kolomTunggal as $kolom) {
            $this->buatUniqueAktif($kolom['table'], $kolom['column'], $isSqlite);
        }

        // kelas_tahun_pelajarans SENGAJA tidak diproses di sini - lihat
        // TODO: GAP-SPEC di buatUniqueAktifKomposit (dead code dihapus,
        // dikonfirmasi user opsi B).
    }

    protected function indexLamaUntuk(string $table, string $column): string
    {
        return $this->overrideIndexLama["{$table}.{$column}"] ?? "{$table}_{$column}_unique";
    }

    protected function buatUniqueAktif(string $table, string $column, bool $isSqlite): void
    {
        $kolomAktif = "{$column}_aktif";
        $indexAktif = "{$table}_{$kolomAktif}_unique";
        $indexLama = $this->indexLamaUntuk($table, $column);

        if ($isSqlite) {
            DB::statement("
                CREATE UNIQUE INDEX {$indexAktif}
                ON {$table} ({$column})
                WHERE deleted_at IS NULL
            ");

            return;
        }

        Schema::table($table, function ($t) use ($indexLama) {
            $t->dropUnique($indexLama);
        });

        DB::statement("
            ALTER TABLE {$table}
            ADD COLUMN {$kolomAktif} VARCHAR(255)
                GENERATED ALWAYS AS (
                    CASE WHEN deleted_at IS NULL THEN {$column} ELSE NULL END
                ) STORED
        ");

        DB::statement("ALTER TABLE {$table} ADD UNIQUE INDEX {$indexAktif} ({$kolomAktif})");
    }

    /**
     * TODO: GAP-SPEC - kelas_tahun_pelajarans TIDAK dibuat soft-delete-aware.
     *
     * kelas_id dan tahun_pelajaran_id adalah kolom FK dengan ON DELETE
     * CASCADE (sengaja - lihat create_kelas_tahun_pelajarans_table).
     * MariaDB menolak meng-index generated column (VIRTUAL maupun STORED)
     * yang bergantung pada kolom FK ber-CASCADE (error 1901 saat ADD
     * INDEX) - root cause: MariaDB tidak menjamin generated column ikut
     * konsisten saat FK cascade action berjalan (lihat MDEV-18114), jadi
     * MariaDB defensif menolak kombinasi ini sepenuhnya di level DDL.
     *
     * Dikonfirmasi user (opsi B): tetap pakai unique constraint standar
     * dari create_kelas_tahun_pelajarans_table
     * (kelas_tahun_pelajarans_kelas_id_tahun_pelajaran_id_unique), TIDAK
     * soft-delete-aware. FK ON DELETE CASCADE dipertahankan apa adanya -
     * tidak diubah ke RESTRICT (Aturan poin 17, akan berdampak ke
     * perilaku hapus Kelas/TahunPelajaran yang sudah berjalan).
     *
     * Konsekuensi: kombinasi (kelas_id, tahun_pelajaran_id) yang sudah
     * di-soft-delete tetap "menahan" slotnya - tidak bisa dibuat baris
     * baru dengan kombinasi sama sampai baris lama di-restore atau
     * di-force-delete. Risiko dinilai rendah karena soft-delete pada
     * Kelas/TahunPelajaran sendiri TIDAK memicu FK action apa pun (FK
     * hanya bereaksi ke hard delete/forceDelete). Keunikan "kombinasi
     * aktif" untuk kasus restore harus divalidasi di level aplikasi
     * (mis. KelasTahunPelajaranResource form rule / Service terkait)
     * jika suatu saat dibutuhkan, bukan di DB.
     */
    protected function buatUniqueAktifKomposit(bool $isSqlite): void
    {
        // Sengaja kosong - lihat TODO: GAP-SPEC di atas method ini.
    }

    public function down(): void
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        foreach (array_reverse($this->kolomTunggal) as $kolom) {
            $this->rollbackUniqueAktif($kolom['table'], $kolom['column'], $isSqlite);
        }
    }

    protected function rollbackUniqueAktif(string $table, string $column, bool $isSqlite): void
    {
        $kolomAktif = "{$column}_aktif";
        $indexAktif = "{$table}_{$kolomAktif}_unique";
        $indexLama = $this->indexLamaUntuk($table, $column);

        if ($isSqlite) {
            DB::statement("DROP INDEX {$indexAktif}");

            return;
        }

        DB::statement("ALTER TABLE {$table} DROP INDEX {$indexAktif}");
        DB::statement("ALTER TABLE {$table} DROP COLUMN {$kolomAktif}");

        Schema::table($table, function ($t) use ($column, $indexLama) {
            $t->unique($column, $indexLama);
        });
    }
};
