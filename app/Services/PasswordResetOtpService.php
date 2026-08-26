<?php

namespace App\Services;

use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class PasswordResetOtpService
{
    public function __construct(
        protected WhatsappService $whatsappService,
    ) {}

    public function kirimOtp(User $user): void
    {
        $rateLimitKey = "otp-reset:{$user->no_telepon}";

        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $detik = RateLimiter::availableIn($rateLimitKey);
            throw new \RuntimeException("Tunggu {$detik} detik sebelum meminta OTP baru.");
        }
        RateLimiter::hit($rateLimitKey, 60);

        $otp = (string) random_int(100000, 999999);

        PasswordResetOtp::query()->where('no_telepon', $user->no_telepon)->delete();
        PasswordResetOtp::create([
            'no_telepon' => $user->no_telepon,
            'otp' => Hash::make($otp),
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->whatsappService->kirimEvent(
            eventCode: 'reset_password_otp',
            nomorTujuan: $user->no_telepon,
            variables: ['nama' => $user->nama, 'otp' => $otp],
            referenceId: "reset-otp-{$user->id}-".now()->timestamp,
        );
    }

    /**
     * @throws \RuntimeException jika OTP salah/kedaluwarsa/tidak ada permintaan
     */
    public function verifikasiDanReset(string $noTelepon, string $otp, string $passwordBaru): void
    {
        $record = PasswordResetOtp::query()
            ->where('no_telepon', $noTelepon)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $record || ! Hash::check($otp, $record->otp)) {
            throw new \RuntimeException('Kode OTP salah atau sudah kedaluwarsa.');
        }

        $user = User::query()->where('no_telepon', $noTelepon)->firstOrFail();
        $user->update(['password' => Hash::make($passwordBaru)]);

        $record->delete();
    }
}
