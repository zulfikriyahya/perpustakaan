<?php

namespace App\Services;

use App\Enums\RoleUser;
use App\Enums\StatusPeminjaman;
use App\Models\Buku;
use App\Models\Denda;
use App\Models\Kunjungan;
use App\Models\Peminjaman;
use App\Models\SnapshotHarian;
use App\Models\User;
use Carbon\Carbon;

class SnapshotHarianService
{
    public function catatUntukTanggal(Carbon $tanggal): SnapshotHarian
    {
        $tanggal = $tanggal->copy()->startOfDay();

        return SnapshotHarian::query()->updateOrCreate(
            ['tanggal' => $tanggal->toDateString()],
            [
                'peminjaman_baru' => Peminjaman::query()
                    ->whereDate('tanggal_pinjam', $tanggal)
                    ->count(),

                'peminjaman_terlambat' => Peminjaman::query()
                    ->whereDate('tanggal_jatuh_tempo', $tanggal)
                    ->where('status', StatusPeminjaman::Terlambat)
                    ->count(),

                'denda_baru' => Denda::query()
                    ->whereDate('created_at', $tanggal)
                    ->count(),

                'kunjungan' => Kunjungan::query()
                    ->whereDate('tanggal', $tanggal)
                    ->count(),

                'total_judul_buku' => Buku::query()
                    ->whereDate('created_at', '<=', $tanggal->copy()->endOfDay())
                    ->count(),

                'total_anggota_aktif' => User::query()
                    ->whereNotIn('role', [RoleUser::Admin, RoleUser::Pustakawan])
                    ->whereDate('created_at', '<=', $tanggal->copy()->endOfDay())
                    ->count(),
            ],
        );
    }
}
