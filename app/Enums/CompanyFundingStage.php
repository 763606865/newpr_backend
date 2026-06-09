<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CompanyFundingStage: int implements HasLabel
{
    case Unfunded = 1;
    case Angel = 2;
    case SeriesA = 3;
    case SeriesB = 4;
    case SeriesC = 5;
    case SeriesDPlus = 6;
    case Listed = 7;
    case NoNeed = 8;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Unfunded => '未融资',
            self::Angel => '天使轮',
            self::SeriesA => 'A轮',
            self::SeriesB => 'B轮',
            self::SeriesC => 'C轮',
            self::SeriesDPlus => 'D轮及以上',
            self::Listed => '已上市',
            self::NoNeed => '不需要融资',
        };
    }
}
