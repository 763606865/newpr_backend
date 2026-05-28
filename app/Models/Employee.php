<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Models\Cast\AliyunOss;
use App\Models\Oa\AttendanceAssignment;
use App\Models\Oa\AttendanceClockLog;
use App\Models\Oa\AttendanceSchedule;
use App\Models\Oa\LeaveBalance;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @method static Builder active()
 * @method static Builder dismissed()
 *
 * @property string $mobile_mask
 * @property string $email_mask
 */
#[Table('employees')]
#[Fillable(['user_id', 'company_id', 'department_id', 'position_id', 'employee_no', 'real_name', 'avatar', 'email', 'mobile', 'status', 'entry_time'])]
class Employee extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'status' => EmployeeStatus::Active->value,
    ];

    protected function casts(): array
    {
        return [
            'avatar' => AliyunOss::class.':oss,public,3600',
            'status' => EmployeeStatus::class,
            'entry_time' => 'datetime',
        ];
    }

    /**
     * 脱敏手机号
     */
    protected function mobileMask(): Attribute
    {
        return Attribute::make(
            get: fn () => Str::mask($this->mobile ?? '', '*', 3, 4),
        );
    }

    /**
     * 脱敏邮箱
     */
    protected function emailMask(): Attribute
    {
        return Attribute::make(
            get: fn () => Str::mask($this->email ?? '', '*', 3, strpos($this->email ?? '', '@') - 3),
        );
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

    public function attendanceClockLogs(): HasMany
    {
        return $this->hasMany(AttendanceClockLog::class, 'employee_id');
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class, 'employee_id');
    }

    /**
     * 只包括活跃用户。
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where($this->getTable().'.status', '=', EmployeeStatus::Active->value);
    }

    /**
     * 只包括离职用户。
     */
    #[Scope]
    public function dismissed(Builder $query): void
    {
        $query->where($this->getTable().'.status', '=', EmployeeStatus::Dismissed->value);
    }
}
