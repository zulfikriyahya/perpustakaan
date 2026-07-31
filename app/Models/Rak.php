<?php

namespace App\Models;

use App\Enums\StatusEksemplar;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rak extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'nama',
        'lokasi',
    ];

    public function kategoris(): BelongsToMany
    {
        return $this->belongsToMany(Kategori::class);
    }

    // Rak tidak lagi punya relasi langsung ke Buku sejak migration
    // 2026_08_02_000003 (bukus.rak_id di-drop). Rak sekarang berelasi ke
    // Eksemplar (kopi fisik), bukan ke Buku (judul).
    public function eksemplars(): HasMany
    {
        return $this->hasMany(Eksemplar::class);
    }

    // Jumlah judul Buku UNIK (distinct) di rak ini, terpisah dari jumlah
    // eksemplar fisik (Rak::eksemplars()->count()).
    public function jumlahJudulUnik(): int
    {
        return $this->eksemplars()->distinct('buku_id')->count('buku_id');
    }

    // GAP-SPEC ditutup: definisi "tersedia" DISAMAKAN persis dengan
    // Buku::stokTersedia() - HANYA status Tersedia yang dihitung, Dipinjam/
    // Rusak/Hilang semua dikecualikan (satu sumber kebenaran definisi
    // "tersedia" di seluruh aplikasi, Aturan poin 3).
    public function stokTersedia(): int
    {
        return $this->eksemplars()->where('status', StatusEksemplar::Tersedia)->count();
    }
}
