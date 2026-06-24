<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CmsHomeRecommendationModuleType: int implements HasLabel
{
    case UrgentJob = 1;
    case HotJob = 2;
    case FamousCompany = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::UrgentJob => '紧急招聘',
            self::HotJob => '热招职位',
            self::FamousCompany => '名企招聘',
        };
    }

    public function recommendableMorphType(): string
    {
        return match ($this) {
            self::UrgentJob, self::HotJob => 'job',
            self::FamousCompany => 'company',
        };
    }

    public function isJobModule(): bool
    {
        return in_array($this, [self::UrgentJob, self::HotJob], true);
    }
}
