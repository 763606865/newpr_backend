<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcReportStatus: int implements HasLabel
{
    case Pending = 0;
    case Processing = 1;
    case Resolved = 2;
    case Rejected = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => '待处理',
            self::Processing => '处理中',
            self::Resolved => '已处理',
            self::Rejected => '已驳回',
        };
    }
}
