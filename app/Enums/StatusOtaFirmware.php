<?php

namespace App\Enums;

/**
 * Value PERSIS string yang dikirim device di field "status" pada endpoint
 * POST /api/perpustakaan/firmware/report (kontrak baru, lihat
 * PerpustakaanDeviceController::firmwareReport()). Jangan ubah value tanpa
 * menyesuaikan firmware juga.
 */
enum StatusOtaFirmware: string
{
    case Sukses = 'success';
    case Gagal = 'failed';
}
