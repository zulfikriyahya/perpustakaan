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

    // Unik per hari per user hanya untuk baris AKTIF (deleted_at IS NULL) - dijaga
    // oleh generated column `unik_aktif` + unique index di DB (lihat migration
    // fix_unique_kunjungan_softdelete_aware). Kolom `unik_aktif` sengaja TIDAK
    // dimasukkan ke $fillable/casts karena murni computed oleh DB, jangan pernah
    // diisi manual dari Filament/kode aplikasi.
}
