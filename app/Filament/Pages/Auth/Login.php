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
use Illuminate\Validation\ValidationException;

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
 *
 * BUG FIX (iterasi ini) - Filament\Auth\Pages\Login::throwFailureValidationException()
 * bawaan hardcode menempelkan pesan gagal login ke key 'data.email'
 * (lihat vendor/filament/filament/src/Auth/Pages/Login.php). Form kustom
 * kita memakai field 'login' (bukan 'email'), jadi pesan tsb menempel ke
 * field yang tidak pernah dirender di schema manapun - user tidak melihat
 * apa pun saat kredensial password salah (parent::authenticate() dipanggil
 * apa adanya untuk mode 'password', lihat method authenticate() di bawah).
 * Diperbaiki dengan override method ini: target key diarahkan ke
 * 'data.login' (field yang benar-benar ada), DAN ditambahkan toast
 * eksplisit supaya konsisten dengan gaya notifikasi lain di halaman ini
 * (poin toast informatif) - inline error saja dianggap terlalu mudah
 * terlewat mengingat field password ada di bawahnya.
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
                ->extraInputAttributes(['class' => 'auth-otp-input', 'inputmode' => 'numeric'])
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
                ->icon('heroicon-o-user-circle')
                ->send();

            return;
        }

        try {
            app(LoginOtpService::class)->kirimOtp($user);
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Belum bisa mengirim OTP')
                ->body($e->getMessage())
                ->warning()
                ->icon('heroicon-o-clock')
                ->send();

            return;
        }

        $this->noTeleponOtp = $user->no_telepon;
        $this->otpTerkirim = true;

        Notification::make()
            ->title('Kode OTP terkirim')
            ->body('Cek pesan WhatsApp di nomor terdaftar, kode berlaku 5 menit.')
            ->success()
            ->icon('heroicon-o-chat-bubble-left-right')
            ->send();
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
     * BUG FIX - lihat docblock class. parent::throwFailureValidationException()
     * menempel ke 'data.email' yang tidak eksis di form kita.
     */
    protected function throwFailureValidationException(): never
    {
        Notification::make()
            ->title('Gagal masuk')
            ->body('NISN/NIP/No. Telepon atau password yang Anda masukkan salah.')
            ->danger()
            ->icon('heroicon-o-x-circle')
            ->send();

        throw ValidationException::withMessages([
            'data.login' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
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
            Notification::make()
                ->title('Gagal memverifikasi OTP')
                ->body($e->getMessage())
                ->danger()
                ->icon('heroicon-o-x-circle')
                ->send();

            return null;
        }

        Auth::login($user, $data['remember'] ?? false);
        session()->regenerate();

        return app(LoginResponse::class);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE),
            Text::make(new HtmlString(
                view('filament.partials.auth-styles')->render()
            )),
            $this->getModeSwitcherComponent(),
            $this->getFormContentComponent(),
            RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER),
            Text::make(new HtmlString(
                view('filament.partials.app-footer', ['authTop' => true])->render()
            ))
                ->extraAttributes(['class' => 'app-footer-wrapper']),
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
