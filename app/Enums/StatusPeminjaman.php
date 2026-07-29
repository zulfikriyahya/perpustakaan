<?php

namespace App\Enums;

enum StatusPeminjaman: string
{
    case Aktif = 'aktif';
    case Terlambat = 'terlambat';
    case Selesai = 'selesai';
    case Hilang = 'hilang';
}
