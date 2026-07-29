<?php

namespace App\Models;

use App\Enums\GroupSetting;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'key',
        'value',
        'group',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'group' => GroupSetting::class,
        ];
    }
}
