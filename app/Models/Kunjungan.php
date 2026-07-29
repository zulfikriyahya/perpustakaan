<?php

namespace App\Models;

use App\Enums\SourceKunjungan;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kunjungan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'tanggal',
        'jam_tap',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tanggal' => 'date',
            'source' => SourceKunjungan::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
