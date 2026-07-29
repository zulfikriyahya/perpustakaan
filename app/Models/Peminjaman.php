<?php

namespace App\Models;

use App\Enums\StatusPeminjaman;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Peminjaman extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'transaksi_id',
        'user_id',
        'buku_id',
        'tanggal_pinjam',
        'tanggal_jatuh_tempo',
        'status',
        'diproses_oleh',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tanggal_pinjam' => 'date',
            'tanggal_jatuh_tempo' => 'date',
            'status' => StatusPeminjaman::class,
            'diproses_oleh' => 'integer',
        ];
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function buku(): BelongsTo
    {
        return $this->belongsTo(Buku::class);
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function pengembalian(): HasOne
    {
        return $this->hasOne(Pengembalian::class);
    }

    public function dendas(): HasMany
    {
        return $this->hasMany(Denda::class);
    }
}
