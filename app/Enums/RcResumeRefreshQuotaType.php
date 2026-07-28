<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcResumeRefreshQuotaType: int implements HasLabel
{
    case FreeDaily = 1;
    case Asset = 2;
    case Membership = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FreeDaily => '每日免费',
            self::Asset => '次数权益',
            self::Membership => '会员权益',
        };
    }
}
