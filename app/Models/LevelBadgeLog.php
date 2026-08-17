<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LevelBadgeLog extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'level_badge_id',
        'tanggal_didapat',
        'sertifikat_path',
        'nomor_sertifikat',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tanggal_didapat' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function levelBadge(): BelongsTo
    {
        return $this->belongsTo(LevelBadge::class)->withTrashed();
    }
}
