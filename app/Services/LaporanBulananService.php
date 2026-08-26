<?php

namespace App\Services;

use App\Models\Denda;
use App\Models\Kunjungan;
use App\Models\LevelBadgeLog;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\Point;
use App\Models\PunishmentLog;
use App\Models\RewardLog;
use Illuminate\Support\Carbon;

class LaporanBulananService
{
    public function generate(int $bulan, int $tahun): array
    {
        $awal = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();

        return [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'periode_label' => $awal->translatedFormat('F Y'),
            'peminjaman' => $this->dataPeminjaman($awal, $akhir),
            'pengembalian' => $this->dataPengembalian($awal, $akhir),
            'denda' => $this->dataDenda($awal, $akhir),
            'kunjungan' => $this->dataKunjungan($awal, $akhir),
            'point' => $this->dataPoint($awal, $akhir),
            'poin_reward_punishment' => $this->dataPoinRewardPunishment($awal, $akhir),
        ];
    }

    protected function dataPeminjaman(Carbon $awal, Carbon $akhir): array
    {
        $records = Peminjaman::query()
            ->with(['user', 'eksemplar.buku'])
            ->whereBetween('tanggal_pinjam', [$awal->toDateString(), $akhir->toDateString()])
            ->orderBy('tanggal_pinjam')
            ->get();

        return [
            'total' => $records->count(),
            'per_status' => $records->groupBy(fn ($r) => $r->status->value)->map->count(),
            'detail' => $records,
        ];
    }

    protected function dataPengembalian(Carbon $awal, Carbon $akhir): array
    {
        $records = Pengembalian::query()
            ->with(['peminjaman.user', 'peminjaman.eksemplar.buku'])
            ->whereBetween('tanggal_kembali', [$awal->toDateString(), $akhir->toDateString()])
            ->orderBy('tanggal_kembali')
            ->get();

        return [
            'total' => $records->count(),
            'per_kondisi' => $records->groupBy(fn ($r) => $r->kondisi->value)->map->count(),
            'detail' => $records,
        ];
    }

    protected function dataDenda(Carbon $awal, Carbon $akhir): array
    {
        $records = Denda::query()
            ->with(['user', 'peminjaman.eksemplar.buku'])
            ->whereBetween('created_at', [$awal, $akhir])
            ->orderBy('created_at')
            ->get();

        return [
            'total' => $records->count(),
            'total_nominal' => $records->sum('nominal'),
            'total_nominal_lunas' => $records->where('status_lunas', true)->sum('nominal'),
            'total_nominal_belum_lunas' => $records->where('status_lunas', false)->sum('nominal'),
            'per_tipe' => $records->groupBy(fn ($r) => $r->tipe->value)->map(fn ($g) => [
                'jumlah' => $g->count(),
                'nominal' => $g->sum('nominal'),
            ]),
            'detail' => $records,
        ];
    }

    protected function dataKunjungan(Carbon $awal, Carbon $akhir): array
    {
        $records = Kunjungan::query()
            ->with('user')
            ->whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])
            ->orderBy('tanggal')
            ->get();

        return [
            'total' => $records->count(),
            'user_unik' => $records->pluck('user_id')->unique()->count(),
            'per_source' => $records->groupBy(fn ($r) => $r->source->value)->map->count(),
            'detail' => $records,
        ];
    }

    protected function dataPoint(Carbon $awal, Carbon $akhir): array
    {
        $records = Point::query()
            ->with('user')
            ->whereBetween('created_at', [$awal, $akhir])
            ->orderBy('created_at')
            ->get();

        return [
            'total_transaksi' => $records->count(),
            'total_nilai' => $records->sum('nilai'),
            'per_event' => $records->groupBy(fn ($r) => $r->event_type->value)->map(fn ($g) => [
                'jumlah' => $g->count(),
                'total_nilai' => $g->sum('nilai'),
            ]),
            'detail' => $records,
        ];
    }

    protected function dataPoinRewardPunishment(Carbon $awal, Carbon $akhir): array
    {
        $badgeLogs = LevelBadgeLog::query()
            ->with(['user', 'levelBadge'])
            ->whereBetween('tanggal_didapat', [$awal, $akhir])
            ->orderBy('tanggal_didapat')
            ->get();

        $rewardLogs = RewardLog::query()
            ->with(['user', 'reward'])
            ->whereBetween('tanggal_didapat', [$awal, $akhir])
            ->orderBy('tanggal_didapat')
            ->get();

        $punishmentLogs = PunishmentLog::query()
            ->with(['user', 'punishment'])
            ->whereBetween('tanggal_diterapkan', [$awal, $akhir])
            ->orderBy('tanggal_diterapkan')
            ->get();

        $userIds = $badgeLogs->pluck('user_id')
            ->merge($rewardLogs->pluck('user_id'))
            ->merge($punishmentLogs->pluck('user_id'))
            ->unique();

        $perUser = $userIds->mapWithKeys(function ($userId) use ($badgeLogs, $rewardLogs, $punishmentLogs) {
            $nama = $badgeLogs->firstWhere('user_id', $userId)?->user?->nama
                ?? $rewardLogs->firstWhere('user_id', $userId)?->user?->nama
                ?? $punishmentLogs->firstWhere('user_id', $userId)?->user?->nama
                ?? '-';

            return [$userId => [
                'nama' => $nama,
                'badge' => $badgeLogs->where('user_id', $userId)->values(),
                'reward' => $rewardLogs->where('user_id', $userId)->values(),
                'punishment' => $punishmentLogs->where('user_id', $userId)->values(),
            ]];
        });

        return [
            'total_badge' => $badgeLogs->count(),
            'total_reward' => $rewardLogs->count(),
            'total_punishment' => $punishmentLogs->count(),
            'per_user' => $perUser,
        ];
    }
}
