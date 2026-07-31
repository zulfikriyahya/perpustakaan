<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eksemplars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('buku_id')->constrained('bukus');
            $table->string('barcode')->unique();
            $table->foreignUuid('rak_id')->nullable()->constrained('raks');
            $table->enum('status', ['tersedia', 'dipinjam', 'rusak', 'hilang'])->default('tersedia');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eksemplars');
    }
};
