<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * 联系我们申请的回访状态。
 */
enum RcContactInquiryStatus: int implements HasLabel
{
    case Pending = 0;
    case FollowedUp = 1;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => '待回访',
            self::FollowedUp => '已回访',
        };
    }
}
