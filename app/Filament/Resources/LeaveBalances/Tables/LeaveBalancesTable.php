<?php

namespace App\Filament\Resources\LeaveBalances\Tables;

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

class LeaveBalancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('company.name')->label('所属企业')->searchable(),
                TextColumn::make('user.name')->label('员工')->searchable(),
                TextColumn::make('leaveType.name')->label('假期类型')->searchable(),
                TextColumn::make('year')->label('年份')->sortable(),
                TextColumn::make('valid_start_date')->label('有效开始')->date('Y-m-d')->sortable(),
                TextColumn::make('valid_end_date')->label('有效结束')->date('Y-m-d')->sortable(),
                TextColumn::make('total_days')->label('总额度'),
                TextColumn::make('used_days')->label('已使用'),
                TextColumn::make('balance_days')->label('剩余额度'),
                TextColumn::make('created_at')->label('创建时间')->dateTime('Y-m-d H:i:s')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label('所属企业')
                    ->relationship('company', 'name')
                    ->searchable(),
                SelectFilter::make('leave_type_id')
                    ->label('假期类型')
                    ->relationship('leaveType', 'name')
                    ->searchable(),
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
