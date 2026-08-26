<?php

namespace App\Enums;

enum StatusRiwayatKelas: string
{
    case Aktif = 'aktif';
    case Naik = 'naik';
    case Tinggal = 'tinggal';
    case Lulus = 'lulus';
    case Keluar = 'keluar';
}
