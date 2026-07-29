<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DeviceLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'device_id',
        'device_name',
        'firmware_version',
        'uptime_sec',
        'heap_free',
        'pending_records',
        'scan_today',
        'rssi',
        'sd_ok',
        'rfid_db_entries',
        'online',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'sd_ok' => 'boolean',
            'online' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }
}
