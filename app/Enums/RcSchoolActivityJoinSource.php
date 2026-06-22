<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcSchoolActivityJoinSource: int implements HasLabel
{
    case SchoolInvite = 0;
    case CompanyApply = 1;
    case Organizer = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SchoolInvite => '院校后台邀约',
            self::CompanyApply => '企业自主申请',
            self::Organizer => '企业主办',
        };
    }
}
