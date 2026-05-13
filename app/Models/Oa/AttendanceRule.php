<?php

namespace App\Models\Oa;

use App\Enums\AttendanceRuleWorkType;
use App\Models\Company;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
