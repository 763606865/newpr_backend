<?php

namespace App\Filament\Resources\Cms;

use App\Enums\CmsPublishStatus;
use App\Enums\CmsStatus;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Support\Contracts\HasLabel;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CmsTable
{
    public static function enumBadge(string $column, string $label, string $enumClass, array $colors = [], ?string $placeholder = null): TextColumn
    {
        return TextColumn::make($column)
            ->label($label)
            ->badge()
            ->formatStateUsing(function (mixed $state) use ($enumClass, $placeholder): string {
                $enum = self::resolveEnum($enumClass, $state);

                if (! $enum instanceof HasLabel) {
                    return $placeholder ?? (string) ($state ?? '-');
                }

                return (string) $enum->getLabel();
            })
            ->color(function (mixed $state) use ($enumClass, $colors): string {
                $enum = self::resolveEnum($enumClass, $state);

                if ($enum === null) {
                    return 'gray';
                }

                return $colors[$enum->value] ?? 'gray';
            });
    }

    public static function cityFilter(string $column = 'city_code', string $label = '城市编码'): Filter
    {
        return Filter::make($column)
            ->label($label)
            ->schema([
                TextInput::make($column)
                    ->label($label)
                    ->placeholder('请输入城市编码'),
            ])
            ->query(function (Builder $query, array $data) use ($column): Builder {
                return $query->when(
                    filled($data[$column] ?? null),
                    fn (Builder $subQuery): Builder => $subQuery->where($column, '=', (string) $data[$column]),
                );
            });
    }

    public static function statusFilter(string $enumClass, string $column = 'status', string $label = '状态'): SelectFilter
    {
        return SelectFilter::make($column)
            ->label($label)
            ->options($enumClass);
    }

    public static function dateRangeFilter(string $column, string $label): Filter
    {
        return Filter::make($column.'_range')
            ->label($label)
            ->schema([
                DatePicker::make('from')->label('开始'),
                DatePicker::make('until')->label('结束'),
            ])
            ->query(function (Builder $query, array $data) use ($column): Builder {
                return $query
                    ->when(
                        filled($data['from'] ?? null),
                        fn (Builder $subQuery): Builder => $subQuery->whereDate($column, '>=', $data['from']),
                    )
                    ->when(
                        filled($data['until'] ?? null),
                        fn (Builder $subQuery): Builder => $subQuery->whereDate($column, '<=', $data['until']),
                    );
            });
    }

    public static function publishAction(string $column = 'status'): Action
    {
        return Action::make('publish')
            ->label('发布')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->requiresConfirmation()
            ->action(function (Model $record) use ($column): void {
                $record->update([$column => CmsPublishStatus::Published->value]);
            });
    }

    public static function offlineAction(string $column = 'status'): Action
    {
        return Action::make('offline')
            ->label('下线')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function (Model $record) use ($column): void {
                $record->update([$column => CmsPublishStatus::Offline->value]);
            });
    }

    public static function enableAction(string $column = 'status'): Action
    {
        return Action::make('enable')
            ->label('启用')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->action(function (Model $record) use ($column): void {
                $record->update([$column => CmsStatus::Enabled->value]);
            });
    }

    public static function disableAction(string $column = 'status'): Action
    {
        return Action::make('disable')
            ->label('停用')
            ->icon('heroicon-o-x-circle')
            ->color('gray')
            ->requiresConfirmation()
            ->action(function (Model $record) use ($column): void {
                $record->update([$column => CmsStatus::Disabled->value]);
            });
    }

    private static function resolveEnum(string $enumClass, mixed $state): ?BackedEnum
    {
        if ($state instanceof BackedEnum) {
            return $state;
        }

        if (! method_exists($enumClass, 'tryFrom')) {
            return null;
        }

        return $enumClass::tryFrom(is_numeric($state) ? (int) $state : $state);
    }
}
