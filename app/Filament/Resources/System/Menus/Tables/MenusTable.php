<?php

namespace App\Filament\Resources\System\Menus\Tables;

use App\Enums\SystemMenuType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MenusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('menu_name')
                    ->label('菜单名称')
                    ->searchable(),

                TextColumn::make('menu_code')
                    ->label('菜单编码')
                    ->searchable(),

                TextColumn::make('parent.menu_name')
                    ->label('父菜单')
                    ->placeholder('顶级'),

                TextColumn::make('menu_type')
                    ->label('类型')
                    ->badge(),

                TextColumn::make('path')
                    ->label('路径'),

                IconColumn::make('visible')
                    ->label('显示')
                    ->boolean(),

                TextColumn::make('sort')
                    ->label('排序')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('menu_type')
                    ->label('菜单类型')
                    ->options(SystemMenuType::class),

                TernaryFilter::make('visible')
                    ->label('是否显示'),
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
