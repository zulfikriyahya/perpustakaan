<?php

namespace App\Services;

use App\Models\LevelBadgeLog;
use App\Models\RewardLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Satu sumber kebenaran generate PDF sertifikat Reward/Badge (Aturan
 * poin 3) - dipanggil dari PointService saat threshold tercapai. Jangan
 * duplikasi pemanggilan Pdf::loadView('pdf.sertifikat', ...) di tempat
 * lain (mis. Filament Action) - reuse method di sini.
 */
class SertifikatService
{
    public function generateUntukReward(RewardLog $log): ?string
    {
        $log->loadMissing(['user', 'reward']);

        return $this->generate(
            log: $log,
            tipe: 'reward',
            judulSertifikat: 'Sertifikat Penghargaan',
            namaPenerima: $log->user->nama,
            namaItem: $log->reward->nama,
            deskripsiItem: $log->reward->deskripsi,
        );
    }

    public function generateUntukBadge(LevelBadgeLog $log): ?string
    {
        $log->loadMissing(['user', 'levelBadge']);

        return $this->generate(
            log: $log,
            tipe: 'badge',
            judulSertifikat: 'Sertifikat Pencapaian Badge',
            namaPenerima: $log->user->nama,
            namaItem: $log->levelBadge->nama_badge,
            deskripsiItem: null,
        );
    }

    /**
     * @param  RewardLog|LevelBadgeLog  $log
     *
     * Kegagalan generate PDF (mis. dompdf error) di-catch dan di-log,
     * TIDAK melempar exception - dipanggil dari dalam DB::transaction
     * PointService dan sertifikat bukan bagian dari data transaksional
     * inti (Point/Badge/Reward tetap tercatat valid walau sertifikat
     * gagal dibuat). TODO: GAP-SPEC - belum ada mekanisme retry/regenerate
     * otomatis untuk sertifikat yang gagal generate di percobaan pertama;
     * saat ini hanya bisa dicek manual dari kolom sertifikat_path = null.
     */
    protected function generate(
        RewardLog|LevelBadgeLog $log,
        string $tipe,
        string $judulSertifikat,
        string $namaPenerima,
        string $namaItem,
        ?string $deskripsiItem,
    ): ?string {
        try {
            $nomor = $this->buatNomorSertifikat($tipe, $log->id);

            $pdf = Pdf::loadView('pdf.sertifikat', [
                'judulSertifikat' => $judulSertifikat,
                'namaPenerima' => $namaPenerima,
                'namaItem' => $namaItem,
                'deskripsiItem' => $deskripsiItem,
                'tanggal' => $log->tanggal_didapat,
                'nomorSertifikat' => $nomor,
            ])->setPaper('a4', 'landscape');

            $path = "sertifikat/{$tipe}/{$log->id}.pdf";
            Storage::disk('public')->put($path, $pdf->output());

            $log->forceFill([
                'sertifikat_path' => $path,
                'nomor_sertifikat' => $nomor,
            ])->save();

            return $path;
        } catch (\Throwable $e) {
            Log::error("SertifikatService: gagal generate sertifikat {$tipe} untuk log id '{$log->id}': {$e->getMessage()}");

            return null;
        }
    }

    /**
     * TODO: ASUMSI - format nomor sertifikat belum dispesifikasikan di
     * dokumen acuan. Pola sementara: {TIPE}/{TAHUN}/{8 karakter awal UUID
     * log, uppercase} - unik per log karena UUID sudah unik, dan mudah
     * dibaca manusia. Ganti jika sekolah punya format penomoran resmi.
     */
    protected function buatNomorSertifikat(string $tipe, string $logId): string
    {
        return sprintf('%s/%s/%s', strtoupper($tipe), now()->format('Y'), strtoupper(substr($logId, 0, 8)));
    }
}
