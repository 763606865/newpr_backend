<?php

namespace App\Filament\Resources\Departments\Tables;

use App\Enums\DepartmentType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DepartmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('parent.name')->label('父级')->toggleable(),
                TextColumn::make('depth')->label('层级')->toggleable(),
                TextColumn::make('name')->label('名称'),
                SelectColumn::make('type')->options(DepartmentType::class)->label('类型')->disabled(),
                TextColumn::make('created_at')->toggleable(),
                TextColumn::make('updated_at')->toggleable(),
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
