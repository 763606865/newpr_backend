<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcBizPlanProductType: int implements HasLabel
{
    case JobPosting = 1;
    case Membership = 2;
    case ValueAddedItem = 3;
    case AiTool = 4;
    case ResumeOptimization = 5;
    case VipCoaching = 6;
    case ResumeRefresh = 7;
    case ResumeExposure = 8;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::JobPosting => '职位发布',
            self::Membership => '会员套餐',
            self::ValueAddedItem => '增值道具',
            self::AiTool => 'AI工具',
            self::ResumeOptimization => '简历优化',
            self::VipCoaching => 'VIP辅导',
            self::ResumeRefresh => '简历刷新',
            self::ResumeExposure => '简历曝光',
        };
    }
}
