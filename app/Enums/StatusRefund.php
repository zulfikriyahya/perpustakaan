<?php

namespace App\Enums;

enum StatusRefund: string
{
    case TidakPerlu = 'tidak_perlu';
    case PerluRefund = 'perlu_refund';
    case SudahDirefund = 'sudah_direfund';
}
