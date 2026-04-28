<?php

namespace App\Models\Oa;

use App\Enums\AttendanceAssignmentCycleType;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
            'date' => 'date',
            'cycle_type' => AttendanceAssignmentCycleType::class,
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
