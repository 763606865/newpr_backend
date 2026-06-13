<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcInterviewStatus: int implements HasLabel
{
    case Pending = 0;
    case Scheduled = 1;
    case Finished = 2;
    case Cancelled = 3;
    case AwaitingCandidate = 4;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => '待安排',
            self::Scheduled => '已安排',
            self::Finished => '已完成',
            self::Cancelled => '已取消',
            self::AwaitingCandidate => '待候选人确认',
        };
    }
}
