<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcEmploymentType: int implements HasLabel
{
    case FullTime = 1;
    case PartTime = 2;
    case Internship = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FullTime => '全职',
            self::PartTime => '兼职',
            self::Internship => '实习',
        };
    }
}
