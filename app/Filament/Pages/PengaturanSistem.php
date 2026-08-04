<?php

namespace App\Filament\Pages;

use App\Enums\GroupSetting;
use App\Models\Setting;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * Halaman Pengaturan Sistem: form terstruktur per GroupSetting, menulis
 * ke tabel `settings` (bukan generate Resource generik).
 *
 * ITERASI INI: tab baru "Kredensial Sensitif" memindahkan WHATSAPP_GATEWAY_*
 * dan DEVICE_GATEWAY_API_KEY dari .env ke Setting (dienkripsi via
 * Setting::setEncrypted() untuk field secret). Method simpan DIPISAH lagi
 * (simpanKredensial(), bukan gabung ke simpanUmum()) dengan dialog
 * konfirmasi Alpine sendiri - sama seperti Device, karena:
 * - Ganti DEVICE_GATEWAY_API_KEY di sini TIDAK mengubah key di firmware
 *   ESP32 yang sudah terpasang - device lama akan langsung gagal
 *   autentikasi (401) sampai direconfigure manual via provisioning mode.
 * - Ganti WhatsApp secret/api_key_id yang tidak cocok dengan panel
 *   gateway zedlabs membuat SELURUH notifikasi WA gagal (HMAC signature
 *   mismatch di WhatsappService::kirimRequest()).
 *
 * Fallback: jika Setting kosong (mis. baru migrate, belum sempat diisi
 * Admin), WhatsappService & AuthenticateDeviceApiKey fallback membaca
 * config()/.env seperti sebelumnya (lihat masing-masing file) - jadi
 * .env TIDAK dihapus, hanya jadi fallback (dikonfirmasi user).
 *
 * TODO: verifikasi signature terhadap versi package yang terpasang -
 * komponen Tabs/Tab/Grid diasumsikan berada di Filament\Schemas\Components
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

    /**
     * Key milik grup Kredensial - kontrak keamanan mengikat WhatsApp
     * Gateway & Device Gateway RFID (Aturan poin 17), wajib konfirmasi
     * terpisah dari grup lain.
     */
    protected const KEY_KREDENSIAL_SENSITIVE = [
        'whatsapp_gateway_base_url',
        'whatsapp_gateway_api_key_id',
        'whatsapp_gateway_secret',
        'whatsapp_gateway_timeout',
        'device_gateway_api_key',
    ];

    /**
     * Subset dari KEY_KREDENSIAL_SENSITIVE yang benar-benar dienkripsi
     * (Setting::setEncrypted()) - base_url & timeout bukan secret,
     * disimpan plaintext seperti Setting lain agar tidak menambah
     * overhead enkripsi percuma.
     */
    protected const KEY_KREDENSIAL_TERENKRIPSI = [
        'whatsapp_gateway_api_key_id',
        'whatsapp_gateway_secret',
        'device_gateway_api_key',
    ];

    protected const GRID_KOLOM_STANDAR = [
        'default' => 1,
        'sm' => 2,
        'lg' => 3,
        'xl' => 4,
    ];

    protected const GRID_KOLOM_PADAT = [
        'default' => 1,
        'sm' => 2,
        'lg' => 3,
    ];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:PengaturanSistem') ?? false;
    }

    public function getHeading(): string|HtmlString
    {
        return 'Pengaturan Sistem';
    }

    /**
     * Nilai key Kredensial-terenkripsi didekripsi transparan oleh
     * Setting::get() (dipanggil lewat query pluck di bawah -> TIDAK,
     * pluck() langsung dari DB TIDAK mendekripsi). Jadi untuk key
     * terenkripsi, isi form manual lewat Setting::get() per key supaya
     * value yang tampil di TextInput::password() sudah plaintext
     * (bukan ciphertext mentah).
     */
    public function mount(): void
    {
        $values = Setting::query()->pluck('value', 'key')->toArray();

        foreach (self::KEY_KREDENSIAL_TERENKRIPSI as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = Setting::get($key);
            }
        }

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Grup')
                ->tabs([
                    Tab::make('Peminjaman & Denda')
                        ->schema([
                            Grid::make(self::GRID_KOLOM_STANDAR)
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
                        ]),

                    Tab::make('Point')
                        ->schema([
                            Grid::make(self::GRID_KOLOM_STANDAR)
                                ->schema([
                                    TextInput::make('point_kunjungan')->label('Point Kunjungan')->numeric()->integer()->required(),
                                    TextInput::make('point_peminjaman')->label('Point Peminjaman')->numeric()->integer()->required(),
                                    TextInput::make('point_pengembalian')->label('Point Pengembalian')->numeric()->integer()->required(),
                                    TextInput::make('point_kerusakan')->label('Point Kerusakan (negatif)')->numeric()->integer()->maxValue(0)->required(),
                                    TextInput::make('point_kehilangan')->label('Point Kehilangan (negatif)')->numeric()->integer()->maxValue(0)->required(),
                                ]),
                        ]),

                    Tab::make('WhatsApp Template')
                        ->schema([
                            Grid::make(self::GRID_KOLOM_PADAT)
                                ->schema(
                                    collect([
                                        'wa_template_peminjaman_aktif' => 'Peminjaman Aktif',
                                        'wa_template_reminder_h3' => 'Reminder H-3',
                                        'wa_template_reminder_h1' => 'Reminder H-1',
                                        'wa_template_jadi_terlambat' => 'Jadi Terlambat',
                                        'wa_template_pengembalian_diproses' => 'Pengembalian Diproses',
                                        'wa_template_kunjungan_tercatat' => 'Kunjungan Tercatat',
                                        'wa_template_denda_dibuat' => 'Denda Dibuat',
                                        'wa_template_denda_lunas' => 'Denda Lunas',
                                        'wa_template_badge_naik' => 'Badge Naik',
                                        'wa_template_reward_didapat' => 'Reward Didapat',
                                        'wa_template_punishment_diterapkan' => 'Punishment Diterapkan',
                                        'wa_template_reset_password_otp' => 'Reset Password OTP',
                                        'wa_template_login_otp' => 'Login OTP',
                                        'wa_template_koreksi_kondisi_pengembalian' => 'Koreksi Kondisi Pengembalian',
                                        'wa_template_denda_dibatalkan_perlu_refund' => 'Denda Dibatalkan (Perlu Refund)',
                                        'wa_template_buku_ditemukan_kembali' => 'Buku Ditemukan Kembali',
                                    ])->map(
                                        fn(string $label, string $key) => TextInput::make($key)
                                            ->label($label)
                                            ->required()
                                            ->helperText('Wajib sama persis dengan template_code di panel gateway.')
                                    )->values()->all()
                                ),
                        ]),

                    Tab::make('Device')
                        ->schema([
                            Placeholder::make('rfid_db_ver')
                                ->label('Versi Daftar Kartu RFID (otomatis)')
                                ->content(fn() => (string) Setting::get('rfid_db_ver', 0))
                                ->columnSpanFull(),
                            Grid::make(self::GRID_KOLOM_STANDAR)
                                ->schema([
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

                    Tab::make('Kredensial Sensitif')
                        ->schema([
                            Placeholder::make('kredensial_info')
                                ->label('')
                                ->content('Field bertanda kunci disimpan terenkripsi di database. Mengubah nilai di sini TIDAK otomatis mengubah konfigurasi di panel gateway WhatsApp atau firmware device RFID yang sudah terpasang - pastikan nilai baru sudah sinkron di kedua sisi sebelum menyimpan.')
                                ->columnSpanFull(),
                            Grid::make(self::GRID_KOLOM_PADAT)
                                ->schema([
                                    TextInput::make('whatsapp_gateway_base_url')
                                        ->label('WhatsApp Gateway Base URL')
                                        ->url()->required(),
                                    TextInput::make('whatsapp_gateway_timeout')
                                        ->label('WhatsApp Gateway Timeout (detik)')
                                        ->numeric()->integer()->minValue(1)->required(),
                                    TextInput::make('whatsapp_gateway_api_key_id')
                                        ->label('WhatsApp Gateway API Key ID')
                                        ->password()->revealable()->required()
                                        ->helperText('Terenkripsi di database.'),
                                    TextInput::make('whatsapp_gateway_secret')
                                        ->label('WhatsApp Gateway Secret (HMAC)')
                                        ->password()->revealable()->required()
                                        ->helperText('Terenkripsi di database. Harus sama persis dengan panel gateway zedlabs.'),
                                    TextInput::make('device_gateway_api_key')
                                        ->label('Device Gateway API Key (ESP32)')
                                        ->password()->revealable()->required()
                                        ->helperText('Terenkripsi di database. Ganti nilai ini TIDAK mengubah key di firmware yang sudah terpasang.'),
                                ]),
                        ]),
                ]),
        ])->statePath('data');
    }

    /**
     * Simpan grup Peminjaman, Denda, Point, WhatsApp - tanpa konfirmasi,
     * tidak menyentuh kontrak device/kredensial.
     */
    public function simpanUmum(): void
    {
        $state = $this->form->getState();

        $keys = array_diff(
            array_keys($state),
            self::KEY_DEVICE_SENSITIVE,
            self::KEY_KREDENSIAL_SENSITIVE,
            self::KEY_READONLY,
        );

        $this->simpanKeys($state, $keys);

        Notification::make()->success()->title('Pengaturan umum disimpan.')->send();
    }

    /**
     * Simpan grup Device - dipanggil setelah konfirmasi Alpine di Blade.
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

    /**
     * Simpan grup Kredensial - dipanggil setelah konfirmasi Alpine di
     * Blade. Field dalam KEY_KREDENSIAL_TERENKRIPSI disimpan via
     * Setting::setEncrypted(); sisanya (base_url, timeout) via jalur
     * biasa (simpanKeys) karena bukan secret.
     */
    public function simpanKredensial(): void
    {
        $state = $this->form->getState();

        foreach (self::KEY_KREDENSIAL_TERENKRIPSI as $key) {
            if (! array_key_exists($key, $state) || $state[$key] === null || $state[$key] === '') {
                continue;
            }

            Setting::setEncrypted($key, (string) $state[$key], GroupSetting::Kredensial);
        }

        $keysPlaintext = array_diff(self::KEY_KREDENSIAL_SENSITIVE, self::KEY_KREDENSIAL_TERENKRIPSI);
        $this->simpanKeys($state, $keysPlaintext);

        Notification::make()
            ->danger()
            ->title('Kredensial sensitif disimpan.')
            ->body('Pastikan nilai baru sudah disesuaikan juga di panel gateway WhatsApp dan/atau firmware device, jika tidak notifikasi WA/autentikasi device akan gagal.')
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
            'whatsapp_gateway_base_url' => GroupSetting::Kredensial,
            'whatsapp_gateway_timeout' => GroupSetting::Kredensial,
        ];

        foreach ($keys as $key) {
            if (in_array($key, self::KEY_READONLY, true) || ! array_key_exists($key, $state)) {
                continue;
            }

            $group = $groupMap[$key] ?? GroupSetting::Whatsapp; // sisanya wa_template_*

            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => (string) $state[$key], 'group' => $group, 'is_encrypted' => false],
            );
        }
    }
}
