<?php

namespace App\Services;

use App\Models\User;
use RuntimeException;

class RfidResolverService
{
    public function findByKartu(string $kartu): ?User
    {
        return User::query()->where('no_kartu_rfid', $kartu)->first();
    }

    /**
     * @throws RuntimeException jika user tidak ditemukan dari kartu, NISN,
     *                          maupun NIP
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

        $user = User::query()->where('nip', $inputKartuAtauNisn)->first();

        if ($user) {
            return $user;
        }

        throw new RuntimeException(
            "User tidak ditemukan untuk kartu/NISN/NIP '{$inputKartuAtauNisn}'. Pastikan kartu sudah didaftarkan atau gunakan NISN/NIP yang valid."
        );
    }
}
