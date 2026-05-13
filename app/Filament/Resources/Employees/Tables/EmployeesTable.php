<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Enums\AttendanceAssignmentCycleType;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Oa\AttendanceRule;
use App\Models\Oa\LeaveType;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
                TextColumn::make('status')->label('状态')->badge()->color(fn (mixed $state): string => $state === EmployeeStatus::Active ? 'success' : 'gray'),
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
                    ->options(EmployeeStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                static::actionAttendanceAssignments(),
                static::actionLeaveBalances(),
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])->label('更多'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    private static function actionAttendanceAssignments(): Action
    {
        return Action::make('attendanceAssignments')
            ->label('排班')
            ->icon('heroicon-o-calendar-days')
            ->modalHeading('员工排班')
            ->modalSubmitActionLabel('保存')
            ->fillForm(function (Employee $record): array {
                $assignments = $record->attendanceAssignments()
                    ->orderBy('effective_start_date')
                    ->get()
                    ->map(function ($assignment): array {
                        return [
                            'attendance_rule_id' => $assignment->attendance_rule_id,
                            'effective_start_date' => $assignment->effective_start_date,
                            'effective_end_date' => $assignment->effective_end_date,
                            'cycle_type' => $assignment->cycle_type?->value ?? AttendanceAssignmentCycleType::Fixed->value,
                            'work_days' => $assignment->work_days,
                            'rest_days' => $assignment->rest_days,
                            'start_anchor_date' => $assignment->start_anchor_date,
                            'priority' => $assignment->priority,
                            'status' => $assignment->status,
                        ];
                    })
                    ->all();

                return [
                    'assignments' => $assignments,
                ];
            })
            ->schema([
                Repeater::make('assignments')
                    ->label('排班规则')
                    ->defaultItems(1)
                    ->addActionLabel('添加排班规则')
                    ->reorderable(false)
                    ->collapsible()
                    ->schema([
                        Select::make('attendance_rule_id')
                            ->label('考勤规则')
                            ->options(fn (Employee $record): array => AttendanceRule::query()
                                ->where('company_id', $record->company_id)
                                ->where('status', 1)
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                        DatePicker::make('effective_start_date')
                            ->label('生效开始日期')
                            ->required(),
                        DatePicker::make('effective_end_date')
                            ->label('生效结束日期')
                            ->afterOrEqual('effective_start_date'),
                        Select::make('cycle_type')
                            ->label('周期类型')
                            ->options(AttendanceAssignmentCycleType::class)
                            ->default(AttendanceAssignmentCycleType::Fixed->value)
                            ->dehydrateStateUsing(function (mixed $state): int {
                                if ($state instanceof AttendanceAssignmentCycleType) {
                                    return $state->value;
                                }

                                return (int) $state;
                            })
                            ->required(),
                        TextInput::make('work_days')
                            ->label('工作天数')
                            ->integer()
                            ->minValue(0)
                            ->default(7)
                            ->required(),
                        TextInput::make('rest_days')
                            ->label('休息天数')
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        DatePicker::make('start_anchor_date')
                            ->label('周期锚点日期'),
                        TextInput::make('priority')
                            ->label('优先级')
                            ->integer()
                            ->default(0)
                            ->required(),
                        Select::make('status')
                            ->label('状态')
                            ->options([
                                1 => '启用',
                                0 => '停用',
                            ])
                            ->default(1)
                            ->required(),
                    ]),
            ])
            ->action(function (Employee $record, array $data): void {
                $rows = collect($data['assignments'] ?? [])
                    ->filter(fn (mixed $item): bool => is_array($item) && filled($item['attendance_rule_id'] ?? null))
                    ->map(function (array $item) use ($record): array {
                        return [
                            'company_id' => $record->company_id,
                            'department_id' => $record->department_id,
                            'employee_id' => $record->id,
                            'attendance_rule_id' => (int) $item['attendance_rule_id'],
                            'effective_start_date' => $item['effective_start_date'] ?? null,
                            'effective_end_date' => $item['effective_end_date'] ?? null,
                            'cycle_type' => $item['cycle_type'] instanceof AttendanceAssignmentCycleType
                                ? $item['cycle_type']->value
                                : (int) ($item['cycle_type'] ?? AttendanceAssignmentCycleType::Fixed->value),
                            'work_days' => (int) ($item['work_days'] ?? 7),
                            'rest_days' => (int) ($item['rest_days'] ?? 0),
                            'start_anchor_date' => $item['start_anchor_date'] ?? null,
                            'priority' => (int) ($item['priority'] ?? 0),
                            'status' => (int) ($item['status'] ?? 1),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    })
                    ->values();

                DB::transaction(function () use ($record, $rows): void {
                    $record->attendanceAssignments()->delete();

                    if ($rows->isNotEmpty()) {
                        $record->attendanceAssignments()->createMany($rows->all());
                    }
                });
            });
    }

    private static function actionLeaveBalances(): Action
    {
        return Action::make('leaveBalances')
            ->label('假期')
            ->icon('heroicon-o-briefcase')
            ->modalHeading('员工假期配置')
            ->modalSubmitActionLabel('保存')
            ->fillForm(function (Employee $record): array {
                $balances = $record->leaveBalances()
                    ->orderBy('year')
                    ->get()
                    ->map(function ($balance): array {
                        return [
                            'leave_type_id' => $balance->leave_type_id,
                            'year' => $balance->year,
                            'valid_start_date' => $balance->valid_start_date,
                            'valid_end_date' => $balance->valid_end_date,
                            'total_days' => $balance->total_days,
                            'used_days' => $balance->used_days,
                            'balance_days' => $balance->balance_days,
                            'overtime_source_id' => $balance->overtime_source_id,
                        ];
                    })
                    ->all();

                return [
                    'leave_balances' => $balances,
                ];
            })
            ->schema([
                Repeater::make('leave_balances')
                    ->label('假期额度')
                    ->defaultItems(1)
                    ->addActionLabel('添加假期配置')
                    ->reorderable(false)
                    ->collapsible()
                    ->schema([
                        Select::make('leave_type_id')
                            ->label('假期类型')
                            ->options(fn (Employee $record): array => LeaveType::query()
                                ->where('company_id', $record->company_id)
                                ->where('status', 1)
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                        TextInput::make('year')
                            ->label('归属年份')
                            ->integer()
                            ->minValue(2000)
                            ->maxValue(3000)
                            ->required(),
                        DatePicker::make('valid_start_date')
                            ->label('有效期开始')
                            ->required(),
                        DatePicker::make('valid_end_date')
                            ->label('有效期结束')
                            ->afterOrEqual('valid_start_date')
                            ->required(),
                        TextInput::make('total_days')
                            ->label('总授予额度')
                            ->numeric()
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (mixed $state, callable $set, callable $get): mixed => $set('balance_days', self::calculateBalanceDays($state, $get('used_days'))))
                            ->required(),
                        TextInput::make('used_days')
                            ->label('已使用额度')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (mixed $state, callable $set, callable $get): mixed => $set('balance_days', self::calculateBalanceDays($get('total_days'), $state)))
                            ->required(),
                        TextInput::make('balance_days')
                            ->label('剩余额度')
                            ->numeric()
                            ->readOnly()
                            ->required(),
                        TextInput::make('overtime_source_id')
                            ->label('来源加班单ID')
                            ->numeric(),
                    ]),
            ])
            ->action(function (Employee $record, array $data): void {
                $rows = collect($data['leave_balances'] ?? [])
                    ->filter(fn (mixed $item): bool => is_array($item) && filled($item['leave_type_id'] ?? null))
                    ->map(function (array $item) use ($record): array {
                        $totalDays = is_numeric($item['total_days'] ?? null) ? (float) $item['total_days'] : 0.0;
                        $usedDays = is_numeric($item['used_days'] ?? null) ? (float) $item['used_days'] : 0.0;

                        return [
                            'company_id' => $record->company_id,
                            'employee_id' => $record->id,
                            'leave_type_id' => (int) $item['leave_type_id'],
                            'year' => (int) ($item['year'] ?? now()->year),
                            'valid_start_date' => $item['valid_start_date'] ?? null,
                            'valid_end_date' => $item['valid_end_date'] ?? null,
                            'total_days' => round($totalDays, 2),
                            'used_days' => round($usedDays, 2),
                            'balance_days' => self::calculateBalanceDays($totalDays, $usedDays),
                            'overtime_source_id' => filled($item['overtime_source_id'] ?? null) ? (int) $item['overtime_source_id'] : null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    })
                    ->values();

                DB::transaction(function () use ($record, $rows): void {
                    $record->leaveBalances()->delete();

                    if ($rows->isNotEmpty()) {
                        $record->leaveBalances()->createMany($rows->all());
                    }
                });
            });
    }

    private static function calculateBalanceDays(mixed $totalDays, mixed $usedDays): float
    {
        $total = is_numeric($totalDays) ? (float) $totalDays : 0.0;
        $used = is_numeric($usedDays) ? (float) $usedDays : 0.0;

        return round($total - $used, 2);
    }
}
