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
