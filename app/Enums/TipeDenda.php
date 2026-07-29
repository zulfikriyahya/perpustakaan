<?php

namespace App\Enums;

enum TipeDenda: string
{
    case Keterlambatan = 'keterlambatan';
    case Kerusakan = 'kerusakan';
    case Kehilangan = 'kehilangan';
}
