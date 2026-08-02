<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel baru murni untuk OTP LOGIN (beda dari password_reset_otps yang
 * dipakai alur reset password) - dipisah supaya semantik jelas: verifikasi
 * di sini TIDAK mengubah password, hanya men-trigger Auth::login().
 * Aman di-rollback, tidak berdampak ke data users/peminjaman/denda/point.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_otps', function (Blueprint $table) {
            $table->id();
            $table->string('no_telepon')->index();
            $table->string('otp'); // disimpan hashed (Hash::make), bukan plain
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_otps');
    }
};
