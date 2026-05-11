<?php

namespace App\Filament\Resources\System\Plans\Tables;

use App\Enums\SystemPlanStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plan_name')
                    ->label('方案名称')
                    ->searchable(),

                TextColumn::make('plan_code')
                    ->label('方案编码')
                    ->searchable(),

                TextColumn::make('price')
                    ->label('价格')
                    ->money('CNY'),

                TextColumn::make('duration')
                    ->label('时长(天)')
                    ->formatStateUsing(fn ($state) => $state == 0 ? '永久' : $state.'天'),

                TextColumn::make('sort')
                    ->label('排序')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('状态')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(SystemPlanStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn ($record) => $record->plan_code !== 'trial_plan'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn ($records) => $records->where('plan_code', '!=', 'trial_plan')->isNotEmpty()),
                ]),
            ]);
    }
}
