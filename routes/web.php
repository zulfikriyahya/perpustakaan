<?php

use App\Http\Controllers\AuthorPublikController;
use App\Http\Controllers\BukuKatalogController;
use App\Http\Controllers\BukuPublikController;
use App\Http\Controllers\BulkDataJobDownloadController;
use App\Http\Controllers\ChartExportController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\SertifikatPublikController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Sesuai keputusan: user sudah login -> langsung transaksi-cepat,
    // guest -> landing page publik.
    if (auth()->check()) {
        return redirect('dashboard/transaksi-cepat');
    }

    return app(LandingPageController::class)->index();
})->name('home');

Route::get('/faq', [LandingPageController::class, 'faq'])->name('faq');
Route::get('/tentang', [LandingPageController::class, 'tentang'])->name('tentang');

Route::get('/authors', [AuthorPublikController::class, 'index'])->name('authors.index');
Route::get('/authors/{author}', [AuthorPublikController::class, 'show'])->name('authors.show');

// Katalog buku fisik, terpisah dari buku digital (route di bawah).
Route::get('/buku', [BukuKatalogController::class, 'index'])->name('katalog.index');
Route::get('/buku/{buku}', [BukuKatalogController::class, 'show'])->name('katalog.show');

Route::get('/buku-digital', [BukuPublikController::class, 'index'])->name('buku.index');
Route::get('/buku-digital/baca/{file}', [BukuPublikController::class, 'baca'])->name('buku.baca');

// BARU - akses publik sertifikat Reward/Badge, dikirim via link WhatsApp.
Route::get('/sertifikat/reward/{rewardLog}', [SertifikatPublikController::class, 'reward'])->name('sertifikat.reward');
Route::get('/sertifikat/badge/{levelBadgeLog}', [SertifikatPublikController::class, 'badge'])->name('sertifikat.badge');

Route::post('/dashboard/chart-export/pdf', [ChartExportController::class, 'pdf'])
    ->middleware(['web', 'auth'])
    ->name('chart-export.pdf');

Route::get('/unduh-bulk-data/{bulkDataJob}', BulkDataJobDownloadController::class)
    ->middleware(['auth'])
    ->name('bulk-data-job.download');
Route::get('/robots.txt', function () {
    $lines = [
        'User-agent: *',
        'Allow: /',
        'Disallow: /dashboard',
        'Disallow: /unduh-bulk-data',
        '',
        'Sitemap: '.route('sitemap'),
    ];

    return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain']);
})->name('robots');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
