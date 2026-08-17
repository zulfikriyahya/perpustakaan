<?php

namespace App\Support;

/**
 * Satu sumber kebenaran normalisasi format No. Telepon Indonesia
 * (Aturan poin 3) - dipakai oleh FormatNomorTelepon (Rule, validasi
 * form/import) DAN WhatsappService (safety net sebelum kirim ke gateway),
 * supaya logika normalisasi tidak terduplikasi/drift di dua tempat.
 *
 * Menerima input "kotor" apa pun yang masih bisa diartikan sebagai nomor
 * seluler Indonesia - boleh mengandung '+', spasi, strip, dan salah satu
 * prefix 08xxx / 628xxx / 8xxx, TERMASUK salah ketik '+62' diikuti '0xxx'
 * (mis. '+62 0812...' -> digit '620812...') yang dianggap '0' redundan
 * dan dibuang (dikonfirmasi eksplisit) - lalu mengembalikan bentuk baku
 * 628xxxxxxxxx (tanpa '+', tanpa spasi/strip). Mengembalikan null jika
 * setelah dibersihkan hasilnya TIDAK bisa dianggap nomor seluler
 * Indonesia yang valid - caller (Rule/WhatsappService) yang memutuskan
 * bagaimana menangani null (reject validasi / gagal kirim + log).
 *
 * TODO: GAP-SPEC - panjang digit setelah '628' dibatasi 7-11 (total nomor
 * dengan prefix 628 jadi 10-14 digit) - sesuaikan jika ada nomor valid di
 * luar rentang ini yang tertolak keliru.
 */
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

        // '620xxx...' - '+62' diikuti '0xxx' (salah ketik lazim, dikonfirmasi
        // dianggap '0' redundan) - buang '0' setelah '62' lalu proses sebagai
        // digit ber-prefix '62' biasa.
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
