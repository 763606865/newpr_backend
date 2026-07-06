<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CmsHomeRecommendationModuleType: int implements HasLabel
{
    case UrgentJob = 1;
    case HotJob = 2;
    case FamousCompany = 3;
    case CampusHotCompany = 4;
    case CampusHotJob = 5;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::UrgentJob => '紧急招聘',
            self::HotJob => '热招职位',
            self::FamousCompany => '名企招聘',
            self::CampusHotCompany => '校招-热门公司',
            self::CampusHotJob => '热门校招',
        };
    }

    public function recommendableMorphType(): string
    {
        return match ($this) {
            self::UrgentJob, self::HotJob, self::CampusHotJob => 'job',
            self::FamousCompany, self::CampusHotCompany => 'company',
        };
    }

    public function isJobModule(): bool
    {
        return in_array($this, [self::UrgentJob, self::HotJob, self::CampusHotJob], true);
    }

    public function isCompanyModule(): bool
    {
        return in_array($this, [self::FamousCompany, self::CampusHotCompany], true);
    }
}
