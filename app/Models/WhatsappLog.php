<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    protected $fillable = [
        'reference_id',
        'template_code',
        'nomor_tujuan',
        'variables',
        'status',
        'keterangan',
        'percobaan_ke',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
        ];
    }
}
