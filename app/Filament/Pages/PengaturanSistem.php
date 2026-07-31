<?php

namespace App\Filament\Pages;

use App\Enums\GroupSetting;
use App\Models\Setting;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * Halaman Pengaturan Sistem: form terstruktur per GroupSetting, menulis
 * ke tabel `settings` (bukan generate Resource generik). Konsisten
 * dengan pola LaporanBulanan (custom Page + Schema->statePath('data')).
 *
 * Simpan dipisah 2 method (simpanUmum / simpanDevice) - BUKAN satu
 * Action generik - supaya konfirmasi dialog hanya wajib untuk grup
 * Device (poin 17: perubahan di sini menyentuh device fisik yang
 * sudah aktif di lapangan). Dialog konfirmasi di-trigger via Alpine
 * confirm() di Blade, bukan Filament Action::requiresConfirmation(),
 * karena signature Action pada Filament 5.7 belum diverifikasi penuh
 * untuk kasus non-Resource page ini.
 *
 * TODO: verifikasi signature terhadap versi package yang terpasang -
 * komponen Tabs/Tab diasumsikan berada di Filament\Schemas\Components
 * (mengikuti pola Schema/Select yang sudah dipakai LaporanBulanan),
 * cek ulang jika filament/filament ^5.7 punya lokasi berbeda.
 */
