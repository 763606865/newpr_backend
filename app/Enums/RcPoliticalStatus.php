<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RcPoliticalStatus: int implements HasLabel
{
    case CpcMember = 1;
    case DemocraticParty = 2;
    case NonPartisan = 3;
    case LeagueMember = 4;
    case Masses = 5;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CpcMember => '中共党员（含预备党员）',
            self::DemocraticParty => '民主党派',
            self::NonPartisan => '无党派人士',
            self::LeagueMember => '共青团员',
            self::Masses => '群众',
        };
    }
}
