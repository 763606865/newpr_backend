<?php

namespace App\Filament\Resources\Employees\Tables;

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
use Illuminate\Database\Eloquent\Builder;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['company', 'department', 'position']))
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('employee_no')->label('员工工号')->searchable(),
                TextColumn::make('real_name')->label('员工姓名')->searchable(),
                TextColumn::make('company.name')->label('所属企业')->searchable(),
                TextColumn::make('department.name')->label('所属部门')->toggleable(),
                TextColumn::make('position.name')->label('所属岗位')->toggleable(),
                TextColumn::make('mobile')->label('手机号')->searchable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => ((int) $state) === 1 ? '在职' : '离职')
                    ->color(fn (mixed $state): string => ((int) $state) === 1 ? 'success' : 'gray'),
                TextColumn::make('entry_time')->label('加入时间')->dateTime('Y-m-d H:i:s')->toggleable(),
                TextColumn::make('created_at')->label('创建时间')->dateTime('Y-m-d H:i:s')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label('所属企业')
                    ->relationship('company', 'name')
                    ->searchable(),
                SelectFilter::make('department_id')
                    ->label('所属部门')
                    ->relationship('department', 'name')
                    ->searchable(),
                SelectFilter::make('position_id')
                    ->label('所属岗位')
                    ->relationship('position', 'name')
                    ->searchable(),
                SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        1 => '在职',
                        2 => '离职',
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
