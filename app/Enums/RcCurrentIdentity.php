<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcCurrentIdentity: int implements HasLabel
{
    case Other = 0;
    case WorkingPerson = 1;
    case Student = 2;
    case Unemployed = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Other => '其他',
            self::WorkingPerson => '职场人',
            self::Student => '学生',
            self::Unemployed => '待业',
        };
    }
}
