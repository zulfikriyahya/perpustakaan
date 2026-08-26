<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FormatKartuRfid implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! preg_match('/^[0-9]{10}$/', (string) $value)) {
            $fail('Nomor kartu RFID harus persis 10 digit angka (sesuai kontrak firmware Attendance Machine). Kartu dengan format lain tidak akan terbaca oleh device.');
        }
    }
}
