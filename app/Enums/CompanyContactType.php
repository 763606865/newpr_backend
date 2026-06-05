<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CompanyContactType: int implements HasLabel
{
    case LegalPerson = 1;
    case Shareholder = 2;
    case Contact = 3;
    case ActualController = 4;
    case Other = 5;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LegalPerson => '法定代表人',
            self::Shareholder => '股东',
            self::Contact => '联系人',
            self::ActualController => '实际控制人',
            self::Other => '其他',
        };
    }
}
