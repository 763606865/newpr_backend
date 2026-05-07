<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum UserStatus: string implements HasLabel
{
    case Active = 'active';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function getLabel(): string|null|Htmlable
    {
        return match ($this) {
            self::Active => '激活',
            self::Resolved => '修复中',
            self::Closed => '已关闭',
        };
    }
}
