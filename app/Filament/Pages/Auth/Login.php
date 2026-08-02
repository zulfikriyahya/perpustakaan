<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\LoginOtpService;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

/**
 * TODO: verifikasi signature terhadap versi package yang terpasang
 * (filament/filament ^5.7). Override content() dan getFormActions() di
 * bawah didasarkan pada pembacaan langsung source
 * vendor/filament/filament/src/Auth/Pages/Login.php yang diberikan
 * pengguna - authenticate() override TOTAL (bukan extend logic
 * rate-limit/timebox/multi-factor bawaan) tetap ASUMSI BESAR untuk cabang
 * mode 'otp': rate-limit(5), Timebox, dan multi-factor challenge bawaan
 * SEMUA di-skip untuk mode OTP. Risiko diterima sadar sesuai konfirmasi
 * (setara reset password), tapi WAJIB diuji end-to-end (poin 12).
 *
 * TODO: GAP-SPEC/BUG FIX - sebelumnya authenticate() mode OTP memakai
 * $data['login'] (raw input, bisa NISN/NIP/No. Telepon) langsung sebagai
 * no_telepon ke LoginOtpService::verifikasiUntukLogin() yang mencari
 * berdasarkan no_telepon murni - OTP selalu gagal untuk user yang login
 * pakai NISN/NIP. Diperbaiki dengan menyimpan no_telepon hasil resolve
 * di property $noTeleponOtp saat kirimOtpLogin() berhasil, dipakai ulang
 * di authenticate() - bukan raw input field lagi.
 */
class Login extends BaseLogin
{
    public string $mode = 'password';

    public bool $otpTerkirim = false;

