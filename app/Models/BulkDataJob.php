<?php

namespace App\Models;

use App\Enums\StatusBulkJob;
use App\Enums\TipeBulkJob;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkDataJob extends Model
{
    use HasUuids;

    protected $fillable = [
        'tipe',
        'status',
        'nama_file_asli',
        'file_path',
        'laporan',
        'diproses_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tipe' => TipeBulkJob::class,
            'status' => StatusBulkJob::class,
            'laporan' => 'array',
        ];
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }
}
