<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Satu sumber kebenaran untuk validasi format no_kartu_rfid (Aturan poin 3).
 * Kontrak mengikat ke firmware Attendance Machine (lihat
 * PerpustakaanDeviceController::rfidList() - firmware downloadRfidDb() hanya
 * menerima baris PERSIS 10 digit angka, isdigit() check + len == 10).
 *
 * Kartu yang tidak lolos rule ini akan tersimpan di DB tapi TIDAK PERNAH
 * muncul di daftar rfid-list yang diunduh device (lihat filter REGEXP di
 * controller) - user tersebut tidak akan bisa tap RFID untuk Kunjungan.
 *
 * Wajib dipakai di:
 * - UserResource form (Filament) saat Resource ini dibuat.
 * - Form Request mana pun yang membuat/mengubah User.no_kartu_rfid.
 */
class FormatKartuRfid implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return; // nullable, kartu belum ditempel/didaftarkan
        }

        if (! preg_match('/^[0-9]{10}$/', (string) $value)) {
            $fail('Nomor kartu RFID harus persis 10 digit angka (sesuai kontrak firmware Attendance Machine). Kartu dengan format lain tidak akan terbaca oleh device.');
        }
    }
}
