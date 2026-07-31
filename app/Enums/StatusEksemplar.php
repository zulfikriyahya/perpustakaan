<?php

namespace App\Enums;

enum StatusEksemplar: string
{
    case Tersedia = 'tersedia';
    case Dipinjam = 'dipinjam';
    case Rusak = 'rusak';
    case Hilang = 'hilang';
}
