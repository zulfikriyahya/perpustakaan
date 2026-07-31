<?php

namespace App\Models;

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

    // FIX: Rak tidak lagi punya relasi langsung ke Buku sejak migration
    // 2026_08_02_000003 (bukus.rak_id di-drop). Rak sekarang berelasi ke
    // Eksemplar (kopi fisik), bukan ke Buku (judul).
    public function eksemplars(): HasMany
    {
        return $this->hasMany(Eksemplar::class);
    }

    // TODO: GAP-SPEC - belum dikonfirmasi apakah Rak butuh hitungan "jumlah
    // judul buku unik" (distinct Buku) selain "jumlah eksemplar". Kalau ya,
    // tambahkan accessor terpisah pakai hasManyThrough(Buku::class,
    // Eksemplar::class)->distinct('bukus.id') - belum ditambahkan di sini
    // supaya tidak menebak kebutuhan tampilan.
}
