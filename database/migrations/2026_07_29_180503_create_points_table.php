<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('event_type', ['kunjungan', 'peminjaman', 'pengembalian', 'kerusakan', 'kehilangan']);
            $table->integer('nilai');
            // ref_type/ref_id: polymorphic manual, BUKAN Eloquent morph — lihat PointService
            $table->string('ref_type')->nullable();
            $table->uuid('ref_id')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points');
    }
};
