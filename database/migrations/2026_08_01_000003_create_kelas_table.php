<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama'); // mis. "X IPA 1"
            $table->unsignedTinyInteger('tingkat'); // 10, 11, 12 - dipakai urutan kenaikan
            $table->uuid('jurusan_id')->nullable();
            $table->foreign('jurusan_id')->references('id')->on('jurusans')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
