<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcSchoolActivityMode: int implements HasLabel
{
    case Online = 1;
    case Offline = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Online => '线上',
            self::Offline => '线下',
        };
    }
}
