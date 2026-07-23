<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ImInteractionRequestStatus: string implements HasLabel
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => '待处理',
            self::Accepted => '已同意',
            self::Rejected => '已拒绝',
            self::Expired => '已过期',
            self::Cancelled => '已取消',
        };
    }
}
