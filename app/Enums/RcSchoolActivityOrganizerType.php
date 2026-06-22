<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcSchoolActivityOrganizerType: string implements HasLabel
{
    case School = 'school';
    case Company = 'company';
    case Area = 'area';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::School => '学校',
            self::Company => '企业',
            self::Area => '区域',
        };
    }
}
