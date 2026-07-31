<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * 联系我们表单中的咨询产品。
 */
enum RcContactProduct: int implements HasLabel
{
    case RecruitmentService = 1;
    case CampusRecruitment = 2;
    case TalentService = 3;
    case Other = 99;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::RecruitmentService => '招聘服务',
            self::CampusRecruitment => '校园招聘',
            self::TalentService => '人才服务',
            self::Other => '其他',
        };
    }
}
