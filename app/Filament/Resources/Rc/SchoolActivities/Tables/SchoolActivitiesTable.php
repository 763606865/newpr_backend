<?php

namespace App\Filament\Resources\Rc\SchoolActivities\Tables;

use App\Models\Area;
use App\Models\Rc\SchoolActivity;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SchoolActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('标题')
                    ->searchable(),
                TextColumn::make('region_label')
                    ->label('所在地区')
                    ->getStateUsing(fn (SchoolActivity $record): ?string => Area::formatRegionLabel(
                        $record->province_code,
                        $record->city_code,
                        $record->district_code,
                    )),
                TextColumn::make('start_time')
                    ->label('开始时间')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label('结束时间')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('organizer.name')
                    ->label('主办方'),
                TextColumn::make('contact_name')
                    ->label('联系人')
                    ->searchable(),
                TextColumn::make('contact_phone')
                    ->label('联系电话')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->sortable(),
                ToggleColumn::make('is_hot')
                    ->label('热门'),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('更新时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('删除时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
