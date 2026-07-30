<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

// TODO: verifikasi signature terhadap versi package yang terpasang (filament/filament ^5.7).
// Nama namespace parent (Filament\Auth\Pages\Login vs Filament\Pages\Auth\Login) dan
// method getCredentialsFromFormData() perlu dicek ulang terhadap source filament/filament
// versi 5.7 yang ter-install - saya tidak bisa memverifikasi langsung dari sini.
class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getLoginFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getRememberFormComponent(),
        ]);
    }

    protected function getLoginFormComponent(): TextInput
    {
        return TextInput::make('login')
            ->label('NISN / NIP / No. Telepon')
            ->required()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    /**
     * Resolusi identifier login ke kolom yang sesuai (Aturan: NISN untuk
     * Siswa, NIP untuk Pegawai/Pustakawan/Admin, atau no_telepon sebagai
     * fallback universal). TODO: GAP-SPEC - jika suatu saat NISN dan NIP
     * bisa bentrok nilai (kemungkinan kecil, belum ada constraint silang),
     * urutan pengecekan di bawah (nisn -> nip -> no_telepon) menentukan
     * prioritas resolusi.
     */
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
}
