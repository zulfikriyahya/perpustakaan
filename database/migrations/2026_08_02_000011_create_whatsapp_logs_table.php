<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel baru murni - tidak mengubah tabel/job existing secara struktural
 * (poin 16/17 Aturan). Satu baris per reference_id (upsert di job, bukan
 * insert per percobaan) supaya retry job.tries=3 tidak menumpuk baris -
 * kolom percobaan_ke merekam berapa kali handle() dijalankan untuk
 * reference_id yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('reference_id')->nullable()->unique();
            $table->string('template_code');
            $table->string('nomor_tujuan');
            $table->json('variables')->nullable();
            $table->enum('status', ['terkirim', 'gagal_transient', 'gagal_permanen'])->default('gagal_transient');
            $table->text('keterangan')->nullable();
            $table->unsignedTinyInteger('percobaan_ke')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};
