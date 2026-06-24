<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CmsMenuAudienceType: int implements HasLabel
{
    case Guest = 0;
    case JobSeeker = 1;
    case Recruiter = 2;
    case CampusManager = 3;
    case GovernmentManager = 4;
    case Headhunter = 5;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Guest => '游客',
            self::JobSeeker => '求职者',
            self::Recruiter => '招聘方',
            self::CampusManager => '校招负责人',
            self::GovernmentManager => '政府机构负责人',
            self::Headhunter => '猎头',
        };
    }

    public static function fromRcIdentity(?RcIdentityType $identity): self
    {
        if ($identity === null) {
            return self::Guest;
        }

        return self::from($identity->value);
    }

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->getLabel()])
            ->all();
    }
}
