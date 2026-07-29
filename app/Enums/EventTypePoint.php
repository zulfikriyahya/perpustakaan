<?php

namespace App\Enums;

enum EventTypePoint: string
{
    case Kunjungan = 'kunjungan';
    case Peminjaman = 'peminjaman';
    case Pengembalian = 'pengembalian';
    case Kerusakan = 'kerusakan';
    case Kehilangan = 'kehilangan';
}
