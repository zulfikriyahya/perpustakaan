<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SnapshotHarian extends Model
{
    protected $fillable = [
        'tanggal',
        'peminjaman_baru',
        'peminjaman_terlambat',
        'denda_baru',
        'kunjungan',
        'total_judul_buku',
        'total_anggota_aktif',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'peminjaman_baru' => 'integer',
            'peminjaman_terlambat' => 'integer',
            'denda_baru' => 'integer',
            'kunjungan' => 'integer',
            'total_judul_buku' => 'integer',
            'total_anggota_aktif' => 'integer',
        ];
    }
}
