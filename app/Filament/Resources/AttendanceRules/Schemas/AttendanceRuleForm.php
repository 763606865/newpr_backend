<?php

namespace App\Filament\Resources\AttendanceRules\Schemas;

use App\Enums\AttendanceRuleWorkType;
use App\Models\Oa\Company;
use App\Models\Oa\Department;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AttendanceRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        $companies = Company::query()->pluck('name', 'id');

        return $schema
            ->components([
                Select::make('company_id')
                    ->label('所属企业')
                    ->options($companies)
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('applicable_scope.department_ids', []);
                    })
                    ->required(),
                TextInput::make('name')
                    ->label('规则名称')
                    ->required()
                    ->maxLength(100),
                TextInput::make('code')
                    ->label('规则编码')
                    ->required()
                    ->unique(table: 'oa_attendance_rules', column: 'code', ignoreRecord: true)
                    ->suffixAction(
                        Action::make('generateCode')
                            ->label('自动生成')
                            ->button()
                            ->color('gray')
                            ->action(function (Set $schemaSet): void {
                                $schemaSet('code', self::generateCode());
                            })
                    )
                    ->maxLength(100),
                Select::make('work_type')
                    ->label('工作类型')
                    ->options(AttendanceRuleWorkType::class)
                    ->live()
                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                        $workType = self::resolveWorkType($state);

                        if ($workType === AttendanceRuleWorkType::Fixed) {
                            $set('is_overnight', 0);
                            $set('time_segments', null);
                            $set('required_work_hours', self::computeWorkHoursFromFixedTimes(null, null, 0));
                        }

                        if ($workType === AttendanceRuleWorkType::Group) {
                            $set('start_time', null);
                            $set('end_time', null);
                            $set('core_start_time', null);
                            $set('core_end_time', null);
                            $set('rest_duration_mins', 0);
                        }

                        if ($workType === AttendanceRuleWorkType::Variable) {
                            $set('start_time', null);
                            $set('end_time', null);
                            $set('time_segments', null);
                            $set('is_overnight', 0);
                            $set('rest_duration_mins', 0);
                        }
                    })
                    ->default(AttendanceRuleWorkType::Fixed->value)
                    ->required(),
                TimePicker::make('start_time')
                    ->label('开始时间')
                    ->native(false)
                    ->seconds(false)
                    ->minutesStep(30)
                    ->live(onBlur: true)
                    ->hidden(fn (Get $get): bool => self::resolveWorkType($get('work_type')) !== AttendanceRuleWorkType::Fixed)
                    ->afterStateUpdated(function (Get $get, Set $set): void {
                        if (self::resolveWorkType($get('work_type')) !== AttendanceRuleWorkType::Fixed) {
                            return;
                        }

                        $set('required_work_hours', self::computeWorkHoursFromFixedTimes(
                            $get('start_time'),
                            $get('end_time'),
                            $get('rest_duration_mins'),
                        ));
                    }),
                TimePicker::make('end_time')
                    ->label('结束时间')
                    ->native(false)
                    ->seconds(false)
                    ->minutesStep(30)
                    ->live(onBlur: true)
                    ->hidden(fn (Get $get): bool => self::resolveWorkType($get('work_type')) !== AttendanceRuleWorkType::Fixed)
                    ->afterStateUpdated(function (Get $get, Set $set): void {
                        if (self::resolveWorkType($get('work_type')) !== AttendanceRuleWorkType::Fixed) {
                            return;
                        }

                        $set('required_work_hours', self::computeWorkHoursFromFixedTimes(
                            $get('start_time'),
                            $get('end_time'),
                            $get('rest_duration_mins'),
                        ));
                    }),
                TimePicker::make('core_start_time')
                    ->label('核心开始时间')
                    ->native(false)
                    ->seconds(false)
                    ->minutesStep(30)
                    ->hidden(fn (Get $get): bool => self::resolveWorkType($get('work_type')) !== AttendanceRuleWorkType::Variable),
                TimePicker::make('core_end_time')
                    ->label('核心结束时间')
                    ->native(false)
                    ->seconds(false)
                    ->minutesStep(30)
                    ->hidden(fn (Get $get): bool => self::resolveWorkType($get('work_type')) !== AttendanceRuleWorkType::Variable),
                TextInput::make('required_work_hours')
                    ->label('要求工作时长')
                    ->numeric()
                    ->minValue(0),
                Select::make('is_overnight')
                    ->label('是否跨天')
                    ->options([
                        0 => '否',
                        1 => '是',
                    ])
                    ->default(0)
                    ->hidden(fn (Get $get): bool => self::resolveWorkType($get('work_type')) !== AttendanceRuleWorkType::Group)
                    ->required(),
                TextInput::make('rest_duration_mins')
                    ->label('休息时长(分钟)')
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set): void {
                        if (self::resolveWorkType($get('work_type')) !== AttendanceRuleWorkType::Fixed) {
                            return;
                        }

                        $set('required_work_hours', self::computeWorkHoursFromFixedTimes(
                            $get('start_time'),
                            $get('end_time'),
                            $get('rest_duration_mins'),
                        ));
                    })
                    ->hidden(fn (Get $get): bool => self::resolveWorkType($get('work_type')) !== AttendanceRuleWorkType::Fixed)
                    ->required(),
                TextInput::make('late_grace_mins')
                    ->label('迟到容忍(分钟)')
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                TextInput::make('early_leave_grace_mins')
                    ->label('早退容忍(分钟)')
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                TextInput::make('clock_in_window_mins')
                    ->label('上班打卡窗口(分钟)')
                    ->integer()
                    ->minValue(0)
                    ->default(30)
                    ->required(),
                TextInput::make('clock_out_window_mins')
                    ->label('下班打卡窗口(分钟)')
                    ->integer()
                    ->minValue(0)
                    ->default(30)
                    ->required(),
                Repeater::make('time_segments')
                    ->label('时间段配置')
                    ->schema([
                        TimePicker::make('start')
                            ->label('开始时间')
                            ->native(false)
                            ->seconds(false)
                            ->minutesStep(30)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                [$requiredWorkHours, $isOvernight] = self::computeWorkHoursFromSegments($get('../../time_segments'));

                                $set('../../required_work_hours', $requiredWorkHours);
                                $set('../../is_overnight', $isOvernight ? 1 : 0);
                            })
                            ->required(),
                        TimePicker::make('end')
                            ->label('结束时间')
                            ->native(false)
                            ->seconds(false)
                            ->minutesStep(30)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                [$requiredWorkHours, $isOvernight] = self::computeWorkHoursFromSegments($get('../../time_segments'));

                                $set('../../required_work_hours', $requiredWorkHours);
                                $set('../../is_overnight', $isOvernight ? 1 : 0);
                            })
                            ->required(),
                    ])
                    ->defaultItems(1)
                    ->addActionLabel('添加时间段')
                    ->reorderable(false)
                    ->collapsible()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                        [$requiredWorkHours, $isOvernight] = self::computeWorkHoursFromSegments($state);

                        $set('required_work_hours', $requiredWorkHours);
                        $set('is_overnight', $isOvernight ? 1 : 0);
                    })
                    ->hidden(fn (Get $get): bool => self::resolveWorkType($get('work_type')) !== AttendanceRuleWorkType::Group),
                Select::make('applicable_scope.department_ids')
                    ->label('适用部门')
                    ->options(function (Get $get): array {
                        $companyId = $get('company_id');

                        if (blank($companyId)) {
                            return [];
                        }

                        return Department::query()
                            ->where('company_id', $companyId)
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->multiple()
                    ->searchable()
                    ->disabled(fn (Get $get): bool => blank($get('company_id')))
                    ->dehydrateStateUsing(function (mixed $state): array {
                        if (! is_array($state)) {
                            return [];
                        }

                        return collect($state)
                            ->filter(fn (mixed $departmentId): bool => filled($departmentId))
                            ->map(fn (mixed $departmentId): int => (int) $departmentId)
                            ->values()
                            ->all();
                    }),
                Select::make('status')
                    ->label('状态')
                    ->options([
                        1 => '启用',
                        0 => '停用',
                    ])
                    ->default(1)
                    ->required(),
            ]);
    }

    /**
     * @return array{0: float, 1: bool}
     */
    private static function computeWorkHoursFromSegments(mixed $segmentsState): array
    {
        if (blank($segmentsState)) {
            return [0.0, false];
        }

        $segments = is_string($segmentsState)
            ? json_decode($segmentsState, true)
            : $segmentsState;

        if (! is_array($segments)) {
            return [0.0, false];
        }

        $totalMinutes = 0;
        $containsOvernightSegment = false;

        foreach ($segments as $segment) {
            if (! is_array($segment)) {
                continue;
            }

            $startMinutes = self::timeToMinutes($segment['start'] ?? null);
            $endMinutes = self::timeToMinutes($segment['end'] ?? null);

            if ($startMinutes === null || $endMinutes === null) {
                continue;
            }

            $durationMinutes = $endMinutes - $startMinutes;

            if ($durationMinutes <= 0) {
                $durationMinutes += 24 * 60;
                $containsOvernightSegment = true;
            }

            $totalMinutes += $durationMinutes;
        }

        return [round($totalMinutes / 60, 2), $containsOvernightSegment];
    }

    private static function timeToMinutes(mixed $time): ?int
    {
        if (! is_string($time)) {
            return null;
        }
        $hour = Carbon::parse($time)->hour;
        $minute = Carbon::parse($time)->minute;

        if ($hour === 24 && $minute !== 0) {
            return null;
        }

        return ($hour * 60) + $minute;
    }

    private static function generateCode(): string
    {
        return strtoupper(uniqid('AR-', false));
    }

    private static function computeWorkHoursFromFixedTimes(mixed $startTime, mixed $endTime, mixed $restDurationMins): float
    {
        $startMinutes = self::timeToMinutes($startTime);
        $endMinutes = self::timeToMinutes($endTime);

        if ($startMinutes === null || $endMinutes === null) {
            return 0.0;
        }

        $durationMinutes = $endMinutes - $startMinutes;

        if ($durationMinutes <= 0) {
            $durationMinutes += 24 * 60;
        }

        $restMinutes = is_numeric($restDurationMins) ? max(0, (int) $restDurationMins) : 0;
        $workedMinutes = max(0, $durationMinutes - $restMinutes);

        return round($workedMinutes / 60, 2);
    }

    private static function resolveWorkType(mixed $workType): ?AttendanceRuleWorkType
    {
        if ($workType instanceof AttendanceRuleWorkType) {
            return $workType;
        }

        if (is_int($workType) || is_string($workType)) {
            return AttendanceRuleWorkType::tryFrom((int) $workType);
        }

        return null;
    }
}
