<?php

namespace App\Filament\Resources\AttendanceSchedules\Tables;

use App\Enums\AttendanceScheduleStatus;
use App\Filament\Exports\AttendanceScheduleExporter;
use App\Models\Company;
use App\Models\Department;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceSchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('company.name')->label('所属企业')->searchable(),
                TextColumn::make('department.name')->label('部门')->searchable(),
                TextColumn::make('employee.real_name')->label('员工姓名')->searchable(),
                TextColumn::make('attendanceRule.name')->label('考勤规则')->searchable(),
                TextColumn::make('date')->label('考勤日期')->date('Y-m-d')->sortable(),
                TextColumn::make('actual_work_hours')->label('实际工时'),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => (string) (AttendanceScheduleStatus::tryFrom((int) $state)?->getLabel() ?? '未知'))
                    ->color(fn (mixed $state): string => match (AttendanceScheduleStatus::tryFrom((int) $state)) {
                        AttendanceScheduleStatus::Normal => 'success',
                        AttendanceScheduleStatus::Late, AttendanceScheduleStatus::Early => 'warning',
                        AttendanceScheduleStatus::MissingCard, AttendanceScheduleStatus::Absence => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Filter::make('company_department')
                    ->label('企业/部门')
                    ->schema([
                        Select::make('company_id')
                            ->label('所属企业')
                            ->options(fn (): array => Company::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('department_id')
                            ->label('所属部门')
                            ->options(function (callable $get): array {
                                $companyId = $get('company_id');

                                if (! filled($companyId)) {
                                    return [];
                                }

                                return Department::query()
                                    ->where('company_id', (int) $companyId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['company_id'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->where('company_id', (int) $data['company_id']),
                            )
                            ->when(
                                filled($data['department_id'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->where('department_id', (int) $data['department_id']),
                            );
                    }),
                Filter::make('date_range')
                    ->label('考勤日期')
                    ->schema([
                        DatePicker::make('date_from')
                            ->label('开始日期'),
                        DatePicker::make('date_until')
                            ->label('结束日期'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['date_from'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->whereDate('date', '>=', $data['date_from']),
                            )
                            ->when(
                                filled($data['date_until'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->whereDate('date', '<=', $data['date_until']),
                            );
                    }),
                SelectFilter::make('attendance_rule_id')
                    ->label('考勤规则')
                    ->relationship('attendanceRule', 'name'),
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(AttendanceScheduleStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->headerActions([
                ExportAction::make()
                    ->label('导出数据')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->authGuard('admin')
                    ->exporter(AttendanceScheduleExporter::class)
                    ->modifyQueryUsing(function (Builder $query, array $options): Builder {
                        return $query
                            ->where('company_id', (int) ($options['company_id'] ?? 0))
                            ->whereDate('date', '>=', (string) ($options['date_from'] ?? '1970-01-01'))
                            ->whereDate('date', '<=', (string) ($options['date_until'] ?? '2099-12-31'));
                    }),
            ]);
    }
}
