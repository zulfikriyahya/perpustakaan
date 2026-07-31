<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LevelBadge extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'nama_badge',
        'min_point',
        'max_point',
        'icon',
        'urutan',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function levelBadgeLogs(): HasMany
    {
        return $this->hasMany(LevelBadgeLog::class);
    }
}
