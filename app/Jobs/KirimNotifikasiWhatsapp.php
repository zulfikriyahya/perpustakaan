<?php

namespace App\Jobs;

use App\Exceptions\WhatsappGatewayException;
use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job pengirim notifikasi WhatsApp - dijalankan di queue 'whatsapp' terpisah
 * agar pengiriman WA tidak blocking proses utama (Peminjaman/Denda/Point).
 * Lihat Logic Module §11 checklist dan Aturan poin 3 (Prinsip DRY).
 *
 * Job ini menerima template_code yang SUDAH di-resolve dari Setting (lihat
 * WhatsappService::kirimEvent()) - lookup Setting tetap dilakukan sinkron
 * di pemanggil supaya job tidak perlu query Setting berulang dan supaya
 * kegagalan "template belum dikonfigurasi" tetap terdeteksi segera (bukan
 * baru diketahui setelah job diproses worker).
 *
 * Idempotency: reference_id yang dikirim oleh WhatsappService::kirimEvent()
 * bersifat stabil per event (bukan UUID acak untuk event terjadwal seperti
 * reminder H-3/H-1/denda), sehingga retry job ini maupun eksekusi cron
 * ganda di hari yang sama aman - gateway mendeteksi reference_id yang
 * sama dan mengembalikan 200 (bukan mengirim ulang WA), sesuai kontrak API
 * §2.2 & §9 (idempotency window 24 jam). Retry di sini hanya menghitung
 * ulang signature/timestamp, TIDAK pernah mengirim signature lama.
 */
class KirimNotifikasiWhatsapp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Konsisten dengan --tries=3 pada supervisor worker queue 'whatsapp'
     * (lihat conf.d/*.conf, program *-whatsapp).
     */
    public int $tries = 3;

    /**
     * Konsisten dengan --timeout=30 pada supervisor worker queue 'whatsapp'.
     * Job tidak boleh berjalan lebih lama dari timeout worker.
     */
    public int $timeout = 25;

    /**
     * Backoff singkat karena kegagalan WA umumnya transient (rate limit,
     * sesi belum ready) - lihat dok kontrak API §9 Guard Rail.
     */
    public array $backoff = [5, 15, 30];

    /**
     * Status code gateway yang bersifat PERMANEN (retry tidak akan mengubah
     * hasil, sesuai kontrak API §2.2):
     * - 400: body/media/variabel tidak valid - kesalahan payload yang kita
     *   kirim sendiri, tidak berubah walau di-retry.
     * - 403: template_code tidak ditemukan/tidak terkait ke API key -
     *   kesalahan konfigurasi Admin di panel gateway, bukan transient.
     * - 409: reference_id sudah dipakai dengan payload BERBEDA - retry
     *   dengan payload sama akan 409 lagi terus (lihat kontrak API §2.2).
     *
     * Di luar daftar ini (401 HMAC, 429 guard rail, 500 internal) dianggap
     * transient dan tetap mengikuti siklus retry/backoff normal.
     */
    private const STATUS_PERMANEN = [400, 403, 409];

    public function __construct(
        protected string $templateCode,
        protected string $nomorTujuan,
        protected array $variables,
        protected ?string $referenceId,
    ) {}

    public function handle(WhatsappService $whatsappService): void
    {
        try {
            $whatsappService->kirimPesan(
                templateCode: $this->templateCode,
                recipient: $this->nomorTujuan,
                variables: $this->variables,
                referenceId: $this->referenceId,
            );
        } catch (WhatsappGatewayException $e) {
            if (in_array($e->statusCode, self::STATUS_PERMANEN, true)) {
                Log::error("KirimNotifikasiWhatsapp: kegagalan permanen (status {$e->statusCode}), tidak di-retry. Template '{$this->templateCode}' ke {$this->nomorTujuan}: {$e->getMessage()}");

                // fail() langsung memindahkan job ke failed_jobs tanpa
                // menghabiskan sisa percobaan $tries - retry dipastikan
                // sia-sia untuk status di STATUS_PERMANEN.
                $this->fail($e);

                return;
            }

            Log::error("KirimNotifikasiWhatsapp: gagal mengirim template '{$this->templateCode}' ke {$this->nomorTujuan}: {$e->getMessage()}");

            // Transient (401/429/500 dsb.) - lempar ulang supaya queue
            // worker retry sesuai $tries/$backoff.
            throw $e;
        }
    }

    /**
     * Dipanggil otomatis oleh queue setelah seluruh percobaan ($tries) habis
     * ATAU setelah $this->fail() dipanggil eksplisit di handle().
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("KirimNotifikasiWhatsapp: job gagal permanen. Template '{$this->templateCode}' ke {$this->nomorTujuan}: {$exception->getMessage()}");
    }
}
