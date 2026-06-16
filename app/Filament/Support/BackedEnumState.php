<?php

namespace App\Filament\Support;

use BackedEnum;
use Filament\Support\Contracts\HasLabel;

final class BackedEnumState
{
    public static function resolve(string $enumClass, mixed $state): ?BackedEnum
    {
        if ($state instanceof BackedEnum) {
            return $state;
        }

        if ($state === null || $state === '') {
            return null;
        }

        if (! method_exists($enumClass, 'tryFrom')) {
            return null;
        }

        return $enumClass::tryFrom(is_numeric($state) ? (int) $state : $state);
    }

    public static function label(string $enumClass, mixed $state, string $placeholder = '-'): string
    {
        $enum = self::resolve($enumClass, $state);

        if ($enum instanceof HasLabel) {
            return (string) $enum->getLabel();
        }

        return $placeholder;
    }
}
