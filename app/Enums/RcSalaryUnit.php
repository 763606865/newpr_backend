<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcSalaryUnit: int implements HasLabel
{
    case Month = 1;
    case Day = 2;
    case Hour = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Month => '月',
            self::Day => '日',
            self::Hour => '时',
        };
    }
}
