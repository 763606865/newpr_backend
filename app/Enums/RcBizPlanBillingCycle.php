<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcBizPlanBillingCycle: int implements HasLabel
{
    case OneTime = 1;
    case Monthly = 2;
    case Quarterly = 3;
    case Yearly = 4;
    case UsageBased = 5;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::OneTime => '一次性',
            self::Monthly => '按月',
            self::Quarterly => '按季',
            self::Yearly => '按年',
            self::UsageBased => '按量',
        };
    }
}
