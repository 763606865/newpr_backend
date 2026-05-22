<?php

namespace App\Models\Oa;

use App\Enums\AttendanceRuleWorkType;
use App\Models\Company;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 考勤规则表
 *
 * @property int $id 主键ID
 * @property int $company_id 公司ID
 * @property string $name 考勤规则名称
 * @property string $code 考勤规则编码
 * @property AttendanceRuleWorkType $work_type 工作类型
 * @property string|null $start_time 上班时间
 * @property string|null $end_time 下班时间
 * @property array<string, mixed>|null $time_segments 时间段配置
 * @property string|null $core_start_time 核心工作开始时间
 * @property string|null $core_end_time 核心工作结束时间
 * @property string|null $required_work_hours 要求工作时长
 * @property bool $is_overnight 是否跨天
 * @property int $rest_duration_mins 扣除的休息时间(分钟)
 * @property int $late_grace_mins 迟到容忍分钟数
 * @property int $early_leave_grace_mins 早退容忍分钟数
 * @property int $clock_in_window_mins 允许上班打卡时间窗口(分钟)
 * @property int $clock_out_window_mins 允许下班打卡时间窗口(分钟)
 * @property array<string, mixed>|null $applicable_scope 适用范围配置
 * @property int $status 状态
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read Company $company 所属公司
 * @property-read Collection<int, AttendanceSchedule> $schedules 考勤排班记录
 * @property-read Collection<int, AttendanceClockLog> $attendanceClockLogs 打卡日志
 */
#[Table('oa_attendance_rules')]
#[Fillable([
    'company_id',
    'name',
    'code',
    'work_type',
    'start_time',
    'end_time',
    'time_segments',
    'core_start_time',
    'core_end_time',
    'required_work_hours',
    'is_overnight',
    'rest_duration_mins',
    'late_grace_mins',
    'early_leave_grace_mins',
    'clock_in_window_mins',
    'clock_out_window_mins',
    'applicable_scope',
    'status',
    'extra',
])]
class AttendanceRule extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'work_type' => AttendanceRuleWorkType::Fixed,
        'is_overnight' => 0,
        'rest_duration_mins' => 0,
        'late_grace_mins' => 0,
        'early_leave_grace_mins' => 0,
        'clock_in_window_mins' => 30,
        'clock_out_window_mins' => 30,
        'status' => 1,
    ];

    protected function casts(): array
    {
        return [
            'work_type' => AttendanceRuleWorkType::class,
            'required_work_hours' => 'decimal:2',
            'is_overnight' => 'boolean',
            'rest_duration_mins' => 'integer',
            'late_grace_mins' => 'integer',
            'early_leave_grace_mins' => 'integer',
            'clock_in_window_mins' => 'integer',
            'clock_out_window_mins' => 'integer',
            'applicable_scope' => 'array',
            'time_segments' => 'array',
            'extra' => 'array',
            'status' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(AttendanceSchedule::class, 'attendance_rule_id');
    }

    public function attendanceClockLogs(): HasMany
    {
        return $this->hasMany(AttendanceClockLog::class, 'attendance_rule_id');
    }
}
