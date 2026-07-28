<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcResumeExposureStatus: int implements HasLabel
{
    case Pending = 0;
    case Active = 1;
    case Expired = 2;
    case Cancelled = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => '待生效',
            self::Active => '生效中',
            self::Expired => '已过期',
            self::Cancelled => '已取消',
        };
    }
}
