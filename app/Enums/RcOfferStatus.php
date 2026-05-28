<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcOfferStatus: int implements HasLabel
{
    case Draft = 0;
    case Sent = 1;
    case Accepted = 2;
    case Rejected = 3;
    case Expired = 4;
    case Revoked = 5;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => '草稿',
            self::Sent => '已发送',
            self::Accepted => '已接受',
            self::Rejected => '已拒绝',
            self::Expired => '已过期',
            self::Revoked => '已撤销',
        };
    }
}
