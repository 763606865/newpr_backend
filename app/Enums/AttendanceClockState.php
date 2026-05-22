<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum AttendanceClockState: string implements HasLabel
{
    case ClockIn = 'clock_in';
    case ClockOut = 'clock_out';
    case Finished = 'finished';
    case Unavailable = 'unavailable';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::ClockIn => '上班卡',
            self::ClockOut => '下班卡',
            self::Finished => '已完成',
            self::Unavailable => '不可打卡',
        };
    }
}
