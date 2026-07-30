<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel baru murni (tidak mengubah tabel existing) untuk OTP reset password
 * via WhatsApp - user tidak punya kolom email, jadi Password Broker Laravel
 * (email + password_reset_tokens) tidak dipakai. Aman di-rollback kapan pun,
 * tidak berdampak ke data peminjaman/denda/point (poin 16 Aturan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_otps', function (Blueprint $table) {
            $table->id();
            $table->string('no_telepon')->index();
            $table->string('otp'); // disimpan hashed (Hash::make), bukan plain
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_otps');
    }
};
