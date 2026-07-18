<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcAiResumeParseStatus: int implements HasLabel
{
    case Pending = 0;
    case Processing = 1;
    case Succeeded = 2;
    case Failed = 3;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => '等待解析',
            self::Processing => '解析中',
            self::Succeeded => '解析成功',
            self::Failed => '解析失败',
        };
    }
}
