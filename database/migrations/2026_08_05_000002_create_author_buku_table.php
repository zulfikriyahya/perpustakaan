<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author_buku', function (Blueprint $table) {
            $table->foreignUuid('author_id')->constrained('authors')->cascadeOnDelete();
            $table->foreignUuid('buku_id')->constrained('bukus')->cascadeOnDelete();
            $table->primary(['author_id', 'buku_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_buku');
    }
};
