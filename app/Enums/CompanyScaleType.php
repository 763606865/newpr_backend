<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CompanyScaleType: int implements HasLabel
{
    case Under20 = 1;
    case From20To99 = 2;
    case From100To499 = 3;
    case From500To999 = 4;
    case From1000To9999 = 5;
    case Over10000 = 6;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Under20 => '0-20人',
            self::From20To99 => '20-99人',
            self::From100To499 => '100-499人',
            self::From500To999 => '500-999人',
            self::From1000To9999 => '1000-9999人',
            self::Over10000 => '10000人以上',
        };
    }
}
