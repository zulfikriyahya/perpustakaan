<?php

namespace App\Enums;

enum JenisTransaksi: string
{
    case Peminjaman = 'peminjaman';
    case Kunjungan = 'kunjungan';
    case PembayaranDenda = 'pembayaran_denda';
}
