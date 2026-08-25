<?php

namespace App\Services;

use App\Models\LevelBadgeLog;
use App\Models\RewardLog;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;
use RuntimeException;

/**
 * Satu sumber kebenaran generate PDF sertifikat Reward/Badge (Aturan
 * poin 3) - dipanggil dari PointService saat threshold tercapai. Jangan
 * duplikasi pemanggilan Pdf::loadView('pdf.sertifikat', ...), QRCode,
 * atau BarcodeGeneratorPNG di tempat lain - reuse method di sini.
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
            tipeLabel: 'Sertifikat Reward',
            namaPenerima: $log->user->nama,
            namaItem: $log->reward->nama,
            deskripsiItem: $log->reward->deskripsi,
            urlVerifikasi: route('sertifikat.reward', $log),
        );
    }

    public function generateUntukBadge(LevelBadgeLog $log): ?string
    {
        $log->loadMissing(['user', 'levelBadge']);

        return $this->generate(
            log: $log,
            tipe: 'badge',
            judulSertifikat: 'Sertifikat Pencapaian Badge',
            tipeLabel: 'Sertifikat Badge',
            namaPenerima: $log->user->nama,
            namaItem: $log->levelBadge->nama_badge,
            deskripsiItem: null,
            urlVerifikasi: route('sertifikat.badge', $log),
        );
    }

    /**
     * @param  RewardLog|LevelBadgeLog  $log
     *
     * Kegagalan generate PDF (mis. dompdf error, atau gagal tulis ke
     * disk) di-catch dan di-log, TIDAK melempar exception ke pemanggil -
     * dipanggil dari dalam DB::transaction PointService dan sertifikat
     * bukan bagian dari data transaksional inti (Point/Badge/Reward
     * tetap tercatat valid walau sertifikat gagal dibuat).
     *
     * TODO: GAP-SPEC - belum ada mekanisme retry/regenerate otomatis
     * untuk sertifikat yang gagal generate di percobaan pertama; saat
     * ini hanya bisa dicek manual dari kolom sertifikat_path = null,
     * atau ditrigger manual lewat Action "Regenerate Sertifikat".
     *
     * TODO: GAP-SPEC - QR code mengarah ke URL publik sertifikat itu
     * sendiri (bukan endpoint verifikasi terpisah dengan payload
     * terenkripsi/tersimpan). Ini konsisten dengan keputusan yang sudah
     * dikonfirmasi bahwa akses sertifikat publik murni via UUID tanpa
     * signed URL - jadi QR ini bersifat "tautan cepat", bukan bukti
     * kriptografis keaslian.
     */
    protected function generate(
        RewardLog|LevelBadgeLog $log,
        string $tipe,
        string $judulSertifikat,
        string $tipeLabel,
        string $namaPenerima,
        string $namaItem,
        ?string $deskripsiItem,
        string $urlVerifikasi,
    ): ?string {
        try {
            $nomor = $this->buatNomorSertifikat($tipe, $log->id, $log->tanggal_didapat);

            $pdf = Pdf::loadView('pdf.sertifikat', [
                'judulSertifikat' => $judulSertifikat,
                'tipeLabel' => $tipeLabel,
                'namaPenerima' => $namaPenerima,
                'namaItem' => $namaItem,
                'deskripsiItem' => $deskripsiItem,
                'tanggal' => $log->tanggal_didapat,
                'nomorSertifikat' => $nomor,
                'qrGambar' => $this->buatQrCode($urlVerifikasi),
                'barcodeGambar' => $this->buatBarcodeNomor($nomor),
            ])->setPaper('a4', 'portrait');

            $path = "sertifikat/{$tipe}/{$log->id}.pdf";
            $berhasilTulis = Storage::disk('public')->put($path, $pdf->output());

            if (! $berhasilTulis) {
                throw new RuntimeException("Storage::put() mengembalikan false untuk path '{$path}' - kemungkinan permission disk atau disk penuh.");
            }

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

    // dipakai untuk tautan cepat verifikasi, lihat TODO: GAP-SPEC di generate()
    protected function buatQrCode(string $urlVerifikasi): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'imageBase64' => true,
            'scale' => 4,
            'imageTransparent' => false,
        ]);

        // chillerlan/php-qrcode dengan imageBase64=true sudah mengembalikan
        // string data URI lengkap ("data:image/png;base64,...."), jangan
        // di-encode base64 dua kali.
        return (new QRCode($options))->render($urlVerifikasi);
    }

    // reuse generator Code128 yang sama dipakai LabelBarcodeService (Aturan poin 3)
    protected function buatBarcodeNomor(string $nomor): string
    {
        $generator = new BarcodeGeneratorPNG;

        $png = $generator->getBarcode(
            $nomor,
            $generator::TYPE_CODE_128,
            2,
            40,
        );

        return 'data:image/png;base64,' . base64_encode($png);
    }

    /**
     * TODO: ASUMSI - format nomor sertifikat belum dispesifikasikan di
     * dokumen acuan. Pola sementara: {TIPE}/{TAHUN}/{8 karakter AKHIR
     * UUID log, uppercase}.
     *
     * Tahun diambil dari $tanggalDidapat (kapan pencapaian diraih),
     * BUKAN now() (kapan PDF di-generate/regenerate) - supaya nomor
     * sertifikat tetap identik selamanya untuk satu log, berapa kali pun
     * di-regenerate. Hanya bagian UUID di akhir yang membedakan antar
     * log berbeda (lihat catatan UUIDv7 di bawah).
     *
     * 8 karakter diambil dari AKHIR UUID, bukan awal - Model ini pakai
     * HasUuids yang secara default menghasilkan UUIDv7 (ordered/time
     * based), di mana karakter awal merepresentasikan timestamp
     * milidetik, BUKAN bagian acak. Log yang dibuat berdekatan waktu
     * (mis. PointService memproses badge banyak siswa sekaligus dalam
     * satu run) akan punya 8 karakter pertama yang SAMA jika dipotong
     * dari depan. Bagian akhir UUIDv7 tetap berasal dari bagian
     * random/node, jauh lebih aman dari kolisi.
     *
     * Ganti jika sekolah punya format penomoran resmi.
     */
    protected function buatNomorSertifikat(string $tipe, string $logId, \DateTimeInterface $tanggalDidapat): string
    {
        return sprintf(
            '%s/%s/%s',
            strtoupper($tipe),
            \Illuminate\Support\Carbon::parse($tanggalDidapat)->format('Y'),
            strtoupper(substr($logId, -8)),
        );
    }
}
