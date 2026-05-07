<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum EmployeeStatus: int implements HasLabel
{
    case Active = 1;

    case Dismissed = 0;

    public function getLabel(): string|null|Htmlable
    {
        return match ($this) {
            self::Active => '在职',
            self::Dismissed => '离职',
        };
    }
}
