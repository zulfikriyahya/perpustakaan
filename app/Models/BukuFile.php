<?php

namespace App\Models;

use App\Enums\JenisFileBuku;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BukuFile extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'buku_id',
        'jenis',
        'path',
        'nama_file',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'jenis' => JenisFileBuku::class,
            'urutan' => 'integer',
        ];
    }

    public function buku(): BelongsTo
    {
        return $this->belongsTo(Buku::class);
    }

    /**
     * BARU - path RELATIF ('/storage/xxx'), BUKAN Storage::disk('public')->url()
     * yang menghasilkan URL absolut dari config APP_URL. Alasan: jika
     * APP_URL di .env berbeda hostname dengan yang dipakai browser saat
     * akses (mis. APP_URL=127.0.0.1 tapi browser buka localhost), browser
     * menganggap keduanya origin BERBEDA dan pdf.js gagal fetch karena
     * diblokir CORS meski server-nya sama persis.
     * TODO: GAP-SPEC - jika nanti disk 'public' dipindah ke S3/CDN
     * eksternal, method ini WAJIB diubah kembali ke Storage::url() dan
     * CORS di sisi storage eksternal harus dikonfigurasi eksplisit.
     */
    public function url(): string
    {
        return '/storage/' . $this->path;
    }
}
