<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CompanyLicenseType: int implements HasLabel
{
    case BusinessLicense = 1;
    case FoodSafetyPermit = 2;
    case Qualification = 3;
    case Other = 4;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::BusinessLicense => '营业执照',
            self::FoodSafetyPermit => '食品经营许可证',
            self::Qualification => '资质证书',
            self::Other => '其他',
        };
    }
}
