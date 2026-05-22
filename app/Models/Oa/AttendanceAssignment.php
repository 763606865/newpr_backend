<?php

namespace App\Models\Oa;

use App\Enums\AttendanceAssignmentCycleType;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 用户考勤表
 *
 * @property int $id 主键ID
 * @property int $company_id 公司ID
 * @property int $department_id 部门ID
 * @property int $employee_id 员工ID
 * @property int $attendance_rule_id 考勤规则ID
 * @property Carbon $effective_start_date 生效开始日期
 * @property Carbon|null $effective_end_date 生效结束日期
 * @property AttendanceAssignmentCycleType $cycle_type 周期类型
 * @property int $work_days 工作天数
 * @property int $rest_days 休息天数
 * @property Carbon|null $start_anchor_date 周期锚点日期
 * @property int $priority 优先级
 * @property int $status 状态
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read Company $company 所属公司
 * @property-read Department $department 所属部门
 * @property-read Employee $employee 所属员工
 * @property-read AttendanceRule $attendanceRule 关联考勤规则
 */
#[Table('oa_attendance_assignments')]
#[Fillable([
    'company_id',
    'department_id',
    'employee_id',
    'attendance_rule_id',
    'effective_start_date',
    'effective_end_date',
    'cycle_type',
    'work_days',
    'rest_days',
    'start_anchor_date',
    'priority',
    'status',
    'extra',
])]
class AttendanceAssignment extends Model
{
    protected $attributes = [
        'status' => 1,
        'cycle_type' => AttendanceAssignmentCycleType::Fixed,
    ];

    protected function casts(): array
    {
        return [
            'effective_start_date' => 'date',
            'effective_end_date' => 'date',
            'start_anchor_date' => 'date',
            'cycle_type' => AttendanceAssignmentCycleType::class,
            'work_days' => 'integer',
            'rest_days' => 'integer',
            'priority' => 'integer',
            'status' => 'integer',
            'extra' => 'array',
        ];
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
}
