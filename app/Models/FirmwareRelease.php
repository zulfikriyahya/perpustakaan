<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FirmwareRelease extends Model
{
    use HasUuids;

    protected $fillable = [
        'version',
        'url',
        'md5',
        'aktif',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }
}
