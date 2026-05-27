<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CmsOpenTarget: int implements HasLabel
{
    case Self = 1;
    case Blank = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Self => '当前页',
            self::Blank => '新窗口',
        };
    }
}
