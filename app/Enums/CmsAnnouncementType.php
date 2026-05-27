<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CmsAnnouncementType: int implements HasLabel
{
    case SelfPublished = 1;
    case Collected = 2;
    case Authorized = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SelfPublished => '自发公告',
            self::Collected => '站点采集',
            self::Authorized => '授权公告',
        };
    }
}
