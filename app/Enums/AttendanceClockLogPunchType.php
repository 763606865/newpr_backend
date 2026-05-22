<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum AttendanceClockLogPunchType: int implements HasLabel
{
    case ClockIn = 1;
    case ClockOut = 2;
    case Supplement = 3;
    case SystemAdjust = 4;

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::ClockIn => '上班卡',
            self::ClockOut => '下班卡',
            self::Supplement => '补卡',
            self::SystemAdjust => '系统修正',
        };
    }
}
