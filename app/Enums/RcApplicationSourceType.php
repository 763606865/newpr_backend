<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcApplicationSourceType: int implements HasLabel
{
    case Direct = 1;
    case Referral = 2;
    case Headhunter = 3;
    case Campus = 4;
    case Government = 5;
    case Import = 6;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Direct => '主动投递',
            self::Referral => '内推',
            self::Headhunter => '猎头',
            self::Campus => '校招',
            self::Government => '政府渠道',
            self::Import => '导入',
        };
    }
}
