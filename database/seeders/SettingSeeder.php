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
 * wa_template_* SEKARANG ikut diseed (berubah dari iterasi sebelumnya) -
 * template_code yang dipakai di bawah ini diasumsikan SAMA PERSIS dengan
 * "Kode Template" pada dokumen Template WhatsApp - Perpustakaan (11 event).
 * TODO: ASUMSI - WAJIB dicek ulang terhadap template_code yang benar-benar
 * terdaftar di panel gateway zedlabs; kalau berbeda, WhatsappService akan
 * mengirim template_code yang salah dan gateway akan menolak (lihat kontrak
 * API dok bagian 2.2, kemungkinan respons 4xx dari WhatsappGatewayException).
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

            // --- Kategori C: template_code WhatsApp (11 event) ---
            // TODO: ASUMSI - value di bawah diasumsikan sama persis dengan template_code
            // yang Anda daftarkan di panel gateway zedlabs. WAJIB dicocokkan manual.
            ['key' => 'wa_template_peminjaman_aktif', 'value' => 'peminjaman_aktif', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_reminder_h3', 'value' => 'reminder_h3', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_reminder_h1', 'value' => 'reminder_h1', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_jadi_terlambat', 'value' => 'jadi_terlambat', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_pengembalian_diproses', 'value' => 'pengembalian_diproses', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_denda_dibuat', 'value' => 'denda_dibuat', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_denda_lunas', 'value' => 'denda_lunas', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_badge_naik', 'value' => 'badge_naik', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_reward_didapat', 'value' => 'reward_didapat', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_punishment_diterapkan', 'value' => 'punishment_diterapkan', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_reset_password_otp', 'value' => 'reset_password_otp', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_koreksi_kondisi_pengembalian', 'value' => 'koreksi_kondisi_pengembalian', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway. Dikirim saat Pustakawan/Admin mengoreksi kondisi Pengembalian yang sudah final.'],
            ['key' => 'wa_template_denda_dibatalkan_perlu_refund', 'value' => 'denda_dibatalkan_perlu_refund', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway. Dikirim saat Denda yang SUDAH TERBAYAR dibatalkan akibat koreksi kondisi - Admin wajib menindaklanjuti refund manual (lihat Denda.status_refund).'],
            ['key' => 'wa_template_login_otp', 'value' => 'login_otp', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway. Dikirim saat user login via OTP WhatsApp (setara reset password, tapi TIDAK mengubah password).'],
            ['key' => 'wa_template_buku_ditemukan_kembali', 'value' => 'buku_ditemukan_kembali', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway. Dikirim saat buku yang dilaporkan hilang (via laporkanHilang(), belum pernah punya Pengembalian) ditemukan kembali.'],
            ['key' => 'wa_template_kunjungan_tercatat', 'value' => 'kunjungan_tercatat', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway. BELUM ada di dokumen Template WhatsApp - wajib dibuat manual di panel gateway zedlabs (variabel: nama, jam_tap, device). Dikirim setiap kali Kunjungan RFID tercatat, berlaku semua role.'],
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

        $kredensial = [
            'whatsapp_gateway_base_url' => [
                'value' => config('services.whatsapp_gateway.base_url'),
                'terenkripsi' => false,
            ],
            'whatsapp_gateway_timeout' => [
                'value' => (string) config('services.whatsapp_gateway.timeout', 15),
                'terenkripsi' => false,
            ],
            'whatsapp_gateway_api_key_id' => [
                'value' => config('services.whatsapp_gateway.api_key_id'),
                'terenkripsi' => true,
            ],
            'whatsapp_gateway_secret' => [
                'value' => config('services.whatsapp_gateway.secret'),
                'terenkripsi' => true,
            ],
            'device_gateway_api_key' => [
                'value' => config('services.device_gateway.api_key'),
                'terenkripsi' => true,
            ],
        ];

        foreach ($kredensial as $key => $data) {
            if (! $data['value']) {
                continue; // .env belum diisi - skip, fallback tetap jalan di service terkait
            }

            if ($data['terenkripsi']) {
                Setting::setEncrypted($key, (string) $data['value'], GroupSetting::Kredensial);
            } else {
                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => (string) $data['value'], 'group' => GroupSetting::Kredensial, 'is_encrypted' => false],
                );
            }
        }
    }
}
