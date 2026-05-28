<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcJobEmploymentType: int implements HasLabel
{
    case FullTime = 1;
    case PartTime = 2;
    case Internship = 3;
    case Campus = 4;
    case Outsource = 5;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FullTime => '全职',
            self::PartTime => '兼职',
            self::Internship => '实习',
            self::Campus => '校招',
            self::Outsource => '外包',
        };
    }
}
