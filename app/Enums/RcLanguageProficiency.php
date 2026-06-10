<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcLanguageProficiency: int implements HasLabel
{
    case Beginner = 1;
    case Conversational = 2;
    case Business = 3;
    case Fluent = 4;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Beginner => '入门',
            self::Conversational => '日常交流',
            self::Business => '商务谈判',
            self::Fluent => '精通',
        };
    }
}
