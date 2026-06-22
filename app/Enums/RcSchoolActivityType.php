<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcSchoolActivityType: int implements HasLabel
{
    case JobFair = 0;
    case Presentation = 1;
    case DualSelection = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::JobFair => '招聘会',
            self::Presentation => '宣讲会',
            self::DualSelection => '双选会',
        };
    }
}
