<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum CompanyBUserStatus: int implements HasLabel
{
    case Disabled = 0;
    case Enabled = 1;

    public function getLabel(): string|null|Htmlable
    {
        return match ($this) {
            self::Disabled => '禁用',
            self::Enabled => '启用',
        };
    }
}
