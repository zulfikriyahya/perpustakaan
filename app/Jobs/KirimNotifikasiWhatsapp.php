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
            Log::error("KirimNotifikasiWhatsapp: gagal mengirim template '{$this->templateCode}' ke {$this->nomorTujuan}: {$e->getMessage()}");

            // TODO: GAP-SPEC - dilempar ulang supaya queue worker melakukan
            // retry sesuai $tries/$backoff. Jika error bersifat permanen
            // (mis. template_code tidak terhubung ke API key, lihat kontrak
            // API §2.2 kode 403), retry tidak akan membantu dan job akan
            // berakhir di failed_jobs setelah 3 percobaan - belum ada
            // pembedaan error permanen vs transient di level job ini.
            throw $e;
        }
    }

    /**
     * Dipanggil otomatis oleh queue setelah seluruh percobaan ($tries) habis.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("KirimNotifikasiWhatsapp: job gagal permanen setelah {$this->tries} percobaan. Template '{$this->templateCode}' ke {$this->nomorTujuan}: {$exception->getMessage()}");
    }
}
