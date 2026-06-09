<?php

namespace App\Support;

use BackedEnum;
use Filament\Support\Contracts\HasLabel;

final class EnumOptions
{
    /**
     * @param  class-string<BackedEnum&HasLabel>  $enumClass
     * @return array<int, array{value: int|string, label: string|null}>
     */
    public static function from(string $enumClass): array
    {
        return collect($enumClass::cases())
            ->map(static fn (BackedEnum&HasLabel $case): array => [
                'value' => $case->value,
                'label' => $case->getLabel(),
            ])
            ->values()
            ->all();
    }
}
