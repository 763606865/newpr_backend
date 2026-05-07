<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum UserGender: int implements HasLabel
{
    case Unknown = 0;
    case Male = 1;
    case Female = 2;

    public function getLabel(): string|null|Htmlable
    {
        return match ($this) {
            self::Unknown => '未知',
            self::Male => '男',
            self::Female => '女',
        };
    }
}
