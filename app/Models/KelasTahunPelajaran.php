<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KelasTahunPelajaran extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['kelas_id', 'tahun_pelajaran_id', 'wali_kelas_id'];

    protected function casts(): array
    {
        return ['wali_kelas_id' => 'integer'];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class)->withTrashed();
    }

    public function tahunPelajaran(): BelongsTo
    {
        return $this->belongsTo(TahunPelajaran::class)->withTrashed();
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_kelas_id')->withTrashed();
    }

    public function siswaAktif(): HasMany
    {
        return $this->hasMany(User::class, 'kelas_tahun_pelajaran_id');
    }

    public function riwayatSiswa(): HasMany
    {
        return $this->hasMany(RiwayatKelasSiswa::class);
    }
}
