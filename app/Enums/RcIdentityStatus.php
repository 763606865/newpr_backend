<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcIdentityStatus: int implements HasLabel
{
    case Enabled = 1;
    case Disabled = 0;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Enabled => '启用',
            self::Disabled => '停用',
        };
    }
}
