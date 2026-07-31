<?php

namespace App\Models;

use App\Enums\StatusEksemplar;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Eksemplar extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'buku_id',
        'barcode',
        'rak_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusEksemplar::class,
        ];
    }

    public function buku(): BelongsTo
    {
        return $this->belongsTo(Buku::class);
    }

    public function rak(): BelongsTo
    {
        return $this->belongsTo(Rak::class);
    }

    public function peminjamans(): HasMany
    {
        return $this->hasMany(Peminjaman::class);
    }
}
