<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CmsLinkType: int implements HasLabel
{
    case Internal = 1;
    case External = 2;
    case None = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Internal => '站内',
            self::External => '站外',
            self::None => '无跳转',
        };
    }
}
