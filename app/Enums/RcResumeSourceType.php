<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcResumeSourceType: int implements HasLabel
{
    case Upload = 1;
    case Parse = 2;
    case Manual = 3;
    case Import = 4;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Upload => '上传',
            self::Parse => '解析',
            self::Manual => '手工创建',
            self::Import => '导入',
        };
    }
}
