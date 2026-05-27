<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CmsPublishStatus: int implements HasLabel
{
    case Draft = 1;
    case Published = 2;
    case Offline = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => '草稿',
            self::Published => '已发布',
            self::Offline => '下线',
        };
    }
}
