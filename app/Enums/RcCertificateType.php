<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcCertificateType: int implements HasLabel
{
    case Certificate = 1;
    case Honor = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Certificate => '证书',
            self::Honor => '荣誉奖项',
        };
    }
}
