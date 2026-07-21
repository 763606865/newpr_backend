<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcReportReasonType: int implements HasLabel
{
    case FalseInformation = 1;
    case Fraud = 2;
    case IllegalContent = 3;
    case Harassment = 4;
    case Other = 99;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FalseInformation => '虚假信息',
            self::Fraud => '诈骗或收费',
            self::IllegalContent => '违法违规内容',
            self::Harassment => '骚扰或不当联系',
            self::Other => '其他',
        };
    }
}
