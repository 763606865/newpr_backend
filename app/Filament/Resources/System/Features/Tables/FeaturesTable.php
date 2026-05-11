<?php

namespace App\Filament\Resources\System\Features\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FeaturesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('feature_name')
                    ->label('功能名称')
                    ->searchable(),

                TextColumn::make('feature_code')
                    ->label('功能编码')
                    ->searchable(),

                TextColumn::make('menu.menu_name')
                    ->label('所属菜单'),

                TextColumn::make('description')
                    ->label('描述')
                    ->limit(50),

                IconColumn::make('status')
                    ->label('状态')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('status')
                    ->label('状态'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
