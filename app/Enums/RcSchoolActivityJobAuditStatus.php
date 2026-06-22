<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcSchoolActivityJobAuditStatus: int implements HasLabel
{
    case Pending = 0;
    case Approved = 1;
    case Rejected = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => '待审核',
            self::Approved => '审核通过',
            self::Rejected => '驳回',
        };
    }
}
