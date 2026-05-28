<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcInterviewResult: int implements HasLabel
{
    case Pending = 0;
    case Passed = 1;
    case Failed = 2;
    case ToBeDetermined = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => '待评估',
            self::Passed => '通过',
            self::Failed => '不通过',
            self::ToBeDetermined => '待定',
        };
    }
}
