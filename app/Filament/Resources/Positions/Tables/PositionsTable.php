<?php

namespace App\Filament\Resources\Positions\Tables;

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

class PositionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('company.name')->label('所属企业')->searchable(),
                TextColumn::make('name')->label('岗位名称')->searchable(),
                TextColumn::make('code')->label('岗位编码')->searchable(),
                TextColumn::make('is_leader')
                    ->label('管理岗')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? '是' : '否')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('sort')->label('排序号')->sortable(),
                TextColumn::make('created_at')->label('创建时间')->dateTime('Y-m-d H:i:s')->toggleable(),
                TextColumn::make('updated_at')->label('更新时间')->dateTime('Y-m-d H:i:s')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label('所属企业')
                    ->relationship('company', 'name')
                    ->searchable(),
                SelectFilter::make('is_leader')
                    ->label('管理岗')
                    ->options([
                        1 => '是',
                        0 => '否',
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
