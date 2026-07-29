<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Cron harian Peminjaman (Logic Module §8): reminder H-3/H-1 dan transisi
 * ke Terlambat. Dijadwalkan jam 06:00 - SEBELUM jam operasional device RFID
 * default (Setting device_sleep_end_hour, default 05:00) supaya notifikasi
 * WA dan perubahan status sudah selesai saat perpustakaan mulai beroperasi.
 *
 * TODO: GAP-SPEC - jam 06:00 dipilih sebagai baseline aman (asumsi logis,
 * belum ada Setting khusus untuk jam eksekusi cron ini). Jika Admin butuh
 * jam berbeda, sebaiknya dibuat Setting terpisah (mis. 'cron_harian_jam')
 * daripada hardcode - belum diimplementasikan pada iterasi ini.
 *
 * withoutOverlapping(): mencegah eksekusi ganda jika scheduler:run tumpang
 * tindih (mis. proses sebelumnya masih jalan karena data besar).
 * onOneServer(): aman jika deployment multi-server di masa depan.
 */
Schedule::command('perpustakaan:cron-harian')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->onOneServer();
