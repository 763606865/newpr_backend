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
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with('parent')
                ->orderBy('company_id')
                ->orderBy('parent_id')
                ->orderBy('sort')
                ->orderBy('id'))
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('parent.name')->label('父级')->toggleable(),
                TextColumn::make('name')
                    ->label('名称')
                    ->searchable(),
                SelectColumn::make('type')->options(DepartmentType::class)->label('类型')->disabled(),
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
