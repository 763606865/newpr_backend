<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcIdentityType: int implements HasLabel
{
    case JobSeeker = 1;
    case Recruiter = 2;
    case CampusManager = 3;
    case GovernmentManager = 4;
    case Headhunter = 5;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::JobSeeker => '求职者',
            self::Recruiter => '招聘方',
            self::CampusManager => '校招负责人',
            self::GovernmentManager => '政府机构负责人',
            self::Headhunter => '猎头',
        };
    }
}
