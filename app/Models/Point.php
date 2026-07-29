<?php

namespace App\Models;

use App\Enums\EventTypePoint;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Point extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'event_type',
        'nilai',
        'ref_type',
        'ref_id',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'event_type' => EventTypePoint::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ref_type/ref_id: polymorphic manual, bukan Eloquent relation - lihat PointService.
}
