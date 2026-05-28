<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcResumeStatus: int implements HasLabel
{
    case Normal = 1;
    case Disabled = 0;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Normal => '正常',
            self::Disabled => '停用',
        };
    }
}
