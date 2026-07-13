<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AreaLevel: int implements HasLabel
{
    case Country = 0;
    case Province = 1;
    case City = 2;
    case District = 3;
    case Street = 4;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Country => '国',
            self::Province => '省',
            self::City => '市',
            self::District => '区县',
            self::Street => '街道',
        };
    }
}
