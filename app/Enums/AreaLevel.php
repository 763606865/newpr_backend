<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AreaLevel: int implements HasLabel
{
    case Province = 1;
    case City = 2;
    case District = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Province => '省',
            self::City => '市',
            self::District => '区县',
        };
    }
}
