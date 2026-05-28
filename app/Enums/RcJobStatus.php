<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcJobStatus: int implements HasLabel
{
    case Draft = 0;
    case Published = 1;
    case Paused = 2;
    case Closed = 3;
    case Expired = 4;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => '草稿',
            self::Published => '已发布',
            self::Paused => '暂停',
            self::Closed => '关闭',
            self::Expired => '过期',
        };
    }
}
