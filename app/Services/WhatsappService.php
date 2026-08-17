<?php

namespace App\Services;

use App\Exceptions\WhatsappGatewayException;
use App\Jobs\KirimNotifikasiWhatsapp;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Support\NomorTeleponFormatter;

/**
 * Wrapper untuk WhatsApp Gateway (whatsapp.zedlabs.id API v1, autentikasi HMAC-SHA256).
 * Signature dihitung dari raw body bytes persis seperti yang dikirim - lihat
 * dokumen kontrak API bagian 2.1. Jangan format ulang body setelah signing.
 *
 * Kredensial SEKARANG dibaca dari Setting (grup Kredensial, lihat
 * PengaturanSistem) - dengan FALLBACK ke config()/.env jika Settingbelum
 * diisi (mis. fresh install belum sempat dikonfigurasi Admin lewat panel).
 * Operator `?:` dipakai (bukan `??`) karena Setting::get() bisa
 * mengembalikan string kosong '' yang harus tetap dianggap "belum diisi".
 *
 * NORMALISASI NOMOR (baru, iterasi ini): recipient SELALU dinormalisasi ke
 * format 62xxxxxxxxxx (tanpa '+') sebelum dikirim ke gateway - lihat
 * normalisasiNomor(). Ini adalah SAFETY NET di satu titik terpusat (Aturan
 * poin 3), independen dari FormatNomorTelepon (validasi form/import) -
 * karena data lama yang tersimpan sebelum Rule ini ada (mis. dari import
 * lama, atau format '0'/'8' tanpa prefix) tetap bisa lolos ke sini lewat
 * User.no_telepon yang belum sempat divalidasi ulang (dikonfirmasi: data
 * lama tidak dibackfill). Jika setelah normalisasi hasilnya TETAP tidak
 * sesuai pola nomor seluler Indonesia, pengiriman digagalkan PERMANEN
 * (bukan dikirim mentah ke gateway) - mencegah resiko banned nomor WA
 * gateway karena format salah.
 */
class WhatsappService
{
    protected string $baseUrl;

    protected string $apiKeyId;

    protected string $secret;

    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) (Setting::get('whatsapp_gateway_base_url') ?: config('services.whatsapp_gateway.base_url')), '/');
        $this->apiKeyId = (string) (Setting::get('whatsapp_gateway_api_key_id') ?: config('services.whatsapp_gateway.api_key_id'));
        $this->secret = (string) (Setting::get('whatsapp_gateway_secret') ?: config('services.whatsapp_gateway.secret'));
        $this->timeout = (int) (Setting::get('whatsapp_gateway_timeout') ?: config('services.whatsapp_gateway.timeout', 15));
    }

    /**
     * @param  array<string, mixed>  $variables
     * @param  array<string, mixed>|null  $media
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
        $recipientTernormalisasi = NomorTeleponFormatter::normalisasi($recipient);

        if ($recipientTernormalisasi === null) {
            Log::error("WhatsappService: recipient '{$recipient}' tidak bisa dinormalisasi menjadi format nomor seluler Indonesia yang valid (628xxxxxxxxx) - pengiriman DIBATALKAN, tidak diteruskan ke gateway, untuk mencegah resiko banned nomor.");

            // dihitung sebagai kegagalan permanen (status 400) oleh
            // KirimNotifikasiWhatsapp::STATUS_PERMANEN - retry tidak akan
            // pernah memperbaiki format nomor yang salah.
            throw new WhatsappGatewayException(400, "Format nomor tujuan '{$recipient}' tidak valid setelah normalisasi.");
        }

        $body = [
            'template_code' => $templateCode,
            'recipient' => $recipientTernormalisasi,
            'variables' => $variables,
            'media' => $media,
        ];

        if ($referenceId !== null) {
            $body['reference_id'] = $referenceId;
        }

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
     * Normalisasi nomor ke format baku pengiriman gateway: 62xxxxxxxxxx
     * (tanpa '+', tanpa spasi/strip). Menangani 3 pola yang divalidasi
     * FormatNomorTelepon (628xxx, 08xxx, 8xxx) SEKALIGUS input "kotor"
     * dari data lama (mis. mengandung spasi/strip/'+').
     */
    protected function normalisasiNomor(string $nomor): string
    {
        $nomor = preg_replace('/[^0-9]/', '', $nomor) ?? '';

        if (str_starts_with($nomor, '0')) {
            return '62' . substr($nomor, 1);
        }

        if (! str_starts_with($nomor, '62')) {
            return '62' . $nomor;
        }

        return $nomor;
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
