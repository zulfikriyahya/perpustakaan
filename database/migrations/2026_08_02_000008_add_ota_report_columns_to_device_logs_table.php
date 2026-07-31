<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kontrak BARU (belum ada di firmware lama) - endpoint report status OTA
 * akan ditambahkan ke firmware bersamaan dengan perubahan ini (dikonfirmasi
 * user: kode firmware akan disesuaikan). Field mengikuti keputusan yang
 * dikonfirmasi: simpan di DeviceLog existing, bukan tabel baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_logs', function (Blueprint $table) {
            $table->string('ota_status')->nullable()->after('firmware_version');
            $table->text('ota_error')->nullable()->after('ota_status');
            $table->timestamp('ota_reported_at')->nullable()->after('ota_error');
        });
    }

    public function down(): void
    {
        Schema::table('device_logs', function (Blueprint $table) {
            $table->dropColumn(['ota_status', 'ota_error', 'ota_reported_at']);
        });
    }
};
