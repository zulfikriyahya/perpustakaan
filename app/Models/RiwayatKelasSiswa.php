<?php

namespace App\Models;

use App\Enums\StatusRiwayatKelas;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiwayatKelasSiswa extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'riwayat_kelas_siswas';

    protected $fillable = [
        'user_id',
        'kelas_tahun_pelajaran_id',
        'status',
        'tanggal_mulai',
        'tanggal_selesai',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'status' => StatusRiwayatKelas::class,
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function kelasTahunPelajaran(): BelongsTo
    {
        return $this->belongsTo(KelasTahunPelajaran::class)->withTrashed();
    }
}
