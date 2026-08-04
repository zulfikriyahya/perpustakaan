<?php

namespace App\Enums;

enum StatusBulkJob: string
{
    case Pending = 'pending';
    case Diproses = 'diproses';
    case Selesai = 'selesai';
    case Gagal = 'gagal';
}
