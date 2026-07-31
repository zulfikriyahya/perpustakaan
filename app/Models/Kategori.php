<?php

namespace App\Models;

use App\Enums\StatusEksemplar;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kategori extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'nama',
        'deskripsi',
    ];

    public function bukus(): BelongsToMany
    {
        return $this->belongsToMany(Buku::class);
    }

    // dihitung via pivot buku_kategori, bukan kolom langsung di eksemplars
    public function eksemplars(): HasManyThrough
    {
        return $this->hasManyThrough(
            Eksemplar::class,
            BukuKategori::class,
            'kategori_id', // FK di buku_kategori -> kategoris.id
            'buku_id',     // FK di eksemplars -> bukus.id
            'id',          // local key di Kategori
            'buku_id',     // local key di buku_kategori (= bukus.id)
        );
    }

    public function raks(): BelongsToMany
    {
        return $this->belongsToMany(Rak::class);
    }

    // dihitung on-the-fly, sama pola dengan Buku::stokTersedia()
    public function stokTersedia(): int
    {
        return $this->eksemplars()->where('status', StatusEksemplar::Tersedia)->count();
    }
}
