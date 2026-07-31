<?php

namespace App\Models;

use App\Enums\StatusEksemplar;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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

    /**
     * Satu sumber kebenaran format barcode auto-generate (Aturan poin 3
     * - DRY). SEBELUMNYA duplikat persis di BukuImporter::afterSave() dan
     * CreateBuku::afterCreate() - kedua caller sekarang memanggil ini.
     * Format: "{ISBN-atau-JUDULSLUG}-{urutan}", fallback suffix random
     * kalau barcode hasil generate kebetulan sudah dipakai (unique
     * constraint kolom 'barcode').
     */
    public static function generateBarcodeUntuk(Buku $buku, int $urutan): string
    {
        $barcode = strtoupper(($buku->isbn ?: Str::slug($buku->judul)).'-'.$urutan);

        if (static::query()->where('barcode', $barcode)->exists()) {
            $barcode .= '-'.strtoupper(Str::random(4));
        }

        return $barcode;
    }
}
