<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('avatar')->nullable();
            $table->string('nama');
            $table->enum('role', ['siswa', 'pegawai', 'pustakawan', 'admin'])->default('siswa');
            $table->string('nis')->nullable()->unique();
            $table->string('nip')->nullable()->unique();
            $table->string('kelas')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('no_telepon')->unique();
            $table->string('no_kartu_rfid')->nullable()->unique();
            // Nullable: user yang hanya pernah login via OTP WhatsApp tidak wajib punya password.
            $table->string('password')->nullable();
            $table->boolean('status_suspend')->default(false);
            $table->integer('akumulasi_point')->default(0);
            // FK ke level_badges ditambahkan di migration terpisah (lihat add_level_badge_fk_to_users_table)
            // karena level_badges dibuat belakangan dalam urutan file.
            $table->uuid('level_badge_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
