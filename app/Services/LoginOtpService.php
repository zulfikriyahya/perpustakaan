<?php

namespace App\Services;

use App\Models\LoginOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class LoginOtpService
{
    public function __construct(
        protected WhatsappService $whatsappService,
    ) {}

    public function kirimOtp(User $user): void
    {
        $rateLimitKey = "otp-login:{$user->no_telepon}";

        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $detik = RateLimiter::availableIn($rateLimitKey);
            throw new \RuntimeException("Tunggu {$detik} detik sebelum meminta OTP baru.");
        }
        RateLimiter::hit($rateLimitKey, 60);

        $otp = (string) random_int(100000, 999999);

        LoginOtp::query()->where('no_telepon', $user->no_telepon)->delete();
        LoginOtp::create([
            'no_telepon' => $user->no_telepon,
            'otp' => Hash::make($otp),
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->whatsappService->kirimEvent(
            eventCode: 'login_otp',
            nomorTujuan: $user->no_telepon,
            variables: ['nama' => $user->nama, 'otp' => $otp],
            referenceId: "login-otp-{$user->id}-".now()->timestamp,
        );
    }

    /**
     * Verifikasi OTP untuk LOGIN - TIDAK menyentuh password sama sekali.
     * status_suspend TIDAK di-guard di sini (dikonfirmasi: OTP tetap
     * berlaku untuk user suspend, konsisten dengan
     * User::canAccessPanel() yang selalu true - guard sesungguhnya ada
     * di Policy per Resource, bukan di gerbang login).
     *
     * @throws \RuntimeException jika OTP salah/kedaluwarsa/user tidak ditemukan
     */
    public function verifikasiUntukLogin(string $noTelepon, string $otp): User
    {
        $record = LoginOtp::query()
            ->where('no_telepon', $noTelepon)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $record || ! Hash::check($otp, $record->otp)) {
            throw new \RuntimeException('Kode OTP salah atau sudah kedaluwarsa.');
        }

        $user = User::query()->where('no_telepon', $noTelepon)->first();

        if (! $user) {
            throw new \RuntimeException('Akun tidak ditemukan.');
        }

        $record->delete();

        return $user;
    }
}
