<?php

namespace App\Services;

use App\Enums\EventTypePoint;
use App\Models\LevelBadge;
use App\Models\LevelBadgeLog;
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
        protected SertifikatService $sertifikatService,
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
     * Update level_badge_id user jika akumulasi_point masuk rentang badge
     * lain. Setiap perubahan JUGA dicatat ke LevelBadgeLog (Aturan poin 3,
     * DRY - mengikuti pola RewardLog/PunishmentLog) sebagai riwayat
     * historis, terpisah dari users.level_badge_id yang tetap jadi
     * snapshot terkini.
     *
     * BARU (gap iterasi ini): sertifikat PDF digenerate sinkron via
     * SertifikatService begitu LevelBadgeLog dibuat, SEBELUM notifikasi
     * WA dikirim, supaya link_sertifikat yang dikirim ke user sudah pasti
     * valid saat WA diterima (generate PDF dilakukan di dalam request
     * ini, bukan job async terpisah).
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

            $levelBadgeLog = LevelBadgeLog::create([
                'user_id' => $user->id,
                'level_badge_id' => $badge->id,
                'tanggal_didapat' => now(),
            ]);

            // dihitung sertifikat, gagal di-log tapi tidak menggagalkan alur
            $this->sertifikatService->generateUntukBadge($levelBadgeLog);

            // eventCode 'badge_naik' - TODO: ASUMSI, samakan dengan Setting
            // wa_template_badge_naik yang harus diisi Admin di panel WA Gateway,
            // TERMASUK placeholder {{link_sertifikat}} (BARU) di template.
            // TODO: GAP-SPEC - eventCode ini terpicu di SETIAP perubahan badge,
            // termasuk kalau badge turun (bukan hanya naik) - belum
            // dikonfirmasi apakah perlu dipisah jadi badge_naik/badge_turun.
            $this->whatsappService->kirimEvent(
                eventCode: 'badge_naik',
                nomorTujuan: $user->no_telepon,
                variables: [
                    'nama' => $user->nama,
                    'badge' => $badge->nama_badge,
                    'link_sertifikat' => route('sertifikat.badge', $levelBadgeLog),
                ],
                referenceId: "badge-{$user->id}-{$badge->id}",
            );
        }
    }

    /**
     * Cek Reward yang tercapai. KEPUTUSAN FINAL (dikonfirmasi): hanya threshold
     * TERTINGGI yang diproses per pemanggilan, bukan seluruh threshold yang
     * terlampaui sekaligus. Reward dengan threshold_point lebih rendah yang
     * belum pernah didapat TIDAK di-backfill jika user melompati beberapa
     * threshold dalam satu event - hanya akan tercatat jika suatu saat menjadi
     * satu-satunya/tertinggi yang eligible.
     *
     * BARU (gap iterasi ini): sertifikat PDF digenerate sinkron via
     * SertifikatService begitu RewardLog dibuat, sebelum notifikasi WA
     * dikirim - lihat catatan sama di cekBadge().
     */
    protected function cekReward(User $user): void
    {
        $reward = Reward::query()
            ->where('aktif', true)
            ->where('threshold_point', '<=', $user->akumulasi_point)
            ->whereDoesntHave('rewardLogs', fn($q) => $q->where('user_id', $user->id))
            ->orderByDesc('threshold_point')
            ->first();

        if (! $reward) {
            return;
        }

        $rewardLog = RewardLog::create([
            'user_id' => $user->id,
            'reward_id' => $reward->id,
            'tanggal_didapat' => now(),
        ]);

        // dihitung sertifikat, gagal di-log tapi tidak menggagalkan alur
        $this->sertifikatService->generateUntukReward($rewardLog);

        // eventCode 'reward_didapat' - TODO: ASUMSI, samakan dengan Setting
        // wa_template_reward_didapat, TERMASUK placeholder {{link_sertifikat}}
        // (BARU) di template.
        $this->whatsappService->kirimEvent(
            eventCode: 'reward_didapat',
            nomorTujuan: $user->no_telepon,
            variables: [
                'nama' => $user->nama,
                'reward' => $reward->nama,
                'link_sertifikat' => route('sertifikat.reward', $rewardLog),
            ],
            referenceId: "reward-{$rewardLog->id}",
        );
    }

    /**
     * Cek Punishment yang tercapai. KEPUTUSAN FINAL (dikonfirmasi): hanya threshold
     * TERTINGGI yang diproses per pemanggilan, bukan seluruh threshold yang
     * terlampaui sekaligus. Punishment dengan threshold_point lebih rendah yang
     * belum pernah didapat TIDAK di-backfill jika user melompati beberapa
     * threshold dalam satu event - hanya akan tercatat jika suatu saat menjadi
     * satu-satunya/tertinggi yang eligible.
     */
    protected function cekPunishment(User $user): void
    {
        $punishment = Punishment::query()
            ->where('aktif', true)
            ->where('threshold_point_minus', '>=', $user->akumulasi_point)
            ->whereDoesntHave('punishmentLogs', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where(function ($q2) {
                        $q2->whereNull('tanggal_berakhir')
                            ->orWhere('tanggal_berakhir', '>', now());
                    });
            })
            ->orderBy('threshold_point_minus')
            ->first();

        if (! $punishment) {
            return;
        }

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

    /**
     * Reverse SATU Point log (mis. saat koreksi kondisi Pengembalian
     * membatalkan alasan event tersebut). Insert entry Point BARU dengan
     * nilai negasi (bukan hapus log lama - riwayat harus auditable),
     * turunkan akumulasi_point, lalu cek ulang Badge (bisa turun level).
     *
     * TODO: GAP-SPEC - Reward/Punishment yang SUDAH terlanjur didapat dari
     * akumulasi sebelum reversal ini TIDAK ditarik kembali (termasuk
     * sertifikat yang sudah digenerate dan mungkin sudah dikirim/diunduh
     * user - TIDAK dihapus). Alasan: logic cekReward()/cekPunishment()
     * hanya memproses "threshold tertinggi yang belum pernah didapat"
     * (lihat komentar KEPUTUSAN FINAL di method tersebut) - tidak ada
     * mekanisme "un-award" yang terdefinisi di spec. Ini keputusan produk
     * yang perlu dikonfirmasi terpisah jika ternyata reward/punishment/
     * sertifikat WAJIB ikut di-reverse.
     */
    public function batalkanEvent(
        Point $pointAsli,
        ?string $keterangan = null,
    ): Point {
        return DB::transaction(function () use ($pointAsli, $keterangan) {
            $pointBalik = Point::create([
                'user_id' => $pointAsli->user_id,
                'event_type' => $pointAsli->event_type,
                'nilai' => -$pointAsli->nilai,
                'ref_type' => $pointAsli->ref_type,
                'ref_id' => $pointAsli->ref_id,
                'keterangan' => $keterangan ?? "Pembatalan otomatis dari Point #{$pointAsli->id}",
            ]);

            $user = $pointAsli->user;
            $user->increment('akumulasi_point', -$pointAsli->nilai);
            $user->refresh();

            $this->cekBadge($user);
            // Reward/Punishment sengaja tidak di-cek ulang di sini - lihat
            // TODO: GAP-SPEC di docblock method ini.

            return $pointBalik;
        });
    }

    /**
     * Cari Point log terakhir milik user untuk ref tertentu (dipakai
     * PeminjamanService::batalkanDenda untuk tahu Point mana yang harus
     * di-reverse saat koreksi kondisi). Dibatasi ke event_type spesifik
     * supaya tidak salah mengambil Point dari event lain yang kebetulan
     * punya ref_type/ref_id sama (mis. 'peminjaman'+id yang sama dipakai
     * beberapa EventTypePoint berbeda: Peminjaman, Pengembalian, Kerusakan,
     * Kehilangan).
     */
    public function cariPointTerakhir(
        int $userId,
        EventTypePoint $eventType,
        string $refType,
        string $refId,
    ): ?Point {
        return Point::query()
            ->where('user_id', $userId)
            ->where('event_type', $eventType)
            ->where('ref_type', $refType)
            ->where('ref_id', $refId)
            ->latest()
            ->first();
    }
}
