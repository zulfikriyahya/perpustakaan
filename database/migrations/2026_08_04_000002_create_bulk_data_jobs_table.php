<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_data_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('tipe', ['import', 'export']);
            $table->enum('status', ['pending', 'diproses', 'selesai', 'gagal'])->default('pending');
            $table->string('nama_file_asli')->nullable();
            $table->string('file_path')->nullable(); // input (import) atau output (export)
            $table->json('laporan')->nullable(); // per-sheet: total/sukses/gagal/errors[]
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_data_jobs');
    }
};
