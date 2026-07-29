<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bukus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('judul');
            $table->string('cover')->nullable();
            $table->string('penulis')->nullable();
            $table->string('penerbit')->nullable();
            $table->string('isbn')->nullable()->unique();
            $table->string('barcode')->unique();
            $table->foreignUuid('rak_id')->nullable()->constrained('raks');
            $table->decimal('harga_ganti', 10, 2)->default(0);
            $table->integer('stok')->default(1);
            $table->text('deskripsi')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bukus');
    }
};
