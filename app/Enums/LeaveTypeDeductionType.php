<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum LeaveTypeDeductionType: int implements HasLabel
{
    case Full = 1;
    case Half = 2;
    case None = 3;

    public function getLabel(): string|null|Htmlable
    {
        return match ($this) {
            self::Full => '带薪',
            self::Half => '半薪',
            self::None => '无薪',
        };
    }
}
