<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum AttendanceScheduleStatus: int implements HasLabel
{
    case Pending = 0;
    case Normal = 1;
    case Late = 2;
    case Early = 3;
    case MissingCard = 4;
    case Absence = 5;

    public function getLabel(): string|null|Htmlable
    {
        return match ($this) {
            self::Pending => '待计算',
            self::Normal => '正常',
            self::Late => '迟到',
            self::Early => '早退',
            self::MissingCard => '缺卡',
            self::Absence => '旷工',
        };
    }
}
