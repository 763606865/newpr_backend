<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcSchoolActivityStatus: int implements HasLabel
{
    case Draft = 0;
    case Published = 1;
    case Ended = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => '草稿',
            self::Published => '已发布',
            self::Ended => '已结束',
        };
    }
}
