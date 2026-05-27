<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CmsAdType: int implements HasLabel
{
    case Image = 1;
    case Text = 2;
    case Code = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Image => '图片',
            self::Text => '文本',
            self::Code => '代码',
        };
    }
}