    /**
     * no_telepon ASLI hasil resolve dari input login (NISN/NIP/No.
     * Telepon) - disimpan terpisah dari raw field 'login' karena
     * LoginOtpService bekerja murni berbasis no_telepon (sama seperti
     * pola RequestPasswordReset menyimpan no_telepon di session, disini
     * cukup property Livewire karena same-page cycle).
     */
    public ?string $noTeleponOtp = null;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getLoginFormComponent(),
            TextInput::make('password')
                ->label('Password')
                ->hint(filament()->hasPasswordReset()
                    ? new HtmlString(Blade::render(
                        '<x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="-1">Lupa kata sandi?</x-filament::link>'
                    ))
                    : null)
                ->password()
                ->revealable()
                ->required()
                ->visible(fn () => $this->mode === 'password')
                ->dehydrated(fn () => $this->mode === 'password'),
            TextInput::make('otp')
                ->label('Kode OTP')
                ->minLength(6)
                ->maxLength(6)
                ->required()
                ->visible(fn () => $this->mode === 'otp' && $this->otpTerkirim)
                ->dehydrated(fn () => $this->mode === 'otp'),
            Text::make('Kode OTP berlaku 5 menit. Tidak menerima? Klik "Password" lalu "OTP WhatsApp" lagi untuk kirim ulang.')
                ->size('xs')
                ->color('gray')
                ->visible(fn () => $this->mode === 'otp' && $this->otpTerkirim),
            $this->getRememberFormComponent(),
        ]);
    }

    protected function getLoginFormComponent(): TextInput
    {
        return TextInput::make('login')
            ->label('NISN / NIP / No. Telepon')
            ->required()
            ->autofocus()
            ->disabled(fn () => $this->mode === 'otp' && $this->otpTerkirim)
            ->extraInputAttributes(['tabindex' => 1]);
    }

    public function gantiMode(string $mode): void
    {
        $this->mode = $mode;
        $this->otpTerkirim = false;
        $this->noTeleponOtp = null;
    }

    protected function resolveUser(string $login): ?User
    {
        return User::query()
            ->where('nisn', $login)
            ->orWhere('nip', $login)
            ->orWhere('no_telepon', $login)
            ->first();
    }

    public function kirimOtpLogin(): void
    {
        $login = (string) ($this->form->getState()['login'] ?? '');
        $user = $this->resolveUser($login);

        if (! $user) {
            Notification::make()
                ->title('Akun tidak ditemukan')
                ->body('Pastikan NISN, NIP, atau No. Telepon sesuai yang terdaftar.')
                ->warning()
                ->send();

            return;
        }

        try {
            app(LoginOtpService::class)->kirimOtp($user);
        } catch (\RuntimeException $e) {
            Notification::make()->title('Belum bisa mengirim OTP')->body($e->getMessage())->warning()->send();

            return;
        }

        // simpan no_telepon ASLI hasil resolve - bukan raw input 'login',
        // supaya verifikasi OTP di authenticate() tetap benar walau user
        // login pakai NISN/NIP.
        $this->noTeleponOtp = $user->no_telepon;
        $this->otpTerkirim = true;

        Notification::make()->title('Kode OTP terkirim ke WhatsApp terdaftar.')->success()->send();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $login = $data['login'];

        $field = match (true) {
            User::query()->where('nisn', $login)->exists() => 'nisn',
            User::query()->where('nip', $login)->exists() => 'nip',
            default => 'no_telepon',
        };

        return [
            $field => $login,
            'password' => $data['password'],
        ];
    }

    /**
     * TODO: GAP-SPEC - livewireSubmitHandler pada <form> di-hardcode ke
     * 'authenticate' oleh base class Filament\Auth\Pages\Login
     * (getFormContentComponent(), tidak di-override disini) - artinya
     * menekan Enter di field manapun dalam form SELALU memanggil
     * authenticate(), terlepas tombol mana yang visible. Di mode 'otp'
     * sebelum OTP terkirim, ini didelegasikan ke kirimOtpLogin() supaya
     * Enter berperilaku sama seperti klik tombol "Kirim OTP" - BUKAN
     * menampilkan galat "kirim OTP terlebih dahulu" (UX buruk, bukan bug
     * keamanan, karena kirimOtpLogin() sendiri sudah divalidasi/rate-limited).
     */
    public function authenticate(): ?LoginResponse
    {
        if ($this->mode === 'password') {
            return parent::authenticate();
        }

        if ($this->mode === 'otp' && ! $this->otpTerkirim) {
            $this->kirimOtpLogin();

            return null;
        }

        $data = $this->form->getState();

        if (! $this->noTeleponOtp) {
            // defensif: seharusnya tidak tercapai lagi via Enter (sudah
            // ditangani cabang di atas), tapi dipertahankan untuk kasus
            // race/edge lain (mis. otpTerkirim true tapi noTeleponOtp
            // ter-reset oleh sebab tak terduga).
            Notification::make()->title('Kirim OTP terlebih dahulu.')->warning()->send();

            return null;
        }

        try {
            $user = app(LoginOtpService::class)->verifikasiUntukLogin($this->noTeleponOtp, (string) $data['otp']);
        } catch (\RuntimeException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            return null;
        }

        Auth::login($user, $data['remember'] ?? false);
        session()->regenerate();

        return app(LoginResponse::class);
    }

    /**
     * Override TOTAL dari content() bawaan - menyisipkan baris toggle mode
     * (Actions link) SEBELUM form, memakai Schema Component API asli
     * (bukan Blade custom) supaya konsisten dengan cara Filament merender
     * halaman ini. Struktur RenderHook before/after dipertahankan sama
     * seperti base class.
     */
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE),
            $this->getModeSwitcherComponent(),
            $this->getFormContentComponent(),
            RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER),
        ]);
    }

    protected function getModeSwitcherComponent(): Component
    {
        return Actions::make([
            Action::make('mode_password')
                ->label('Password')
                ->action(fn () => $this->gantiMode('password'))
                ->extraAttributes([
                    'class' => 'flex-1 !justify-center rounded-md px-3 py-1.5 text-sm font-medium transition-colors '
                        .($this->mode === 'password'
                            ? 'bg-white text-gray-950 dark:bg-gray-700 dark:text-white'
                            : 'bg-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'),
                ])
                ->color('gray'),
            Action::make('mode_otp')
                ->label('OTP WhatsApp')
                ->action(fn () => $this->gantiMode('otp'))
                ->extraAttributes([
                    'class' => 'flex-1 !justify-center rounded-md px-3 py-1.5 text-sm font-medium transition-colors '
                        .($this->mode === 'otp'
                            ? 'bg-white text-gray-950 dark:bg-gray-700 dark:text-white'
                            : 'bg-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'),
                ])
                ->color('gray'),
        ])
            ->fullWidth()
            ->extraAttributes([
                'class' => 'mb-6 gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-800/60',
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('kirim_otp')
                ->label('Kirim OTP')
                ->action('kirimOtpLogin')
                ->visible(fn () => $this->mode === 'otp' && ! $this->otpTerkirim),
            Action::make('authenticate')
                ->label(fn () => $this->mode === 'otp' ? 'Verifikasi & Masuk' : 'Masuk')
                ->submit('authenticate')
                ->visible(fn () => $this->mode === 'password' || $this->otpTerkirim),
        ];
    }
}
