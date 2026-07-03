<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Models\Biz\Plan;
use App\Models\Oa\AttendanceClockLog;
use App\Models\Oa\AttendanceRule;
use App\Models\Oa\AttendanceSchedule;
use App\Models\Oa\LeaveBalance;
use App\Models\Oa\LeaveType;
use App\Models\Pivot\CompanyBUsers;
use App\Models\Rc\CompanyAlbum;
use App\Models\Rc\SchoolActivityBooth;
use App\Models\Rc\SchoolActivityCompany;
use App\Observers\CompanyObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @method static Builder enabled()
 * @method static Builder disabled()
 */
#[Table('companies')]
#[Fillable(['auditor_id', 'parent_id', 'depth', 'name', 'credit_code', 'legal_person', 'contact_phone', 'address', 'status'])]
#[ObservedBy(CompanyObserver::class)]
class Company extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'status' => CompanyStatus::Enabled,
    ];

    protected $casts = [
        'auditor_id' => 'integer',
        'parent_id' => 'integer',
        'depth' => 'integer',
        'status' => CompanyStatus::class,
    ];

    /**
     * 审批人
     */
    public function auditor(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'auditor_id');
    }

    /**
     * 运营操作日志
     */
    public function operationLogs(): HasMany
    {
        return $this->hasMany(CompanyOperationLog::class, 'company_id')
            ->with('operator')
            ->latest('created_at');
    }

    /**
     * 上级企业（集团总部）
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * 下级企业（子公司）
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * 当前企业及所有下级子公司 ID（含自身）。
     *
     * @return Collection<int, int>
     */
    public function descendantAndSelfIds(): Collection
    {
        $ids = collect([$this->id]);
        $frontier = collect([$this->id]);

        while ($frontier->isNotEmpty()) {
            $children = self::query()
                ->whereIn('parent_id', $frontier->all())
                ->pluck('id');

            $newIds = $children->diff($ids);
            if ($newIds->isEmpty()) {
                break;
            }

            $ids = $ids->merge($newIds);
            $frontier = $newIds;
        }

        return $ids->values();
    }

    /**
     * 招聘展示资料
     */
    public function profile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class, 'company_id');
    }

    /**
     * 企业证件
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(CompanyLicense::class, 'company_id');
    }

    /**
     * 企业联系人/股东
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(CompanyContact::class, 'company_id');
    }

    /**
     * 企业相册
     */
    public function albums(): HasMany
    {
        return $this->hasMany(CompanyAlbum::class, 'company_id')
            ->orderBy('sort')
            ->orderByDesc('id');
    }

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
     * 打卡日志
     */
    public function attendanceClockLogs(): HasMany
    {
        return $this->hasMany(AttendanceClockLog::class, 'company_id');
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
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, CompanyPlan::class, 'company_id', 'plan_id', 'id', 'id');
    }

    /**
     * 当前正在执行的方案
     */
    public function currentPlans(): BelongsToMany
    {
        return $this->plans()
            ->wherePivot('is_current', 1)
            ->withPivot(['is_current', 'status', 'start_time', 'end_time']);
    }

    /**
     * B端用户
     */
    public function bUsers(): BelongsToMany
    {
        return $this->belongsToMany(BUser::class, CompanyBUsers::class, 'company_id', 'b_user_id')
            ->withPivot(['status', 'last_login_ip', 'last_login_at'])
            ->wherePivot('status', 1)
            ->orderByPivot('last_login_at', 'desc');
    }

    /**
     * 员工
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'company_id');
    }

    /**
     * 校园活动企业报名记录
     */
    public function schoolActivityCompanies(): HasMany
    {
        return $this->hasMany(SchoolActivityCompany::class, 'company_id');
    }

    /**
     * 校园活动展位占用记录
     */
    public function schoolActivityBooths(): HasMany
    {
        return $this->hasMany(SchoolActivityBooth::class, 'company_id');
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
