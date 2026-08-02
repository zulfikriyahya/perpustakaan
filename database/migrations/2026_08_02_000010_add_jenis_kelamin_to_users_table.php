<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom baru murni, nullable, default null - AMAN untuk data users
 * existing (poin 16 Aturan). Data lama tetap null sampai diisi manual
 * lewat form UserResource (tidak ada backfill otomatis - tidak ada
 * sumber data untuk menebak jenis kelamin user lama).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable()->after('nama');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('jenis_kelamin');
        });
    }
};
