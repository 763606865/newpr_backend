<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcMaritalStatus: int implements HasLabel
{
    case Unknown = 0;
    case Single = 1;
    case Married = 2;
    case Divorced = 3;
    case Widowed = 4;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Unknown => '未知',
            self::Single => '未婚',
            self::Married => '已婚',
            self::Divorced => '离异',
            self::Widowed => '丧偶',
        };
    }
}
