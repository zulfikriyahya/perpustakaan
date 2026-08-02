<?php

use App\Http\Controllers\ChartExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('dashboard');
});

Route::post('/dashboard/chart-export/pdf', [ChartExportController::class, 'pdf'])
    ->middleware(['web', 'auth'])
    ->name('chart-export.pdf');
