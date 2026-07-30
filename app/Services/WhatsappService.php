<?php

namespace App\Services;

use App\Enums\GroupSetting;
use App\Exceptions\WhatsappGatewayException;
use App\Jobs\KirimNotifikasiWhatsapp;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Wrapper untuk WhatsApp Gateway (whatsapp.zedlabs.id API v1, autentikasi HMAC-SHA256).
 * Signature dihitung dari raw body bytes persis seperti yang dikirim - lihat
 * dokumen kontrak API bagian 2.1. Jangan format ulang body setelah signing.
 */
class WhatsappService
{
    protected string $baseUrl;

    protected string $apiKeyId;

    protected string $secret;

    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.whatsapp_gateway.base_url'), '/');
        $this->apiKeyId = (string) config('services.whatsapp_gateway.api_key_id');
        $this->secret = (string) config('services.whatsapp_gateway.secret');
        $this->timeout = (int) config('services.whatsapp_gateway.timeout', 15);
    }

    /**
     * Kirim pesan berbasis template terdaftar di panel gateway.
     * Dipanggil SINKRON oleh KirimNotifikasiWhatsapp job (bukan langsung
     * oleh Controller/Observer/Service lain) - lihat kirimEvent() di bawah.
     *
     * @param  array<string, mixed>  $variables
     * @param  array<string, mixed>|null  $media  Lihat dokumen kontrak API bagian 2.2 (jenis: dokumen|gambar|video|link|kontak)
     * @return array{job_id: string, status: string}
     *
     * @throws WhatsappGatewayException
     */
    public function kirimPesan(
        string $templateCode,
        string $recipient,
        array $variables = [],
        ?array $media = null,
        ?string $referenceId = null,
    ): array {
        $body = [
            'template_code' => $templateCode,
            'recipient' => $recipient,
            'variables' => $variables,
            'media' => $media,
        ];

        if ($referenceId !== null) {
            $body['reference_id'] = $referenceId;
        }

        // json_encode default PHP tanpa spasi tambahan - konsisten dengan body yang
        // ditandatangani. JSON_UNESCAPED_SLASHES/UNICODE agar tidak ada karakter
        // escape tak perlu yang mengubah representasi byte.
        $bodyString = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        [$status, $payload] = $this->kirimRequest('POST', '/api/v1/messages', $bodyString);

        if (! in_array($status, [200, 202], true)) {
            throw new WhatsappGatewayException($status, $payload['error'] ?? 'unknown error');
        }

        return [
            'job_id' => $payload['job_id'] ?? '',
            'status' => $payload['status'] ?? '',
        ];
    }

    /**
     * Ambil status terkini satu job (queued|processing|sent|delivered|read|failed).
     *
     * @return array{job_id: string, status: string, waktu_antre: string, waktu_kirim: string, keterangan_gagal: string}
     *
     * @throws WhatsappGatewayException
     */
    public function ambilStatus(string $jobId): array
    {
        [$status, $payload] = $this->kirimRequest('GET', "/api/v1/messages/{$jobId}", '');

        if ($status !== 200) {
            throw new WhatsappGatewayException($status, $payload['error'] ?? 'unknown error');
        }

        return $payload;
    }

    /**
     * Titik masuk TUNGGAL untuk seluruh notifikasi WA di aplikasi (Aturan
     * poin 3 - Prinsip DRY). Method ini hanya me-resolve template_code dari
     * Setting lalu men-dispatch KirimNotifikasiWhatsapp ke queue 'whatsapp'.
     *
     * ->afterCommit(): pemanggil (PeminjamanService::tandaiDenda,
     * PointService::catatEvent, dsb.) sering berada di dalam DB::transaction.
     * Tanpa afterCommit(), worker queue 'redis' bisa memproses job sebelum
     * transaksi commit (config/queue.php redis tidak set after_commit=true
     * secara global) - kalau transaksi rollback, notifikasi WA sudah
     * terlanjur terkirim untuk data yang batal tersimpan. Jika dipanggil di
     * luar transaksi (tidak ada transaksi aktif), afterCommit() tidak
     * memberi efek tambahan - job tetap dispatch langsung.
     *
     * Key pola: wa_template_{event_code}, mis. 'wa_template_peminjaman_aktif'.
     *
     * TODO: ASUMSI - nama key Setting per event belum ditentukan spec, memakai pola
     * di atas. Admin wajib mengisi Setting ini + membuat/mengaitkan template_code
     * yang sesuai di panel gateway (dok bagian 4.2) sebelum notifikasi terkirim.
     *
     * Jika template belum dikonfigurasi (Setting kosong), pengiriman di-skip dan
     * dicatat sebagai warning - TIDAK di-dispatch ke queue sama sekali, supaya
     * tidak menumpuk job yang pasti gagal karena template_code kosong.
     */
    public function kirimEvent(
        string $eventCode,
        string $nomorTujuan,
        array $variables = [],
        ?string $referenceId = null,
    ): void {
        $templateCode = Setting::get("wa_template_{$eventCode}");
        if (! $templateCode) {
            Log::warning("WhatsappService: template untuk event '{$eventCode}' belum dikonfigurasi di Setting, notifikasi di-skip.");
            return;
        }
        KirimNotifikasiWhatsapp::dispatch(
            $templateCode,
            $nomorTujuan,
            $variables,
            $referenceId ?? (string) Str::uuid(),
        )->onQueue('whatsapp')->afterCommit();
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    protected function kirimRequest(string $method, string $path, string $bodyString): array
    {
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $bodyString, $this->secret);

        $headers = [
            'Content-Type' => 'application/json',
            'X-API-Key' => $this->apiKeyId,
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
        ];

        $response = Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->withBody($bodyString, 'application/json')
            ->send($method, $this->baseUrl . $path);

        return [$response->status(), $response->json() ?? []];
    }
}
