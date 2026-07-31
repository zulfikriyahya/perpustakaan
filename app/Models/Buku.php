<?php

namespace App\Models;

use App\Enums\StatusEksemplar;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Buku extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'judul',
        'cover',
        'penulis',
        'penerbit',
        'isbn',
        'tahun_terbit',
        'harga_ganti',
        'deskripsi',
    ];

    protected function casts(): array
    {
        return [
            'harga_ganti' => 'decimal:2',
            'tahun_terbit' => 'integer',
        ];
    }

    public function kategoris(): BelongsToMany
    {
        return $this->belongsToMany(Kategori::class);
    }

    public function eksemplars(): HasMany
    {
        return $this->hasMany(Eksemplar::class);
    }

    // dihitung on-the-fly, bukan field statis lagi
    public function stokTersedia(): int
    {
        return $this->eksemplars()->where('status', StatusEksemplar::Tersedia)->count();
    }
}
