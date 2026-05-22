<?php

namespace App\Models\Oa;

use App\Enums\AttendanceClockLogPunchType;
use App\Enums\AttendanceClockState;
use App\Enums\AttendanceRuleWorkType;
use App\Enums\AttendanceScheduleStatus;
use App\Enums\ClockMode;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 考勤记录表
 *
 * @property int $id 主键ID
 * @property int $company_id 公司ID
 * @property int $department_id 部门ID
 * @property int $employee_id 员工ID
 * @property int $attendance_rule_id 考勤规则ID
 * @property Carbon|null $date 考勤日期
 * @property Carbon|null $std_start_time 标准上班开始时间(含日期)
 * @property Carbon|null $std_end_time 标准下班结束时间(含日期)
 * @property string|null $std_work_hours 标准应出勤工时(小时)
 * @property bool $is_rest_day 是否休息日
 * @property bool $is_overnight 是否跨天班次
 * @property AttendanceRuleWorkType $work_type 班次模型快照
 * @property Carbon|null $actual_start_time 实际最早打卡时间
 * @property Carbon|null $actual_end_time 实际最晚打卡时间
 * @property-read bool $has_clocked_in 是否已打上班卡
 * @property-read bool $has_clocked_out 是否已打下班卡
 * @property-read bool $is_clock_finished 是否已完成当日打卡
 * @property-read AttendanceClockLogPunchType|null $next_punch_type 下一次打卡类型
 * @property string $actual_work_hours 实际出勤工时
 * @property int $status 考勤状态
 * @property-read AttendanceClockState $clock_state 当前打卡阶段
 * @property int $late_mins 迟到分钟数
 * @property int $early_leave_mins 早退分钟数
 * @property int $absence_mins 缺勤/旷工分钟数
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read Company $company 所属公司
 * @property-read Department $department 所属部门
 * @property-read Employee $employee 所属员工
 * @property-read AttendanceRule $attendanceRule 关联考勤规则
 * @property-read Collection<int, AttendanceClockLog> $attendanceClockLogs 打卡日志
 */
