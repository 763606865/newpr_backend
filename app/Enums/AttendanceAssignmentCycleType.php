<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum AttendanceAssignmentCycleType: int implements HasLabel
{
    case Fixed = 1;
    case Shift = 2;
    case Do_X_Rest_Y = 3;

    public function getLabel(): string|null|Htmlable
    {
        return match ($this) {
            self::Fixed => '固定',
            self::Shift => '大小周',
            self::Do_X_Rest_Y => '做X休Y',
        };
    }
}
