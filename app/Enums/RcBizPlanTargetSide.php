<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcBizPlanTargetSide: int implements HasLabel
{
    case Recruiter = 1;
    case JobSeeker = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Recruiter => 'B端招聘方',
            self::JobSeeker => 'C端求职者',
        };
    }
}
