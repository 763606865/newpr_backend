<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SystemMenuType: int implements HasLabel
{
    case Menu = 1;
    case Button = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Menu => '菜单',
            self::Button => '按钮/权限点',
        };
    }
}
