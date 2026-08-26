<?php

use App\Http\Controllers\Api\PerpustakaanDeviceController;
use Illuminate\Support\Facades\Route;

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
