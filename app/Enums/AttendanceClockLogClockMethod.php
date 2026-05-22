<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum AttendanceClockLogClockMethod: int implements HasLabel
{
    case App = 1;
    case Web = 2;
    case Admin = 3;
    case Import = 4;

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::App => 'APP',
            self::Web => 'WEB',
            self::Admin => '管理员代打',
            self::Import => '导入',
        };
    }
}
