<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcSchoolBoothStatus: int implements HasLabel
{
    case Disabled = 0;
    case Enabled = 1;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Disabled => '禁用',
            self::Enabled => '启用',
        };
    }
}
