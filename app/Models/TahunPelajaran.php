<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TahunPelajaran extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['nama', 'tanggal_mulai', 'tanggal_selesai', 'aktif'];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'aktif' => 'boolean',
        ];
    }

    public function kelasTahunPelajarans(): HasMany
    {
        return $this->hasMany(KelasTahunPelajaran::class);
    }

    public static function aktif(): ?self
    {
        return static::query()->where('aktif', true)->first();
    }
}
