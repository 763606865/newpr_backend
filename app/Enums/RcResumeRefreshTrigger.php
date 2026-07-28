<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcResumeRefreshTrigger: int implements HasLabel
{
    case ResumeUpdated = 1;
    case Explicit = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ResumeUpdated => '更新简历',
            self::Explicit => '主动刷新',
        };
    }
}
