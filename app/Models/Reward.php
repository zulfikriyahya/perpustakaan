<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reward extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'nama',
        'deskripsi',
        'threshold_point',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function rewardLogs(): HasMany
    {
        return $this->hasMany(RewardLog::class);
    }
}
