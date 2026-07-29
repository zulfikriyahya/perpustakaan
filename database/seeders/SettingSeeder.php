<?php

namespace Database\Seeders;

use App\Enums\GroupSetting;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Baseline Setting agar aplikasi tidak diam-diam berjalan dengan default
 * hardcode di kode (Setting::get($key, $default)). Nilai berkategori
 * "bisnis" (bukan teknis/device) ditandai TODO: ASUMSI - wajib direview
 * Admin lewat panel sebelum dianggap final, terutama nilai Point yang
 * menentukan kecepatan naik Badge dan pemicu Punishment.
 *
 * SENGAJA TIDAK menyeed wa_template_* - template_code terkait belum dibuat
 * di panel WhatsApp Gateway (dok kontrak API §4.2). Sampai template dibuat
 * manual dan key ini diisi, WhatsappService::kirimEvent() akan skip dengan
 * Log::warning (by design), notifikasi WA tidak terkirim.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // --- Kategori A: teknis/device - konsisten dengan default firmware ESP32 ---
            ['key' => 'rfid_db_ver', 'value' => '0', 'group' => GroupSetting::Device, 'keterangan' => 'Versi daftar kartu RFID aktif, dinaikkan otomatis oleh UserObserver.'],
            ['key' => 'device_sleep_start_hour', 'value' => '18', 'group' => GroupSetting::Device, 'keterangan' => 'Jam mulai device deep sleep (0-23).'],
            ['key' => 'device_sleep_end_hour', 'value' => '5', 'group' => GroupSetting::Device, 'keterangan' => 'Jam device bangun dari deep sleep (0-23).'],
            ['key' => 'device_oled_dim_start_hour', 'value' => '8', 'group' => GroupSetting::Device, 'keterangan' => 'Jam mulai OLED device dimatikan sementara (0-23).'],
            ['key' => 'device_oled_dim_end_hour', 'value' => '12', 'group' => GroupSetting::Device, 'keterangan' => 'Jam OLED device kembali menyala (0-23).'],
            ['key' => 'device_sync_interval_ms', 'value' => '300000', 'group' => GroupSetting::Device, 'keterangan' => 'Interval sinkronisasi data offline device ke server (ms).'],
            ['key' => 'device_ota_check_interval_ms', 'value' => '30000', 'group' => GroupSetting::Device, 'keterangan' => 'Interval device mengecek update firmware (ms).'],

            // --- Kategori B.1: aturan peminjaman & denda ---
            // TODO: ASUMSI - baseline dari default fallback di PeminjamanService, wajib direview Admin.
            ['key' => 'max_peminjaman_aktif', 'value' => '3', 'group' => GroupSetting::Peminjaman, 'keterangan' => 'TODO: ASUMSI - maksimal jumlah Peminjaman berstatus aktif per user.'],
            ['key' => 'lama_peminjaman_hari', 'value' => '7', 'group' => GroupSetting::Peminjaman, 'keterangan' => 'TODO: ASUMSI - masa pinjam dalam hari sejak tanggal_pinjam.'],
            ['key' => 'tarif_denda_per_hari', 'value' => '500', 'group' => GroupSetting::Denda, 'keterangan' => 'TODO: ASUMSI - tarif denda keterlambatan per hari (rupiah).'],
            ['key' => 'persentase_denda_kerusakan', 'value' => '100', 'group' => GroupSetting::Denda, 'keterangan' => 'TODO: ASUMSI - persentase dari Buku.harga_ganti untuk denda kerusakan.'],

            // --- Kategori B.2: nilai Point per event ---
            // TODO: ASUMSI - angka belum ditentukan spec, dipilih sebagai baseline awal
            // supaya sistem Badge/Reward/Punishment tidak mati total (default kode = 0).
            // Kerusakan/Kehilangan sengaja negatif sesuai Logic Module §4.
            ['key' => 'point_kunjungan', 'value' => '1', 'group' => GroupSetting::Point, 'keterangan' => 'TODO: ASUMSI - point per kunjungan (tap RFID).'],
            ['key' => 'point_peminjaman', 'value' => '2', 'group' => GroupSetting::Point, 'keterangan' => 'TODO: ASUMSI - point per buku dipinjam.'],
            ['key' => 'point_pengembalian', 'value' => '3', 'group' => GroupSetting::Point, 'keterangan' => 'TODO: ASUMSI - point per pengembalian kondisi baik/tepat waktu.'],
            ['key' => 'point_kerusakan', 'value' => '-10', 'group' => GroupSetting::Point, 'keterangan' => 'TODO: ASUMSI - point (negatif) saat buku dikembalikan rusak.'],
            ['key' => 'point_kehilangan', 'value' => '-20', 'group' => GroupSetting::Point, 'keterangan' => 'TODO: ASUMSI - point (negatif) saat buku dilaporkan/berstatus hilang.'],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                    'keterangan' => $setting['keterangan'],
                ]
            );
        }
    }
}
