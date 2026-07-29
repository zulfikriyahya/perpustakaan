<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_rak', function (Blueprint $table) {
            $table->foreignUuid('kategori_id')->constrained('kategoris')->cascadeOnDelete();
            $table->foreignUuid('rak_id')->constrained('raks')->cascadeOnDelete();
            $table->primary(['kategori_id', 'rak_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_rak');
    }
};