#[Table('oa_attendance_schedules')]
#[Fillable([
    'company_id',
    'department_id',
    'employee_id',
    'attendance_rule_id',
    'date',
    'std_start_time',
    'std_end_time',
    'std_work_hours',
    'is_rest_day',
    'is_overnight',
    'work_type',
    'actual_start_time',
    'actual_end_time',
    'actual_work_hours',
    'status',
    'late_mins',
    'early_leave_mins',
    'absence_mins',
    'extra',
])]
class AttendanceSchedule extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'is_rest_day' => 0,
        'is_overnight' => 0,
        'work_type' => AttendanceRuleWorkType::Fixed->value,
        'actual_work_hours' => 0,
        'status' => 0,
        'late_mins' => 0,
        'early_leave_mins' => 0,
        'absence_mins' => 0,
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'std_start_time' => 'datetime',
            'std_end_time' => 'datetime',
            'std_work_hours' => 'decimal:2',
            'is_rest_day' => 'boolean',
            'is_overnight' => 'boolean',
            'work_type' => AttendanceRuleWorkType::class,
            'actual_start_time' => 'datetime',
            'actual_end_time' => 'datetime',
            'actual_work_hours' => 'decimal:2',
            'status' => 'integer',
            'late_mins' => 'integer',
            'early_leave_mins' => 'integer',
            'absence_mins' => 'integer',
            'extra' => 'array',
        ];
    }

    public function clock(Carbon $clockAt, ClockMode $mode = ClockMode::Normal): self
    {
        $start = $this->actual_start_time ? Carbon::parse($this->actual_start_time) : null;
        $end = $this->actual_end_time ? Carbon::parse($this->actual_end_time) : null;

        if (! $start) {
            $this->actual_start_time = $clockAt;

            return $this;
        }

        if (! $end) {
            if ($clockAt->lessThan($start)) {
                $this->actual_start_time = $clockAt;
                $this->actual_end_time = $start;
            } else {
                $this->actual_end_time = $clockAt;
            }

            return $this;
        }

        if ($mode === ClockMode::ForceOverwrite) {
            if ($clockAt->lessThanOrEqualTo($start)) {
                $this->actual_start_time = $clockAt;
            } else {
                $this->actual_end_time = $clockAt;
            }

            return $this;
        }

        if ($clockAt->lessThan($start)) {
            $this->actual_start_time = $clockAt;
        }

        if ($clockAt->greaterThan($end)) {
            $this->actual_end_time = $clockAt;
        }

        return $this;
    }

    public function recalculateStatus(): self
    {
        $start = $this->actual_start_time ? Carbon::parse($this->actual_start_time) : null;
        $end = $this->actual_end_time ? Carbon::parse($this->actual_end_time) : null;

        $lateMins = 0;
        $earlyLeaveMins = 0;
        $absenceMins = 0;
        $actualWorkHours = 0;

        if ($start && $end) {
            $workedMins = max(0, $start->diffInMinutes($end, false));
            $restDurationMins = (int) ($this->attendanceRule?->rest_duration_mins ?? 0);
            $actualWorkHours = round(max(0, $workedMins - $restDurationMins) / 60, 2);
        }

        if ($start && $this->std_start_time && ! $this->is_rest_day) {
            $lateCompareTime = Carbon::parse($this->std_start_time)
                ->addMinutes((int) ($this->attendanceRule?->late_grace_mins ?? 0));
            if ($start->greaterThan($lateCompareTime)) {
                $lateMins = $lateCompareTime->diffInMinutes($start);
            }
        }

        if ($end && $this->std_end_time && ! $this->is_rest_day) {
            $earlyCompareTime = Carbon::parse($this->std_end_time)
                ->subMinutes((int) ($this->attendanceRule?->early_leave_grace_mins ?? 0));
            if ($end->lessThan($earlyCompareTime)) {
                $earlyLeaveMins = $end->diffInMinutes($earlyCompareTime);
            }
        }

        if ($this->is_rest_day) {
            $status = AttendanceScheduleStatus::Normal;
        } elseif ($start && $end) {
            $status = match (true) {
                $lateMins > 0 => AttendanceScheduleStatus::Late,
                $earlyLeaveMins > 0 => AttendanceScheduleStatus::Early,
                default => AttendanceScheduleStatus::Normal,
            };
        } elseif ($start || $end) {
            $status = AttendanceScheduleStatus::MissingCard;
        } else {
            $status = AttendanceScheduleStatus::Pending;
        }

        $this->late_mins = $lateMins;
        $this->early_leave_mins = $earlyLeaveMins;
        $this->absence_mins = $absenceMins;
        $this->actual_work_hours = $actualWorkHours;
        $this->status = $status->value;

        return $this;
    }

    protected function clockState(): Attribute
    {
        return Attribute::make(
            get: static function (mixed $value, array $attributes): AttendanceClockState {
                if (! empty($attributes['actual_start_time']) && ! empty($attributes['actual_end_time'])) {
                    return AttendanceClockState::Finished;
                }

                if (! empty($attributes['actual_start_time'])) {
                    return AttendanceClockState::ClockOut;
                }

                return AttendanceClockState::ClockIn;
            },
        );
    }

    protected function hasClockedIn(): Attribute
    {
        return Attribute::make(
            get: static fn (mixed $value, array $attributes): bool => ! empty($attributes['actual_start_time']),
        );
    }

    protected function hasClockedOut(): Attribute
    {
        return Attribute::make(
            get: static fn (mixed $value, array $attributes): bool => ! empty($attributes['actual_end_time']),
        );
    }

    protected function isClockFinished(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->clock_state === AttendanceClockState::Finished,
        );
    }

    protected function nextPunchType(): Attribute
    {
        return Attribute::make(
            get: fn (): ?AttendanceClockLogPunchType => match ($this->clock_state) {
                AttendanceClockState::Finished, AttendanceClockState::Unavailable => null,
                AttendanceClockState::ClockOut => AttendanceClockLogPunchType::ClockOut,
                default => AttendanceClockLogPunchType::ClockIn,
            },
        );
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function attendanceRule(): BelongsTo
    {
        return $this->belongsTo(AttendanceRule::class, 'attendance_rule_id');
    }

    public function attendanceClockLogs(): HasMany
    {
        return $this->hasMany(AttendanceClockLog::class, 'attendance_schedule_id');
    }
}
