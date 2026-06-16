<?php

namespace App\Filament\Resources\Rc;

use App\Filament\Support\BackedEnumState;
use BackedEnum;
use Filament\Support\Contracts\HasLabel;
use Filament\Tables\Columns\TextColumn;

class RcTable
{
    public static function enumBadge(
        string $column,
        string $label,
        string $enumClass,
        array $colors = [],
        ?string $placeholder = null,
    ): TextColumn {
        return TextColumn::make($column)
            ->label($label)
            ->badge()
            ->formatStateUsing(function (mixed $state) use ($enumClass, $placeholder): string {
                $enum = BackedEnumState::resolve($enumClass, $state);

                if (! $enum instanceof HasLabel) {
                    return $placeholder ?? (string) ($state ?? '-');
                }

                return (string) $enum->getLabel();
            })
            ->color(function (mixed $state) use ($enumClass, $colors): string {
                $enum = BackedEnumState::resolve($enumClass, $state);

                if ($enum === null) {
                    return 'gray';
                }

                return $colors[$enum->value] ?? 'gray';
            });
    }

    public static function integerBooleanBadge(
        string $column,
        string $label,
        string $trueLabel = '是',
        string $falseLabel = '否',
    ): TextColumn {
        return TextColumn::make($column)
            ->label($label)
            ->badge()
            ->formatStateUsing(fn (mixed $state): string => self::formatIntegerBoolean($state, $trueLabel, $falseLabel));
    }

    public static function formatIntegerBoolean(
        mixed $state,
        string $trueLabel = '是',
        string $falseLabel = '否',
    ): string {
        if ($state === true || $state === 1 || $state === '1') {
            return $trueLabel;
        }

        return $falseLabel;
    }

    public static function resolveEnum(string $enumClass, mixed $state): ?BackedEnum
    {
        return BackedEnumState::resolve($enumClass, $state);
    }
}
