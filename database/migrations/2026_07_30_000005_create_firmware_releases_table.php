<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firmware_releases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('version')->unique(); // format semver: x.y.z, dibandingkan dengan compareFirmwareVersion() di firmware
            $table->string('url'); // URL binary .bin, wajib https, wajib bisa diverifikasi lewat X-API-KEY yang sama
            $table->string('md5')->nullable();
            $table->boolean('aktif')->default(true);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firmware_releases');
    }
};
