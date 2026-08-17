<?php

namespace App\Http\Controllers;

use App\Models\LevelBadgeLog;
use App\Models\RewardLog;
use Illuminate\Support\Facades\Storage;

/**
 * Akses PUBLIK tanpa login (dikonfirmasi) - link dikirim lewat notifikasi
 * WhatsApp. UUID id log sebagai identifier (tidak sekuensial/mudah
 * ditebak). Header X-Robots-Tag: noindex dipasang karena ini berisi data
 * personal per-user, mengikuti pola BukuPublikController::baca().
 *
 * TODO: GAP-SPEC - saat ini TIDAK ada mekanisme signed URL/expiring link
 * maupun otentikasi tambahan (dikonfirmasi: publik murni via UUID).
 * Siapa pun yang mendapatkan URL bisa mengakses PDF tanpa batas waktu.
 */
class SertifikatPublikController extends Controller
{
    public function reward(RewardLog $rewardLog)
    {
        abort_if(! $rewardLog->sertifikat_path, 404);

        return $this->responseFile($rewardLog->sertifikat_path);
    }

    public function badge(LevelBadgeLog $levelBadgeLog)
    {
        abort_if(! $levelBadgeLog->sertifikat_path, 404);

        return $this->responseFile($levelBadgeLog->sertifikat_path);
    }

    protected function responseFile(string $path)
    {
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path)
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
