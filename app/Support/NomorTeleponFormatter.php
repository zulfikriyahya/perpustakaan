<?php

namespace App\Support;

class NomorTeleponFormatter
{
    public static function normalisasi(?string $nomor): ?string
    {
        if ($nomor === null || trim($nomor) === '') {
            return null;
        }

        // Buang semua karakter selain digit ('+', spasi, strip, dsb.)
        $digitSaja = preg_replace('/[^0-9]/', '', $nomor) ?? '';

        if ($digitSaja === '') {
            return null;
        }

        if (str_starts_with($digitSaja, '620')) {
            $digitSaja = '62'.substr($digitSaja, 3);
        }

        $ternormalisasi = match (true) {
            str_starts_with($digitSaja, '62') => $digitSaja,
            str_starts_with($digitSaja, '0') => '62'.substr($digitSaja, 1),
            str_starts_with($digitSaja, '8') => '62'.$digitSaja,
            default => null,
        };

        if ($ternormalisasi === null) {
            return null;
        }

        return preg_match('/^628[0-9]{7,11}$/', $ternormalisasi) ? $ternormalisasi : null;
    }
}