class PengaturanSistem extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan Sistem';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';

    protected string $view = 'filament.pages.pengaturan-sistem';

    public ?array $data = [];

    /**
     * Key yang HANYA dikelola otomatis oleh sistem (UserObserver) -
     * tidak boleh masuk fillable form, ditampilkan read-only saja.
     */
    protected const KEY_READONLY = ['rfid_db_ver'];

    /**
     * Key milik grup Device yang wajib lewat konfirmasi terpisah
     * karena berdampak langsung ke device fisik aktif.
     */
    protected const KEY_DEVICE_SENSITIVE = [
        'device_sleep_start_hour',
        'device_sleep_end_hour',
        'device_oled_dim_start_hour',
        'device_oled_dim_end_hour',
        'device_sync_interval_ms',
        'device_ota_check_interval_ms',
    ];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:PengaturanSistem') ?? false;
    }

    public function getHeading(): string|HtmlString
    {
        return 'Pengaturan Sistem';
    }

    public function mount(): void
    {
        $values = Setting::query()->pluck('value', 'key');

        $this->form->fill($values->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Grup')
                ->tabs([
                    Tab::make('Peminjaman & Denda')
                        ->schema([
                            TextInput::make('max_peminjaman_aktif')
                                ->label('Maks. Peminjaman Aktif per User')
                                ->numeric()->integer()->minValue(1)->required(),
                            TextInput::make('lama_peminjaman_hari')
                                ->label('Lama Masa Pinjam (hari)')
                                ->numeric()->integer()->minValue(1)->required(),
                            TextInput::make('tarif_denda_per_hari')
                                ->label('Tarif Denda Keterlambatan / Hari (Rp)')
                                ->numeric()->minValue(0)->required(),
                            TextInput::make('persentase_denda_kerusakan')
                                ->label('Persentase Denda Kerusakan (%)')
                                ->numeric()->minValue(0)->maxValue(100)->required(),
                        ]),

                    Tab::make('Point')
                        ->schema([
                            TextInput::make('point_kunjungan')->label('Point Kunjungan')->numeric()->integer()->required(),
                            TextInput::make('point_peminjaman')->label('Point Peminjaman')->numeric()->integer()->required(),
                            TextInput::make('point_pengembalian')->label('Point Pengembalian')->numeric()->integer()->required(),
                            TextInput::make('point_kerusakan')->label('Point Kerusakan (negatif)')->numeric()->integer()->maxValue(0)->required(),
                            TextInput::make('point_kehilangan')->label('Point Kehilangan (negatif)')->numeric()->integer()->maxValue(0)->required(),
                        ]),

                    Tab::make('WhatsApp Template')
                        ->schema(
                            collect([
                                'wa_template_peminjaman_aktif' => 'Peminjaman Aktif',
                                'wa_template_reminder_h3' => 'Reminder H-3',
                                'wa_template_reminder_h1' => 'Reminder H-1',
                                'wa_template_jadi_terlambat' => 'Jadi Terlambat',
                                'wa_template_pengembalian_diproses' => 'Pengembalian Diproses',
                                'wa_template_denda_dibuat' => 'Denda Dibuat',
                                'wa_template_denda_lunas' => 'Denda Lunas',
                                'wa_template_badge_naik' => 'Badge Naik',
                                'wa_template_reward_didapat' => 'Reward Didapat',
                                'wa_template_punishment_diterapkan' => 'Punishment Diterapkan',
                                'wa_template_reset_password_otp' => 'Reset Password OTP',
                                'wa_template_koreksi_kondisi_pengembalian' => 'Koreksi Kondisi Pengembalian',
                                'wa_template_denda_dibatalkan_perlu_refund' => 'Denda Dibatalkan (Perlu Refund)',
                            ])->map(
                                fn (string $label, string $key) => TextInput::make($key)
                                    ->label($label)
                                    ->required()
                                    ->helperText('Wajib sama persis dengan template_code di panel gateway.')
                            )->values()->all()
                        ),

                    Tab::make('Device')
                        ->schema([
                            Placeholder::make('rfid_db_ver')
                                ->label('Versi Daftar Kartu RFID (otomatis)')
                                ->content(fn () => (string) Setting::get('rfid_db_ver', 0)),
                            TextInput::make('device_sleep_start_hour')
                                ->label('Jam Mulai Sleep (0-23)')
                                ->numeric()->integer()->minValue(0)->maxValue(23)->required(),
                            TextInput::make('device_sleep_end_hour')
                                ->label('Jam Bangun dari Sleep (0-23)')
                                ->numeric()->integer()->minValue(0)->maxValue(23)->required(),
                            TextInput::make('device_oled_dim_start_hour')
                                ->label('Jam OLED Dimatikan Sementara (0-23)')
                                ->numeric()->integer()->minValue(0)->maxValue(23)->required(),
                            TextInput::make('device_oled_dim_end_hour')
                                ->label('Jam OLED Menyala Kembali (0-23)')
                                ->numeric()->integer()->minValue(0)->maxValue(23)->required(),
                            TextInput::make('device_sync_interval_ms')
                                ->label('Interval Sinkronisasi (ms)')
                                ->numeric()->integer()->minValue(1000)->required()
                                ->helperText('Minimum 1000ms - nilai terlalu kecil membebani device & jaringan.'),
                            TextInput::make('device_ota_check_interval_ms')
                                ->label('Interval Cek Firmware OTA (ms)')
                                ->numeric()->integer()->minValue(1000)->required(),
                        ]),
                ]),
        ])->statePath('data');
    }

    /**
     * Simpan grup Peminjaman, Denda, Point, WhatsApp - tanpa konfirmasi,
     * tidak menyentuh kontrak device.
     */
    public function simpanUmum(): void
    {
        $state = $this->form->getState();

        $keys = array_diff(
            array_keys($state),
            self::KEY_DEVICE_SENSITIVE,
            self::KEY_READONLY,
        );

        $this->simpanKeys($state, $keys);

        Notification::make()->success()->title('Pengaturan umum disimpan.')->send();
    }

    /**
     * Simpan grup Device - dipanggil setelah konfirmasi Alpine di Blade.
     * TODO: GAP-SPEC - perubahan di sini baru dipakai device pada siklus
     * fetch config berikutnya (lihat PerpustakaanDeviceController), TIDAK
     * ada mekanisme push aktif ke device yang sedang online. Jika perlu
     * push real-time, perlu keputusan desain tambahan (mis. queue command
     * ke device via endpoint lain) - belum diimplementasikan.
     */
    public function simpanDevice(): void
    {
        $state = $this->form->getState();

        $this->simpanKeys($state, self::KEY_DEVICE_SENSITIVE);

        Notification::make()
            ->warning()
            ->title('Pengaturan device disimpan.')
            ->body('Device akan memakai nilai baru pada sinkronisasi berikutnya, bukan seketika.')
            ->send();
    }

    protected function simpanKeys(array $state, array $keys): void
    {
        $groupMap = [
            'max_peminjaman_aktif' => GroupSetting::Peminjaman,
            'lama_peminjaman_hari' => GroupSetting::Peminjaman,
            'tarif_denda_per_hari' => GroupSetting::Denda,
            'persentase_denda_kerusakan' => GroupSetting::Denda,
            'point_kunjungan' => GroupSetting::Point,
            'point_peminjaman' => GroupSetting::Point,
            'point_pengembalian' => GroupSetting::Point,
            'point_kerusakan' => GroupSetting::Point,
            'point_kehilangan' => GroupSetting::Point,
            'device_sleep_start_hour' => GroupSetting::Device,
            'device_sleep_end_hour' => GroupSetting::Device,
            'device_oled_dim_start_hour' => GroupSetting::Device,
            'device_oled_dim_end_hour' => GroupSetting::Device,
            'device_sync_interval_ms' => GroupSetting::Device,
            'device_ota_check_interval_ms' => GroupSetting::Device,
        ];

        foreach ($keys as $key) {
            if (in_array($key, self::KEY_READONLY, true) || ! array_key_exists($key, $state)) {
                continue;
            }

            $group = $groupMap[$key] ?? GroupSetting::Whatsapp; // sisanya wa_template_*

            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => (string) $state[$key], 'group' => $group],
            );
        }
    }
}
