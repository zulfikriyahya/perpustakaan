<?php

namespace App\Services;

use App\Enums\EventTypePoint;
use App\Enums\JenisTransaksi;
use App\Enums\SourceKunjungan;
use App\Models\Kunjungan;
use App\Models\Transaksi;
use App\Models\User;

/**
 * Satu sumber kebenaran untuk SELURUH efek samping saat Kunjungan berhasil
 * tercatat (Point, Transaksi log, notifikasi WhatsApp) - dipakai bersama
 * oleh PerpustakaanDeviceController (tap fisik via Attendance Machine
 * ESP32) DAN Sirkulasi (tap via RFID reader keyboard-wedge di halaman
 * web) - Aturan poin 3 (DRY). Method ini TIDAK melakukan pengecekan
 * duplikat (exists() hari ini) - itu tetap tanggung jawab caller, karena
 * masing-masing caller punya kebutuhan response yang berbeda saat
 * duplikat terdeteksi (mis. device controller balas HTTP 400/'error',
 * Sirkulasi cukup tetap tampilkan modal).
 *
 * Kontrak: caller WAJIB menangkap Illuminate\Database\QueryException
 * sendiri untuk race condition unique index kunjungans_unik_aktif_unique
 * (lihat pemanggil di PerpustakaanDeviceController/Sirkulasi) - method ini
 * sengaja TIDAK menangkapnya supaya caller bisa memutuskan response yang
 * sesuai kontrak masing-masing (Aturan poin 17 - kontrak device binding,
 * tidak boleh diseragamkan diam-diam).
 */
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

    /**
     * TODO: GAP-SPEC - Transaksi hasil ini TIDAK menyimpan FK balik ke
     * Kunjungan (tabel kunjungans tidak punya kolom transaksi_id, sengaja
     * tidak ditambah migration baru - lihat diskusi terkait di
     * PerpustakaanDeviceController versi sebelumnya). Transaksi murni log
     * independen, keterangan berisi ringkasan (jam tap + sumber) untuk
     * audit manual.
     */
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

    /**
     * TODO: ASUMSI - eventCode 'kunjungan_tercatat' dan variabel
     * nama/jam_tap/device WAJIB terdaftar di panel gateway zedlabs dengan
     * template_code PERSIS 'kunjungan_tercatat' (lihat catatan lama di
     * PerpustakaanDeviceController) - berlaku sama untuk kedua sumber tap.
     */
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
