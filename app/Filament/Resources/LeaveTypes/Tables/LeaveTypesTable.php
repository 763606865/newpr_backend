<?php

namespace App\Filament\Resources\LeaveTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class LeaveTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('company.name')->label('所属企业')->searchable(),
                TextColumn::make('name')->label('假期名称')->searchable(),
                TextColumn::make('code')->label('假期编码')->searchable(),
                TextColumn::make('deduction_type')
                    ->label('扣薪类型'),
                TextColumn::make('unit_type')
                    ->label('单位')
                    ->formatStateUsing(fn (mixed $state): string => ((int) $state) === 2 ? '小时' : '天'),
                TextColumn::make('min_duration')->label('最小时长'),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => ((int) $state) === 1 ? '启用' : '停用')
                    ->color(fn (mixed $state): string => ((int) $state) === 1 ? 'success' : 'gray'),
                TextColumn::make('created_at')->label('创建时间')->dateTime('Y-m-d H:i:s')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label('所属企业')
                    ->relationship('company', 'name')
                    ->searchable(),
                SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        1 => '启用',
                        0 => '停用',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
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
