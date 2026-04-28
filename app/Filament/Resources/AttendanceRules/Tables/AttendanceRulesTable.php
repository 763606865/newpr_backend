<?php

namespace App\Filament\Resources\AttendanceRules\Tables;

use App\Enums\AttendanceRuleWorkType;
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

class AttendanceRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('company.name')->label('所属企业')->searchable(),
                TextColumn::make('name')->label('规则名称')->searchable(),
                TextColumn::make('code')->label('规则编码')->searchable(),
                TextColumn::make('work_type')
                    ->label('工作类型')
                    ->formatStateUsing(function (mixed $state): string {
                        if ($state instanceof AttendanceRuleWorkType) {
                            return (string) $state->getLabel();
                        }

                        return AttendanceRuleWorkType::tryFrom((int) $state)?->getLabel() ?? '固定';
                    }),
                TextColumn::make('required_work_hours')->label('工时'),
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
