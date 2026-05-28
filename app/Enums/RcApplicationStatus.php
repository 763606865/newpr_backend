<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcApplicationStatus: int implements HasLabel
{
    case Pending = 0;
    case Screening = 1;
    case Interviewing = 2;
    case Offering = 3;
    case Hired = 4;
    case Rejected = 5;
    case Withdrawn = 6;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => '待处理',
            self::Screening => '筛选中',
            self::Interviewing => '面试中',
            self::Offering => 'Offer中',
            self::Hired => '录用',
            self::Rejected => '淘汰',
            self::Withdrawn => '撤回',
        };
    }
}
