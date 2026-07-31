<?php

use App\Http\Controllers\Api\PerpustakaanDeviceController;
use Illuminate\Support\Facades\Route;

/*
 * Endpoint Attendance Machine (ESP32-C3) - path WAJIB persis sama dengan
 * apiBaseUrl + path yang dipanggil firmware (lihat firmware v2.3.1):
 *   GET  /api/perpustakaan/ping
 *   GET  /api/perpustakaan/rfid-list/version
 *   GET  /api/perpustakaan/rfid-list
 *   POST /api/perpustakaan/sync-bulk
 *   POST /api/perpustakaan            (kirimLangsung - real-time, SD tidak tersedia)
 *   POST /api/perpustakaan/heartbeat
 *   GET  /api/perpustakaan/config
 *   POST /api/perpustakaan/firmware/check
 *   POST /api/perpustakaan/firmware/report  (BARU - kontrak OTA report, firmware akan disesuaikan)
 *
 * Semua endpoint di bawah prefix ini wajib header X-API-KEY (lihat
 * AuthenticateDeviceApiKey) - firmware mengirim header ini di SETIAP request
 * termasuk GET. Perubahan path/method di sini WAJIB dicek ulang terhadap
 * firmware yang sudah terpasang di lapangan (Aturan poin 17).
 */

Route::prefix('perpustakaan')
    ->middleware('device.api.key')
    ->group(function () {
        Route::get('/ping', [PerpustakaanDeviceController::class, 'ping']);
        Route::get('/rfid-list/version', [PerpustakaanDeviceController::class, 'rfidListVersion']);
        Route::get('/rfid-list', [PerpustakaanDeviceController::class, 'rfidList']);
        Route::post('/sync-bulk', [PerpustakaanDeviceController::class, 'syncBulk']);
        Route::post('/', [PerpustakaanDeviceController::class, 'kirimLangsung']);
        Route::post('/heartbeat', [PerpustakaanDeviceController::class, 'heartbeat']);
        Route::get('/config', [PerpustakaanDeviceController::class, 'config']);
        Route::post('/firmware/check', [PerpustakaanDeviceController::class, 'firmwareCheck']);
        Route::post('/firmware/report', [PerpustakaanDeviceController::class, 'firmwareReport']);
    });
