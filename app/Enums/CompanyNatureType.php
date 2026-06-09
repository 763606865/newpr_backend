<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CompanyNatureType: int implements HasLabel
{
    case Private = 1;
    case StateOwned = 2;
    case Foreign = 3;
    case JointVenture = 4;
    case PublicInstitution = 5;
    case NonProfit = 6;
    case Other = 7;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Private => '民营企业',
            self::StateOwned => '国有企业',
            self::Foreign => '外资企业',
            self::JointVenture => '合资企业',
            self::PublicInstitution => '事业单位',
            self::NonProfit => '非营利组织',
            self::Other => '其他',
        };
    }
}
