<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcSkillProficiency: int implements HasLabel
{
    case Aware = 1;
    case Familiar = 2;
    case Proficient = 3;
    case Expert = 4;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Aware => '了解',
            self::Familiar => '熟悉',
            self::Proficient => '熟练',
            self::Expert => '精通',
        };
    }
}
