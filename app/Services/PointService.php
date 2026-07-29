<?php

namespace App\Services;

use App\Enums\EventTypePoint;
use App\Models\LevelBadge;
use App\Models\Point;
use App\Models\Punishment;
use App\Models\PunishmentLog;
use App\Models\Reward;
use App\Models\RewardLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PointService
{
    public function __construct(
        protected WhatsappService $whatsappService,
    ) {}

    /**
     * Catat event Point untuk user, lalu jalankan seluruh alur otomatis:
     * update akumulasi -> cek Badge -> cek Reward -> cek Punishment.
     *
     * $refType/$refId: polymorphic manual (bukan Eloquent morph), misal
     * 'peminjaman' + $peminjaman->id.
     */
    public function catatEvent(
        User $user,
        EventTypePoint $eventType,
        ?string $refType = null,
        ?string $refId = null,
        ?string $keterangan = null,
    ): Point {
        // TODO: ASUMSI - key Setting mengikuti pola 'point_{event_type}', mis.
        // 'point_kunjungan', 'point_peminjaman', 'point_kerusakan' (boleh negatif).
        // Spec tidak menyebutkan nama key pasti.
        $nilai = (int) Setting::get("point_{$eventType->value}", 0);

        return DB::transaction(function () use ($user, $eventType, $nilai, $refType, $refId, $keterangan) {
            $point = Point::create([
                'user_id' => $user->id,
                'event_type' => $eventType,
                'nilai' => $nilai,
                'ref_type' => $refType,
                'ref_id' => $refId,
                'keterangan' => $keterangan,
            ]);

            $user->increment('akumulasi_point', $nilai);
            $user->refresh();

            $this->cekBadge($user);
            $this->cekReward($user);
            $this->cekPunishment($user);

            return $point;
        });
    }

    /**
     * Update level_badge_id user jika akumulasi_point masuk rentang badge lain.
     */
    protected function cekBadge(User $user): void
    {
        $badge = LevelBadge::query()
            ->where('min_point', '<=', $user->akumulasi_point)
            ->where(function ($q) use ($user) {
                $q->whereNull('max_point')
                    ->orWhere('max_point', '>=', $user->akumulasi_point);
            })
            ->orderByDesc('urutan')
            ->first();

        if ($badge && $badge->id !== $user->level_badge_id) {
            $user->update(['level_badge_id' => $badge->id]);

            // eventCode 'badge_naik' - TODO: ASUMSI, samakan dengan Setting
            // wa_template_badge_naik yang harus diisi Admin di panel WA Gateway.
            $this->whatsappService->kirimEvent(
                eventCode: 'badge_naik',
                nomorTujuan: $user->no_telepon,
                variables: ['nama' => $user->nama, 'badge' => $badge->nama_badge],
                referenceId: "badge-{$user->id}-{$badge->id}",
            );
        }
    }

    /**
     * Cek apakah user baru saja melewati threshold Reward yang belum pernah didapat.
     */
    protected function cekReward(User $user): void
    {
        $rewardTercapai = Reward::query()
            ->where('aktif', true)
            ->where('threshold_point', '<=', $user->akumulasi_point)
            ->whereDoesntHave('rewardLogs', fn($q) => $q->where('user_id', $user->id))
            ->get();

        foreach ($rewardTercapai as $reward) {
            $rewardLog = RewardLog::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'tanggal_didapat' => now(),
            ]);

            // eventCode 'reward_didapat' - TODO: ASUMSI, samakan dengan Setting
            // wa_template_reward_didapat.
            $this->whatsappService->kirimEvent(
                eventCode: 'reward_didapat',
                nomorTujuan: $user->no_telepon,
                variables: ['nama' => $user->nama, 'reward' => $reward->nama],
                referenceId: "reward-{$rewardLog->id}",
            );
        }
    }

    /**
     * Cek apakah user baru saja melewati threshold Punishment (point minus).
     * TODO: GAP-SPEC - overlap status_suspend dengan Denda. Suspend dari Punishment
     * TIDAK ditandai lunas/tidak seperti Denda, melainkan berdasarkan tanggal_berakhir.
     * DendaObserver sudah disesuaikan untuk ikut mengecek PunishmentLog aktif sebelum
     * unsuspend user (lihat app/Observers/DendaObserver.php).
     */
    protected function cekPunishment(User $user): void
    {
        $punishmentTercapai = Punishment::query()
            ->where('aktif', true)
            ->where('threshold_point_minus', '>=', $user->akumulasi_point)
            ->whereDoesntHave('punishmentLogs', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where(function ($q2) {
                        $q2->whereNull('tanggal_berakhir')
                            ->orWhere('tanggal_berakhir', '>', now());
                    });
            })
            ->get();

        foreach ($punishmentTercapai as $punishment) {
            $punishmentLog = PunishmentLog::create([
                'user_id' => $user->id,
                'punishment_id' => $punishment->id,
                'tanggal_diterapkan' => now(),
                'tanggal_berakhir' => $punishment->durasi_suspend_hari
                    ? now()->addDays($punishment->durasi_suspend_hari)
                    : null,
            ]);

            $user->update(['status_suspend' => true]);

            // eventCode 'punishment_diterapkan' - TODO: ASUMSI, samakan dengan
            // Setting wa_template_punishment_diterapkan.
            $this->whatsappService->kirimEvent(
                eventCode: 'punishment_diterapkan',
                nomorTujuan: $user->no_telepon,
                variables: ['nama' => $user->nama, 'alasan' => $punishment->nama],
                referenceId: "punishment-{$punishmentLog->id}",
            );
        }
    }
}
