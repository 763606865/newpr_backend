<?php

namespace App\Models\Oa;

use App\Enums\CompanyStatus;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static Builder enabled()
 * @method static Builder disabled()
 */
#[Table('oa_companies')]
#[Fillable(['name', 'credit_code', 'legal_person', 'contact_phone', 'address', 'status'])]
class Company extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'status' => CompanyStatus::Enabled,
    ];

    /**
     * 部门
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class, 'company_id');
    }

    /**
     * 职位
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class, 'company_id');
    }

    /**
     * 假期类型
     */
    public function leaveTypes(): HasMany
    {
        return $this->hasMany(LeaveType::class, 'company_id');
    }

    /**
     * 假期额度
     */
    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class, 'company_id');
    }

    /**
     * 考勤规则
     */
    public function attendanceRules(): HasMany
    {
        return $this->hasMany(AttendanceRule::class, 'company_id');
    }

    /**
     * 考勤排班
     */
    public function attendanceSchedules(): HasMany
    {
        return $this->hasMany(AttendanceSchedule::class, 'company_id');
    }

    /**
     * 激活状态
     */
    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where($this->getTable() . '.status', '=', CompanyStatus::Enabled->value);
    }

    /**
     * 禁用状态
     */
    #[Scope]
    protected function disabled(Builder $query): void
    {
        $query->where($this->getTable() . '.status', '=', CompanyStatus::Disabled->value);
    }
}
