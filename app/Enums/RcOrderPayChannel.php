<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcOrderPayChannel: int implements HasLabel
{
    case Unselected = 0;
    case Wechat = 1;
    case Alipay = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Unselected => '未选择',
            self::Wechat => '微信支付',
            self::Alipay => '支付宝',
        };
    }
}
