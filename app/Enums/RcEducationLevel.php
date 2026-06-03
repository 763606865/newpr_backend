<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcEducationLevel: int implements HasLabel
{
    case HighSchool = 1;
    case Associate = 2;
    case Bachelor = 3;
    case Master = 4;
    case Doctor = 5;
    case Other = 6;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::HighSchool => '高中/中专',
            self::Associate => '专科',
            self::Bachelor => '本科',
            self::Master => '硕士',
            self::Doctor => '博士',
            self::Other => '其他',
        };
    }
}
