<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum AttendanceClockLogClockResult: int implements HasLabel
{
    case Valid = 1;
    case Invalid = 2;
    case OutOfWindowRejected = 3;
    case Duplicate = 4;

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Valid => '有效',
            self::Invalid => '无效',
            self::OutOfWindowRejected => '超窗拒绝',
            self::Duplicate => '重复',
        };
    }
}
