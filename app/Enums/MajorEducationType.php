<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MajorEducationType: string implements HasLabel
{
    case Undergraduate = '本科';
    case VocationalSecondary = '中职';
    case HigherVocational = '高职专科';
    case VocationalUndergraduate = '职教本科';

    public function getLabel(): ?string
    {
        return $this->value;
    }
}
