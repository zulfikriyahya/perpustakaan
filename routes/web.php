<?php

use App\Http\Controllers\BulkDataJobDownloadController;
use App\Http\Controllers\ChartExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('dashboard/transaksi-cepat');
});

Route::post('/dashboard/chart-export/pdf', [ChartExportController::class, 'pdf'])
    ->middleware(['web', 'auth'])
    ->name('chart-export.pdf');

Route::get('/unduh-bulk-data/{bulkDataJob}', BulkDataJobDownloadController::class)
    ->middleware(['auth'])
    ->name('bulk-data-job.download');
