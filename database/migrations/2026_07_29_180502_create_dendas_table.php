<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dendas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('peminjaman_id')->constrained('peminjamans');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('tipe', ['keterlambatan', 'kerusakan', 'kehilangan']);
            $table->decimal('nominal', 10, 2);
            $table->boolean('status_lunas')->default(false);
            $table->dateTime('tanggal_lunas')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dendas');
    }
};
