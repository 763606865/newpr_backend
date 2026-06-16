<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Select;

final class NullableParentIdSelect
{
    public static function configure(Select $select): Select
    {
        return $select
            ->nullable()
            ->formatStateUsing(static fn (mixed $state): ?int => self::normalize($state))
            ->dehydrateStateUsing(static fn (mixed $state): ?int => self::normalize($state));
    }

    public static function normalize(mixed $state): ?int
    {
        if ($state === null || $state === '' || $state === false) {
            return null;
        }

        $normalized = (int) $state;

        return $normalized === 0 ? null : $normalized;
    }

    public static function configureForZeroRoot(Select $select): Select
    {
        return $select
            ->nullable()
            ->formatStateUsing(static fn (mixed $state): ?int => self::normalize($state))
            ->dehydrateStateUsing(static fn (mixed $state): int => self::dehydrateZeroRoot($state));
    }

    public static function dehydrateZeroRoot(mixed $state): int
    {
        return self::normalize($state) ?? 0;
    }
}
