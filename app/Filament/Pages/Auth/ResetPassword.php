<?php

namespace App\Filament\Pages\Auth;

use App\Services\PasswordResetOtpService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\HtmlString;

/**
 * Langkah 2: user masukkan OTP yang diterima di WhatsApp + password baru.
 * no_telepon diambil dari session yang di-set RequestPasswordReset - jika
 * session kosong (mis. buka langsung URL ini tanpa lewat step 1), tendang
 * balik ke RequestPasswordReset.
 */
class ResetPassword extends SimplePage
{
    protected string $view = 'filament.pages.auth.reset-password';

    public ?array $data = [];

    public function getHeading(): string|HtmlString
    {
        return 'Reset Kata Sandi';
    }

    public function getSubheading(): string|HtmlString|null
    {
        return 'Masukkan kode OTP yang dikirim ke WhatsApp-mu.';
    }

    public function mount(): void
    {
        if (! Session::has('reset_password_no_telepon')) {
            $this->redirect(route('filament.dashboard.auth.password-reset.request'));
            return;
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('otp')
                ->label('Kode OTP')
                ->required()
                ->length(6),
            TextInput::make('password')
                ->label('Password Baru')
                ->password()
                ->required()
                ->minLength(8),
            TextInput::make('password_confirmation')
                ->label('Konfirmasi Password Baru')
                ->password()
                ->required()
                ->same('password'),
        ])->statePath('data');
    }

    public function prosesReset(PasswordResetOtpService $otpService): void
    {
        $data = $this->form->getState();
        $noTelepon = Session::get('reset_password_no_telepon');

        try {
            $otpService->verifikasiDanReset($noTelepon, $data['otp'], $data['password']);
        } catch (\RuntimeException $e) {
            Notification::make()->title($e->getMessage())->warning()->send();
            return;
        }

        Session::forget('reset_password_no_telepon');

        Notification::make()->title('Password berhasil direset, silakan login.')->success()->send();

        $this->redirect(route('filament.dashboard.auth.login'));
    }
}
