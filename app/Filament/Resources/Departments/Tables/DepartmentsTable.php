<?php

namespace App\Filament\Resources\Departments\Tables;

use App\Enums\DepartmentType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DepartmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query
                    ->treeOf(fn (Builder $rootQuery): Builder => $rootQuery->where('parent_id', 0))
                    ->depthFirst();
            })
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('parent.name')->label('父级')->toggleable(),
                TextColumn::make('tree_depth')
                    ->label('层级')
                    ->formatStateUsing(fn (mixed $state): int => ((int) $state) + 1)
                    ->toggleable(),
                TextColumn::make('name')
                    ->label('名称')
                    ->formatStateUsing(function (string $state, mixed $record): string {
                        $level = max(0, (int) ($record->tree_depth ?? 0));

                        return str_repeat('|- ', $level).$state;
                    }),
                SelectColumn::make('type')->options(DepartmentType::class)->label('类型')->disabled(),
                TextColumn::make('created_at')->label('创建时间')->dateTime('Y-m-d H:i:s')->toggleable(),
                TextColumn::make('updated_at')->label('更新时间')->dateTime('Y-m-d H:i:s')->toggleable(),
            ])
            ->filters([
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
