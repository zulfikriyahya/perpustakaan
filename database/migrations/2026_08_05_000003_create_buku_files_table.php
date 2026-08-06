<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buku_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('buku_id')->constrained('bukus')->cascadeOnDelete();
            $table->string('jenis'); // lihat App\Enums\JenisFileBuku
            $table->string('path'); // relatif terhadap disk 'public'
            $table->string('nama_file')->nullable(); // nama tampilan, mis. judul track audio
            $table->unsignedInteger('urutan')->default(0); // untuk multi-track audiobook
            $table->timestamps();
            $table->softDeletes();

            $table->index(['buku_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buku_files');
    }
};
