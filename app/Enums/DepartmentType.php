<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum DepartmentType: int implements HasLabel
{
    case Function = 1;
    case Business = 2;
    case Leader =3;

    public function getLabel():  string| null| Htmlable
    {
        return match($this) {
            self::Function => '职能',
            self::Business => '业务',
            self::Leader => '管理层',
        };
    }
}
