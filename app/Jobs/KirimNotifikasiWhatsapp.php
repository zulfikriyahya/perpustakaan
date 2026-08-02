<?php

namespace App\Jobs;

use App\Exceptions\WhatsappGatewayException;
use App\Models\WhatsappLog;
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
 *
 * LOGGING (baru, iterasi ini): setiap eksekusi handle() (termasuk retry)
 * melakukan UPSERT ke whatsapp_logs berdasarkan reference_id - BUKAN
 * insert baru per percobaan, supaya satu event tetap satu baris log
 * dengan status/keterangan TERBARU dan counter percobaan_ke bertambah.
 * TODO: GAP-SPEC - reference_id null (constructor mengizinkan ?string,
 * meski kirimEvent() di WhatsappService selalu mengisi fallback UUID)
 * akan selalu INSERT baris baru (tidak bisa di-upsert tanpa key unik) -
 * skenario ini seharusnya tidak pernah terjadi lewat kirimEvent(), tapi
 * dijaga agar job tidak fatal error jika suatu saat dipanggil manual
 * dengan referenceId null.
 */
class KirimNotifikasiWhatsapp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 25;

    public array $backoff = [5, 15, 30];

    private const STATUS_PERMANEN = [400, 403, 409];

    public function __construct(
        protected string $templateCode,
        protected string $nomorTujuan,
        protected array $variables,
        protected ?string $referenceId,
    ) {}

    public function handle(WhatsappService $whatsappService): void
    {
        $percobaanKe = $this->attempts();

        try {
            $whatsappService->kirimPesan(
                templateCode: $this->templateCode,
                recipient: $this->nomorTujuan,
                variables: $this->variables,
                referenceId: $this->referenceId,
            );

            $this->catatLog('terkirim', null, $percobaanKe);
        } catch (WhatsappGatewayException $e) {
            if (in_array($e->statusCode, self::STATUS_PERMANEN, true)) {
                Log::error("KirimNotifikasiWhatsapp: kegagalan permanen (status {$e->statusCode}), tidak di-retry. Template '{$this->templateCode}' ke {$this->nomorTujuan}: {$e->getMessage()}");

                $this->catatLog('gagal_permanen', $e->getMessage(), $percobaanKe);

                $this->fail($e);

                return;
            }

            Log::error("KirimNotifikasiWhatsapp: gagal mengirim template '{$this->templateCode}' ke {$this->nomorTujuan}: {$e->getMessage()}");

            $this->catatLog('gagal_transient', $e->getMessage(), $percobaanKe);

            throw $e;
        }
    }

    /**
     * Daftar nama variable yang dianggap sensitif dan WAJIB di-redact
     * sebelum disimpan ke whatsapp_logs (dikonfirmasi eksplisit - OTP
     * tidak boleh tersimpan plaintext permanen di log, beda dengan
     * login_otps/password_reset_otps yang sudah hashed by design).
     * TODO: GAP-SPEC - daftar ini match case-insensitive terhadap NAMA
     * key variable, bukan terhadap eventCode - kalau suatu saat ada
     * variable baru yang juga sensitif (mis. 'password_sementara'),
     * WAJIB ditambahkan di sini, satu tempat, bukan di tiap pemanggil.
     */
    private const VARIABLE_SENSITIF = ['otp', 'password', 'password_baru', 'password_sementara'];

    protected function catatLog(string $status, ?string $keterangan, int $percobaanKe): void
    {
        $atribut = [
            'template_code' => $this->templateCode,
            'nomor_tujuan' => $this->nomorTujuan,
            'variables' => $this->redactVariabelSensitif($this->variables),
            'status' => $status,
            'keterangan' => $keterangan,
            'percobaan_ke' => $percobaanKe,
        ];

        if ($this->referenceId === null) {
            WhatsappLog::create(['reference_id' => null, ...$atribut]);

            return;
        }

        WhatsappLog::updateOrCreate(
            ['reference_id' => $this->referenceId],
            $atribut,
        );
    }

    protected function redactVariabelSensitif(array $variables): array
    {
        $hasil = [];

        foreach ($variables as $key => $value) {
            $hasil[$key] = in_array(strtolower((string) $key), self::VARIABLE_SENSITIF, true)
                ? '***'
                : $value;
        }

        return $hasil;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("KirimNotifikasiWhatsapp: job gagal permanen. Template '{$this->templateCode}' ke {$this->nomorTujuan}: {$exception->getMessage()}");
    }
}
