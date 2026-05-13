<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Models\Biz\Plan;
use App\Models\Oa\AttendanceRule;
use App\Models\Oa\AttendanceSchedule;
use App\Models\Oa\LeaveBalance;
use App\Models\Oa\LeaveType;
use App\Observers\CompanyObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static Builder enabled()
 * @method static Builder disabled()
 */
#[Table('companies')]
#[Fillable(['name', 'credit_code', 'legal_person', 'contact_phone', 'address', 'status'])]
#[ObservedBy(CompanyObserver::class)]
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
     * 企业方案关联
     */
    public function companyPlans(): HasMany
    {
        return $this->hasMany(CompanyPlan::class, 'company_id');
    }

    /**
     * 企业历史方案关联
     */
    public function shipCompanyPlans(): HasMany
    {
        return $this->hasMany(ShipCompanyPlan::class, 'company_id');
    }

    /**
     * 使用过的方案
     */
    public function plans(): HasManyThrough
    {
        return $this->hasManyThrough(Plan::class, CompanyPlan::class, 'company_id', 'id', 'id', 'plan_id');
    }

    /**
     * 当前正在执行的方案
     */
    public function currentPlan(): HasOneThrough
    {
        return $this->hasOneThrough(
            Plan::class,
            CompanyPlan::class,
            'company_id',
            'id',
            'id',
            'plan_id'
        )->where('is_current', 1);
    }

    /**
     * 激活状态
     */
    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where($this->getTable().'.status', '=', CompanyStatus::Enabled->value);
    }

    /**
     * 禁用状态
     */
    #[Scope]
    protected function disabled(Builder $query): void
    {
        $query->where($this->getTable().'.status', '=', CompanyStatus::Disabled->value);
    }
}
