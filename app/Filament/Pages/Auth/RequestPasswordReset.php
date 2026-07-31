<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\PasswordResetOtpService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

/**
 * Langkah 1: minta identifier (NISN/NIP/No. Telepon - sama seperti Login,
 * lihat App\Filament\Pages\Auth\Login), kirim OTP via WhatsApp ke
 * User.no_telepon, lalu redirect ke ResetPassword page. no_telepon ASLI
 * (bukan raw input) disimpan di session, supaya PasswordResetOtpService
 * (yang bekerja berbasis no_telepon) tetap konsisten walau user login
 * pakai NISN/NIP.
 */
class RequestPasswordReset extends SimplePage
{
    protected string $view = 'filament.pages.auth.request-password-reset';

    public ?array $data = [];

    public function getHeading(): string|HtmlString
    {
        return 'Lupa Kata Sandi';
    }

    public function getSubheading(): string|HtmlString|null
    {
        return 'Masukkan NISN, NIP, atau No. Telepon yang terdaftar - kode OTP akan dikirim via WhatsApp.';
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('login')
                ->label('NISN / NIP / No. Telepon')
                ->required(),
        ])->statePath('data');
    }

    /**
     * Resolusi identifier ke User - pola SAMA persis dengan
     * Login::getCredentialsFromFormData() (Aturan poin 3, DRY). Jika suatu
     * saat logic resolusi berubah (mis. tambah identifier baru), kedua
     * tempat ini wajib disinkronkan bersamaan (poin 11).
     */
    protected function resolveUser(string $login): ?User
    {
        return User::query()
            ->where('nisn', $login)
            ->orWhere('nip', $login)
            ->orWhere('no_telepon', $login)
            ->first();
    }

    public function kirim(PasswordResetOtpService $otpService): void
    {
        $data = $this->form->getState();

        $user = $this->resolveUser($data['login']);

        if (! $user) {
            // TODO: GAP-SPEC - notifikasi eksplisit "tidak terdaftar" atas
            // permintaan (trade-off: bisa dipakai enumerasi identifier
            // terdaftar). Lihat catatan sebelumnya jika ingin direvert ke
            // pesan generik.
            Notification::make()
                ->title('Akun tidak ditemukan')
                ->body('Pastikan NISN, NIP, atau No. Telepon yang dimasukkan sesuai dengan yang terdaftar di sistem perpustakaan.')
                ->warning()
                ->send();

            return;
        }

        try {
            $otpService->kirimOtp($user);
        } catch (\RuntimeException $e) {
            // rate limit OTP (lihat PasswordResetOtpService::kirimOtp) -
            // ditangkap disini, bukan dibiarkan jadi fatal error.
            Notification::make()
                ->title('Belum bisa mengirim OTP')
                ->body($e->getMessage())
                ->warning()
                ->send();

            return;
        }

        Session::put('reset_password_no_telepon', $user->no_telepon);

        $this->redirect(
            URL::signedRoute('filament.dashboard.auth.password-reset.reset')
        );
    }
}
