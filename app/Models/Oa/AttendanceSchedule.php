<?php

namespace App\Models\Oa;

use App\Enums\AttendanceRuleWorkType;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
