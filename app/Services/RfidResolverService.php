<?php

namespace App\Services;

use App\Models\User;
use RuntimeException;

/**
 * Resolusi User dari input reader RFID/keyboard-wedge (tersambung ke komputer)
 * untuk konteks Peminjaman/Pengembalian, maupun dari kartu RFID yang dikirim
 * device Attendance Machine (ESP32) untuk konteks Kunjungan. Satu sumber
 * kebenaran untuk matching kartu-ke-user (Aturan poin 3) - jangan menulis ulang
 * query 'no_kartu_rfid' di tempat lain.
 */
class RfidResolverService
{
    /**
     * Cari user berdasarkan nomor kartu RFID saja (tanpa fallback NISN, tanpa
     * throw). Dipakai konteks yang tidak boleh melempar exception, mis.
     * endpoint device (respons 404/"error" per item, bukan 500).
     */
    public function findByKartu(string $kartu): ?User
    {
        return User::query()->where('no_kartu_rfid', $kartu)->first();
    }

    /**
     * @throws RuntimeException jika user tidak ditemukan dari kartu maupun NISN
     */
    public function resolveUser(string $inputKartuAtauNisn): User
    {
        $user = $this->findByKartu($inputKartuAtauNisn);

        if ($user) {
            return $user;
        }

        $user = User::query()->where('nisn', $inputKartuAtauNisn)->first();

        if ($user) {
            return $user;
        }

        throw new RuntimeException(
            "User tidak ditemukan untuk kartu/NISN '{$inputKartuAtauNisn}'. Pastikan kartu sudah didaftarkan atau gunakan NISN yang valid."
        );
    }
}
