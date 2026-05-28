<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcTalentPoolMemberSourceType: int implements HasLabel
{
    case Manual = 1;
    case JobInflow = 2;
    case Import = 3;
    case Recommendation = 4;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Manual => '主动加入',
            self::JobInflow => '职位沉淀',
            self::Import => '导入',
            self::Recommendation => '推荐',
        };
    }
}
