<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CompanyPlanStatus: int implements HasLabel
{
    case Disabled = 0;
    case Enabled = 1;
    case Pause = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Disabled => '失效',
            self::Enabled => '生效中',
            self::Pause => '暂停维护',
        };
    }
}
