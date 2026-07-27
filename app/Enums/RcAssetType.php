<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcAssetType: int implements HasLabel
{
    case Count = 1;
    case Duration = 2;
    case Credit = 3;
    case Membership = 4;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Count => '次数',
            self::Duration => '时长',
            self::Credit => '额度',
            self::Membership => '会员',
        };
    }
}
