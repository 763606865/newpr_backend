<?php

namespace App\Filament\Resources\AttendanceSchedules\Tables;

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

class AttendanceSchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('company.name')->label('所属企业')->searchable(),
                TextColumn::make('department.name')->label('部门')->searchable(),
                TextColumn::make('employee.employee_no')->label('员工工号')->searchable(),
                TextColumn::make('attendanceRule.name')->label('考勤规则')->searchable(),
                TextColumn::make('date')->label('考勤日期')->date('Y-m-d')->sortable(),
                TextColumn::make('std_start_time')->label('标准上班')->dateTime('Y-m-d H:i'),
                TextColumn::make('std_end_time')->label('标准下班')->dateTime('Y-m-d H:i'),
                TextColumn::make('actual_work_hours')->label('实际工时'),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => match ((int) $state) {
                        1 => '正常',
                        2 => '迟到',
                        3 => '早退',
                        4 => '缺卡',
                        5 => '旷工',
                        default => '待计算',
                    }),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label('所属企业')
                    ->relationship('company', 'name')
                    ->searchable(),
                SelectFilter::make('attendance_rule_id')
                    ->label('考勤规则')
                    ->relationship('attendanceRule', 'name')
                    ->searchable(),
                SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        0 => '待计算',
                        1 => '正常',
                        2 => '迟到',
                        3 => '早退',
                        4 => '缺卡',
                        5 => '旷工',
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
