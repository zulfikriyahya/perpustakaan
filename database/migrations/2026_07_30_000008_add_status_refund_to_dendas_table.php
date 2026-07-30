<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menutup gap refund (lihat PeminjamanService::batalkanDenda). Saat Denda
 * yang SUDAH TERBAYAR dibatalkan (koreksi kondisi Pengembalian), sistem
 * tidak bisa mengembalikan uang secara otomatis - kolom ini hanya menandai
 * status refund manual di luar sistem, supaya Admin/Pustakawan punya
 * catatan tugas yang jelas, bukan hilang begitu saja di kolom 'keterangan'.
 *
 * PERUBAHAN SKEMA EKSPLISIT (Aturan poin 16): kolom baru, nullable,
 * default 'tidak_perlu' - tidak mengubah/menghapus kolom existing, aman
 * untuk data produksi yang sudah ada (semua baris lama otomatis
 * 'tidak_perlu', tidak salah secara historis karena Denda lama tidak
 * pernah dibatalkan oleh mekanisme ini).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dendas', function (Blueprint $table) {
            $table->enum('status_refund', ['tidak_perlu', 'perlu_refund', 'sudah_direfund'])
                ->default('tidak_perlu')
                ->after('status_lunas');
        });
    }

    public function down(): void
    {
        Schema::table('dendas', function (Blueprint $table) {
            $table->dropColumn('status_refund');
        });
    }
};
