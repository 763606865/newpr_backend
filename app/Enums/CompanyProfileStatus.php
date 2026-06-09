<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CompanyProfileStatus: int implements HasLabel
{
    case Draft = 0;
    case Complete = 1;
    case Auditing = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => '草稿',
            self::Complete => '已完善',
            self::Auditing => '审核中',
        };
    }
}
