<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MajorLevel: int implements HasLabel
{
    case Category = 1;
    case Discipline = 2;
    case Major = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Category => '大类',
            self::Discipline => '专业类',
            self::Major => '专业',
        };
    }
}
