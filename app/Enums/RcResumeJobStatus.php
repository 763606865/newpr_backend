<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcResumeJobStatus: int implements HasLabel
{
    case OpenToOpportunity = 1;
    case NotConsidering = 2;
    case ActivelyLooking = 3;
    case FreshGraduate = 4;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::OpenToOpportunity => '在职考虑机会',
            self::NotConsidering => '在职不考虑',
            self::ActivelyLooking => '离职找工作',
            self::FreshGraduate => '应届生',
        };
    }
}
