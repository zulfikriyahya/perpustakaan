<?php

namespace App\Models;

use App\Enums\TipeDenda;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Denda extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'peminjaman_id',
        'user_id',
        'tipe',
        'nominal',
        'status_lunas',
        'tanggal_lunas',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tipe' => TipeDenda::class,
            'nominal' => 'decimal:2',
            'status_lunas' => 'boolean',
            'tanggal_lunas' => 'datetime',
        ];
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
