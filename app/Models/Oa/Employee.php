<?php

namespace App\Models\Oa;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('oa_employees')]
#[Fillable(['user_id', 'company_id', 'department_id', 'position_id', 'employee_no', 'real_name', 'avatar', 'email', 'mobile', 'status', 'entry_time'])]
class Employee extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'status' => 1,
    ];

    protected function casts(): array
    {
        return [
            'entry_time' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function attendanceSchedules(): HasMany
    {
        return $this->hasMany(AttendanceSchedule::class, 'employee_id');
    }

    public function attendanceAssignments(): HasMany
    {
        return $this->hasMany(AttendanceAssignment::class, 'employee_id');
    }
}
