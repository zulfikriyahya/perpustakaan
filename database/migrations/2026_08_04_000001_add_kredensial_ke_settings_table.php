<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah dukungan penyimpanan Setting terenkripsi (kredensial WhatsApp
 * Gateway & Device Gateway RFID yang sebelumnya hanya di .env).
 *
 * - Kolom `is_encrypted` menandai baris yang value-nya dienkripsi via
 *   Crypt::encryptString() (kunci = APP_KEY) - dibaca ulang oleh
 *   Setting::get() secara transparan.
 * - Kolom `group` (enum) ditambah value 'kredensial' - MySQL/MariaDB enum
 *   wajib di-MODIFY via raw SQL, bukan Schema Builder biasa.
 *
 * FIX: `group` adalah reserved keyword di MariaDB - WAJIB dibungkus
 * backtick (`group`), tanpa itu MariaDB gagal parse ALTER TABLE (error
 * 1064 - lihat percobaan migrate sebelumnya).
 *
 * TODO: GAP-SPEC - rollback down() mengembalikan enum TANPA 'kredensial'.
 * Jika sudah ada baris group='kredensial' saat rollback dijalankan, MySQL
 * akan mengosongkan value tersebut (data loss pada kolom group, BUKAN
 * pada value/secret-nya) - pastikan tidak rollback migration ini di
 * production tanpa backup tabel settings terlebih dahulu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function ($table) {
            $table->boolean('is_encrypted')->default(false)->after('value');
        });

        DB::statement("ALTER TABLE settings MODIFY `group` ENUM('peminjaman', 'point', 'notifikasi', 'denda', 'device', 'whatsapp', 'kredensial') DEFAULT 'peminjaman'");
    }

    public function down(): void
    {
        DB::statement("UPDATE settings SET `group` = 'whatsapp' WHERE `group` = 'kredensial'");
        DB::statement("ALTER TABLE settings MODIFY `group` ENUM('peminjaman', 'point', 'notifikasi', 'denda', 'device', 'whatsapp') DEFAULT 'peminjaman'");

        Schema::table('settings', function ($table) {
            $table->dropColumn('is_encrypted');
        });
    }
};
