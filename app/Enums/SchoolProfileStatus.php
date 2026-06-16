<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SchoolProfileStatus: int implements HasLabel
{
    case Disabled = 0;
    case Normal = 1;
    case Reviewing = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Disabled => '禁用',
            self::Normal => '正常',
            self::Reviewing => '审核中',
        };
    }
}
