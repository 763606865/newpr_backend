<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcAssetOwnerType: int implements HasLabel
{
    case Universal = 0;
    case Company = 1;
    case User = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Universal => '通用',
            self::Company => '企业',
            self::User => '个人',
        };
    }
}
