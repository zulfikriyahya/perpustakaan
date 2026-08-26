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
            Notification::make()
                ->title('Akun tidak ditemukan')
                ->body('Pastikan NISN, NIP, atau No. Telepon yang dimasukkan sesuai dengan yang terdaftar di sistem perpustakaan.')
                ->warning()
                ->icon('heroicon-o-user-circle')
                ->send();

            return;
        }

        try {
            $otpService->kirimOtp($user);
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Belum bisa mengirim OTP')
                ->body($e->getMessage())
                ->warning()
                ->icon('heroicon-o-clock')
                ->send();

            return;
        }

        Session::put('reset_password_no_telepon', $user->no_telepon);

        // FITUR BARU - sebelumnya redirect diam-diam tanpa toast, user
        // bisa bingung apakah OTP benar-benar terkirim sebelum landing
        // di halaman berikutnya.
        Notification::make()
            ->title('Kode OTP terkirim')
            ->body('Cek pesan WhatsApp di nomor terdaftar, lalu masukkan kodenya di halaman berikutnya.')
            ->success()
            ->icon('heroicon-o-chat-bubble-left-right')
            ->send();

        $this->redirect(
            URL::signedRoute('filament.dashboard.auth.password-reset.reset')
        );
    }
}
