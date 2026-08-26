<?php

namespace App\Http\Controllers;

use App\Models\LevelBadgeLog;
use App\Models\RewardLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SertifikatPublikController extends Controller
{
    public function reward(RewardLog $rewardLog)
    {
        abort_if(! $rewardLog->sertifikat_path, 404);

        $rewardLog->loadMissing(['user', 'reward']);

        $nama = $this->buatNamaFile('reward', $rewardLog->reward?->nama, $rewardLog->user?->nama);

        return $this->responseFile($rewardLog->sertifikat_path, $nama);
    }

    public function badge(LevelBadgeLog $levelBadgeLog)
    {
        abort_if(! $levelBadgeLog->sertifikat_path, 404);

        $levelBadgeLog->loadMissing(['user', 'levelBadge']);

        $nama = $this->buatNamaFile('badge', $levelBadgeLog->levelBadge?->nama_badge, $levelBadgeLog->user?->nama);

        return $this->responseFile($levelBadgeLog->sertifikat_path, $nama);
    }

    protected function buatNamaFile(string $tipe, ?string $namaItem, ?string $namaPenerima): string
    {
        $bagian = collect(['sertifikat', $tipe, $namaItem, $namaPenerima])
            ->filter()
            ->map(fn (string $s) => Str::slug($s))
            ->filter();

        return ($bagian->isNotEmpty() ? $bagian->implode('-') : 'sertifikat').'.pdf';
    }

    protected function responseFile(string $path, string $namaFile)
    {
        abort_unless(Storage::disk('public')->exists($path), 404);
        $response = Storage::disk('public')->response($path, $namaFile);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
