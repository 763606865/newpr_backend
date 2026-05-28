<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcInterviewMode: int implements HasLabel
{
    case Online = 1;
    case Offline = 2;
    case Phone = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Online => '线上',
            self::Offline => '线下',
            self::Phone => '电话',
        };
    }
}
