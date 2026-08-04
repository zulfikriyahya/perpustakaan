<?php

namespace App\Enums;

enum GroupSetting: string
{
    case Peminjaman = 'peminjaman';
    case Point = 'point';
    case Notifikasi = 'notifikasi';
    case Denda = 'denda';
    case Device = 'device';
    case Whatsapp = 'whatsapp';
    case Kredensial = 'kredensial';
}
