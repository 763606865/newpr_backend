<?php

namespace App\Filament\Resources\AttendanceSchedules\Schemas;

use App\Enums\AttendanceRuleWorkType;
use App\Enums\AttendanceScheduleStatus;
use App\Models\Oa\AttendanceRule;
use App\Models\Oa\Company;
use App\Models\Oa\Department;
use App\Models\Oa\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AttendanceScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        $companies = Company::query()->pluck('name', 'id');

        return $schema
            ->components([
                Select::make('company_id')
                    ->label('所属企业')
                    ->options($companies)
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('department_id', null);
                        $set('employee_id', null);
                        $set('attendance_rule_id', null);
                    })
                    ->searchable()
                    ->required(),
                Select::make('department_id')
                    ->label('部门')
                    ->options(function (Get $get): array {
                        $companyId = $get('company_id');

                        if (blank($companyId)) {
                            return [];
                        }

                        return Department::query()
                            ->where('company_id', $companyId)
                            ->orderBy('sort')
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->disabled(fn (Get $get): bool => blank($get('company_id')))
                    ->searchable()
                    ->required(),
                Select::make('employee_id')
                    ->label('员工')
                    ->options(function (Get $get): array {
                        $companyId = $get('company_id');

                        if (blank($companyId)) {
                            return [];
                        }

                        return Employee::query()
                            ->where('company_id', $companyId)
                            ->orderBy('id')
                            ->get()
                            ->mapWithKeys(fn (Employee $employee): array => [
                                $employee->id => sprintf('%s（%s）', $employee->real_name ?: $employee->employee_no, $employee->employee_no),
                            ])
                            ->all();
                    })
                    ->disabled(fn (Get $get): bool => blank($get('company_id')))
                    ->searchable()
                    ->required(),
                Select::make('attendance_rule_id')
                    ->label('考勤规则')
                    ->options(function (Get $get): array {
                        $companyId = $get('company_id');

                        if (blank($companyId)) {
                            return [];
                        }

                        return AttendanceRule::query()
                            ->where('company_id', $companyId)
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->disabled(fn (Get $get): bool => blank($get('company_id')))
                    ->searchable()
                    ->required(),
                DatePicker::make('date')
                    ->label('考勤日期')
                    ->required(),
                DateTimePicker::make('std_start_time')
                    ->label('标准上班时间')
                    ->seconds(false)
                    ->minutesStep(30),
                DateTimePicker::make('std_end_time')
                    ->label('标准下班时间')
                    ->seconds(false)
                    ->minutesStep(30),
                TextInput::make('std_work_hours')
                    ->label('标准工时')
                    ->numeric()
                    ->minValue(0),
                Select::make('is_rest_day')
                    ->label('休息日')
                    ->options([
                        0 => '否',
                        1 => '是',
                    ])
                    ->default(0)
                    ->required(),
                Select::make('is_overnight')
                    ->label('跨天班次')
                    ->options([
                        0 => '否',
                        1 => '是',
                    ])
                    ->default(0)
                    ->required(),
                Select::make('work_type')
                    ->label('班次模型')
                    ->options(AttendanceRuleWorkType::class)
                    ->default(AttendanceRuleWorkType::Fixed->value)
                    ->required(),
                DateTimePicker::make('actual_start_time')
                    ->label('实际最早打卡')
                    ->seconds(false)
                    ->minutesStep(30),
                DateTimePicker::make('actual_end_time')
                    ->label('实际最晚打卡')
                    ->seconds(false)
                    ->minutesStep(30),
                TextInput::make('actual_work_hours')
                    ->label('实际工时')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                Select::make('status')
                    ->label('状态')
                    ->options(AttendanceScheduleStatus::class)
                    ->default(AttendanceScheduleStatus::Pending->value)
                    ->required(),
                TextInput::make('late_mins')
                    ->label('迟到分钟')
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                TextInput::make('early_leave_mins')
                    ->label('早退分钟')
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                TextInput::make('absence_mins')
                    ->label('缺勤分钟')
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                Textarea::make('extra')
                    ->label('扩展信息(JSON)')
                    ->maxLength(1000),
            ]);
    }
}
