<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcPortfolioType: int implements HasLabel
{
    case Link = 1;
    case Image = 2;
    case Video = 3;
    case Document = 4;
    case Other = 5;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Link => '链接',
            self::Image => '图片',
            self::Video => '视频',
            self::Document => '文档',
            self::Other => '其他',
        };
    }
}
