<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CompanyRestType: int implements HasLabel
{
    case WeekendOff = 1;
    case SingleDayOff = 2;
    case AlternatingWeekend = 3;
    case Shift = 4;
    case Other = 5;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::WeekendOff => '双休',
            self::SingleDayOff => '单休',
            self::AlternatingWeekend => '大小周',
            self::Shift => '排班',
            self::Other => '其他',
        };
    }
}
