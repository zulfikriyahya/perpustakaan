<?php

namespace App\Services;

use App\Enums\EventTypePoint;
use App\Enums\JenisTransaksi;
use App\Enums\SourceKunjungan;
use App\Models\Kunjungan;
use App\Models\Transaksi;
use App\Models\User;

class KunjunganService
{
    public function __construct(
        protected PointService $pointService,
        protected WhatsappService $whatsappService,
    ) {}

    /**
     * @param  string  $sumberLabel  label singkat sumber tap untuk audit
     *                               manual - dipakai di keterangan Transaksi maupun variabel 'device'
     *                               di WhatsApp (mis. device_id fisik ESP32, atau
     *                               'Sirkulasi (RFID Reader Web)' untuk tap via halaman web).
     */
    public function catatKunjungan(
        User $user,
        SourceKunjungan $source,
        string $sumberLabel,
        ?string $tanggal = null,
        ?string $jamTap = null,
    ): Kunjungan {
        $kunjungan = Kunjungan::create([
            'user_id' => $user->id,
            'tanggal' => $tanggal ?? today()->toDateString(),
            'jam_tap' => $jamTap ?? now()->toTimeString(),
            'source' => $source,
        ]);

        $this->pointService->catatEvent(
            $user,
            EventTypePoint::Kunjungan,
            'kunjungan',
            $kunjungan->id,
        );

        $this->catatTransaksiKunjungan($user, $kunjungan, $sumberLabel);
        $this->kirimNotifikasiKunjungan($user, $kunjungan, $sumberLabel);

        return $kunjungan;
    }

    protected function catatTransaksiKunjungan(User $user, Kunjungan $kunjungan, string $sumberLabel): Transaksi
    {
        return Transaksi::create([
            'user_id' => $user->id,
            'jenis' => JenisTransaksi::Kunjungan,
            'diproses_oleh' => null, // otomatis (device/tap), bukan input manual staff
            'tanggal' => now(),
            'keterangan' => "Kunjungan RFID jam {$kunjungan->jam_tap} via '{$sumberLabel}'.",
        ]);
    }

    protected function kirimNotifikasiKunjungan(User $user, Kunjungan $kunjungan, string $sumberLabel): void
    {
        $this->whatsappService->kirimEvent(
            eventCode: 'kunjungan_tercatat',
            nomorTujuan: $user->no_telepon,
            variables: [
                'nama' => $user->nama,
                'jam_tap' => (string) $kunjungan->jam_tap,
                'device' => $sumberLabel,
            ],
            referenceId: "kunjungan-{$kunjungan->id}",
        );
    }
}
