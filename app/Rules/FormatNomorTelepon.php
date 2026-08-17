<?php

namespace App\Rules;

use App\Support\NomorTeleponFormatter;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Satu sumber kebenaran untuk validasi format No. Telepon (Aturan poin 3).
 *
 * Menerima input "kotor" apa pun (boleh mengandung '+', spasi, strip, dan
 * prefix 08xxx / 628xxx / 8xxx) - validasi dilakukan dengan mencoba
 * menormalisasi via NomorTeleponFormatter::normalisasi(); jika hasilnya
 * null (tidak bisa diartikan sebagai nomor seluler Indonesia yang valid),
 * input ditolak di sini, TIDAK PERNAH sampai tersimpan/dikirim ke gateway.
 *
 * Normalisasi hasil AKHIR (628xxxxxxxxx) disimpan ke DB oleh caller
 * (UserResource::form() via dehydrateStateUsing, UserImporter via
 * beforeSave()) - Rule ini hanya bertanggung jawab menolak/meloloskan.
 */
class FormatNomorTelepon implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return; // nullable-safe, required/required_without diatur terpisah di caller
        }

        if (NomorTeleponFormatter::normalisasi((string) $value) === null) {
            $fail('Format No. Telepon tidak valid. Nomor harus bisa diartikan sebagai nomor seluler Indonesia (boleh diawali 628, 08, atau 8, dengan/tanpa "+", spasi, atau strip).');
        }
    }
}
