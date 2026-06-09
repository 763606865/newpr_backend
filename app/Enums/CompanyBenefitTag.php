<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CompanyBenefitTag: string implements HasLabel
{
    case SocialInsurance = 'social_insurance';
    case HousingFund = 'housing_fund';
    case WeekendOff = 'weekend_off';
    case FlexibleWork = 'flexible_work';
    case AnnualBonus = 'annual_bonus';
    case PaidLeave = 'paid_leave';
    case MealAllowance = 'meal_allowance';
    case TransportAllowance = 'transport_allowance';
    case StockOption = 'stock_option';
    case Training = 'training';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SocialInsurance => '五险一金',
            self::HousingFund => '住房公积金',
            self::WeekendOff => '双休',
            self::FlexibleWork => '弹性工作',
            self::AnnualBonus => '年终奖',
            self::PaidLeave => '带薪年假',
            self::MealAllowance => '餐补',
            self::TransportAllowance => '交通补贴',
            self::StockOption => '股票期权',
            self::Training => '培训机会',
        };
    }
}
