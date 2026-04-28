<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum AttendanceRuleWorkType: int implements HasLabel
{
    case Fixed = 1;
    case Group = 2;
    case Variable = 3;

    public function getLabel(): string|null|Htmlable
    {
        return match ($this) {
            self::Fixed => '固定',
            self::Group => '分段',
            self::Variable => '弹性',
        };
    }
}
