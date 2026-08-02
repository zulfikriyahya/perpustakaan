<?php

namespace App\Models;

use App\Enums\KondisiBuku;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pengembalian extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'peminjaman_id',
        'tanggal_kembali',
        'kondisi',
        'catatan',
        'diproses_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kembali' => 'date',
            'kondisi' => KondisiBuku::class,
            'diproses_oleh' => 'integer',
        ];
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class)->withTrashed();
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh')->withTrashed();
    }
}
