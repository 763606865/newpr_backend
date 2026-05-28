<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcApplicationFlowActionType: int implements HasLabel
{
    case Transfer = 1;
    case Note = 2;
    case Withdraw = 3;
    case Reject = 4;
    case Hire = 5;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Transfer => '流转',
            self::Note => '备注',
            self::Withdraw => '撤回',
            self::Reject => '淘汰',
            self::Hire => '录用',
        };
    }
}
