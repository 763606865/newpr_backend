<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcAnnouncementApplyDeadlineType: int implements HasLabel
{
    case Fixed = 1;
    case UntilFilled = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Fixed => '指定截止日期',
            self::UntilFilled => '招满即止',
        };
    }
}
