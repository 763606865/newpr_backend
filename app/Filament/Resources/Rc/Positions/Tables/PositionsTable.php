<?php

namespace App\Filament\Resources\Rc\Positions\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PositionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')->label('职位名称')->searchable(),
                TextColumn::make('code')->label('职位编码')->searchable(),
                TextColumn::make('parent.name')->label('父级职位')->placeholder('-'),
                TextColumn::make('sort')->label('排序')->sortable(),
                TextColumn::make('updated_at')->label('更新时间')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('父级职位')
                    ->relationship(
                        name: 'parent',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->orderBy('sort')->orderBy('id'),
                    )
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->defaultSort('sort')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
