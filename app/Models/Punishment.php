<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Punishment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'nama',
        'deskripsi',
        'threshold_point_minus',
        'durasi_suspend_hari',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function punishmentLogs(): HasMany
    {
        return $this->hasMany(PunishmentLog::class);
    }
}
