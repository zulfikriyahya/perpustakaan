<?php

namespace App\Http\Controllers;

use App\Models\LevelBadgeLog;
use App\Models\RewardLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Akses PUBLIK tanpa login (dikonfirmasi) - link dikirim lewat notifikasi
 * WhatsApp. UUID id log sebagai identifier (tidak sekuensial/mudah
 * ditebak). Header X-Robots-Tag: noindex dipasang karena ini berisidata
 * personal per-user, mengikuti pola BukuPublikController::baca().
 *
 * TODO: GAP-SPEC - saat ini TIDAK ada mekanisme signed URL/expiringlink
 * maupun otentikasi tambahan (dikonfirmasi: publik murni via UUID).
 * Siapa pun yang mendapatkan URL bisa mengakses PDF tanpa batas waktu.
 */
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

    // dibentuk dari nama item + nama penerima, fallback 'sertifikat' jika kosong
    protected function buatNamaFile(string $tipe, ?string $namaItem, ?string $namaPenerima): string
    {
        $bagian = collect(['sertifikat', $tipe, $namaItem, $namaPenerima])
            ->filter()
            ->map(fn(string $s) => Str::slug($s))
            ->filter();

        return ($bagian->isNotEmpty() ? $bagian->implode('-') : 'sertifikat') . '.pdf';
    }

    protected function responseFile(string $path, string $namaFile)
    {
        abort_unless(Storage::disk('public')->exists($path), 404);

        // BUGFIX (sebelumnya): Storage::disk()->response() mengembalikan
        // Symfony\Component\HttpFoundation\StreamedResponse, yang TIDAK
        // punya method header() (itu method Illuminate\Http\Response,
        // bukan bawaan Symfony) - dulu memicu Error 500 "Call to undefined
        // method ... header()" setiap link sertifikat diakses. Diperbaiki
        // dengan mengakses properti HeaderBag $response->headers secara
        // langsung, yang tersedia di SEMUA turunan Response Symfony,
        // termasuk StreamedResponse.
        //
        // $namaFile diteruskan sebagai parameter kedua Storage::response()
        // agar Content-Disposition memakai nama manusiawi, bukan UUID
        // mentah dari nama file fisik di disk.
        $response = Storage::disk('public')->response($path, $namaFile);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
