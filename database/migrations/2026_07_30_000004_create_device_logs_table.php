<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // device_id dari firmware: MAC-based (ESP32_XXXX) atau nama custom jika diisi saat provisioning.
            $table->string('device_id')->unique();
            $table->string('device_name')->nullable();
            $table->string('firmware_version')->nullable();
            $table->unsignedBigInteger('uptime_sec')->default(0);
            $table->unsignedBigInteger('heap_free')->default(0);
            $table->unsignedInteger('pending_records')->default(0);
            $table->unsignedInteger('scan_today')->default(0);
            $table->integer('rssi')->default(0);
            $table->boolean('sd_ok')->default(false);
            $table->unsignedInteger('rfid_db_entries')->default(0);
            $table->boolean('online')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_logs');
    }
};
