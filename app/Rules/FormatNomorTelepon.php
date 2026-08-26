<?php

namespace App\Rules;

use App\Support\NomorTeleponFormatter;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FormatNomorTelepon implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (NomorTeleponFormatter::normalisasi((string) $value) === null) {
            $fail('Format No. Telepon tidak valid. Nomor harus bisa diartikan sebagai nomor seluler Indonesia (boleh diawali 628, 08, atau 8, dengan/tanpa "+", spasi, atau strip).');
        }
    }
}
