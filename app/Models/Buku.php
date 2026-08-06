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

    /**
     * BARU - relasi many-to-many ke Author. Kolom string 'penulis'
     * SENGAJA dipertahankan (legacy/fallback display) - TIDAK dihapus,
     * TIDAK di-backfill otomatis ke tabel authors.
     * TODO: GAP-SPEC - migrasi data 'penulis' (string) ke tabel authors
     * belum diputuskan; keduanya berjalan independen untuk saat ini.
     */
    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class);
    }

    /**
     * BARU - file digital (PDF/EPUB/audio). 1 buku boleh punya banyak
     * file lintas jenis sekaligus (dikonfirmasi).
     */
    public function files(): HasMany
    {
        return $this->hasMany(BukuFile::class)->orderBy('urutan');
    }

    public function stokTersedia(): int
    {
        return $this->eksemplars()->where('status', StatusEksemplar::Tersedia)->count();
    }

    public function jumlahEksemplarAktif(): int
    {
        return $this->eksemplars()->where('status', '!=', StatusEksemplar::Hilang)->count();
    }

    public function jumlahEksemplarRusak(): int
    {
        return $this->eksemplars()->where('status', StatusEksemplar::Rusak)->count();
    }

    public function jumlahEksemplarHilang(): int
    {
        return $this->eksemplars()->where('status', StatusEksemplar::Hilang)->count();
    }
}
