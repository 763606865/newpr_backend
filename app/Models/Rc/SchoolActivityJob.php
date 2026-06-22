<?php

namespace App\Models\Rc;

use App\Enums\RcSchoolActivityJobAuditStatus;
use App\Models\Company;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 活动绑定招聘岗位表
 *
 * @property int $id 主键ID
 * @property int $activity_id 校园活动ID
 * @property int $company_id 参会企业ID
 * @property int $school_activity_company_id 企业活动报名单ID
 * @property int $job_id 企业基础职位ID
 * @property RcSchoolActivityJobAuditStatus $audit_status 审核状态
 * @property string|null $reject_reason 岗位驳回原因
 * @property Carbon|null $audit_at 审核操作时间
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read SchoolActivity $activity 所属活动
 * @property-read Company $company 参会企业
 * @property-read SchoolActivityCompany $companyApplication 企业活动报名单
 * @property-read Job $job 关联职位
 *
 * @method static Builder pendingAudit()
 * @method static Builder approved()
 * @method static Builder rejected()
 */
#[Table('rc_school_activity_jobs')]
#[Fillable([
    'activity_id',
    'company_id',
    'school_activity_company_id',
    'job_id',
    'audit_status',
    'reject_reason',
    'audit_at',
])]
class SchoolActivityJob extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'audit_status' => RcSchoolActivityJobAuditStatus::Pending,
    ];

    protected function casts(): array
    {
        return [
            'activity_id' => 'integer',
            'company_id' => 'integer',
            'school_activity_company_id' => 'integer',
            'job_id' => 'integer',
            'audit_status' => RcSchoolActivityJobAuditStatus::class,
            'audit_at' => 'datetime',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(SchoolActivity::class, 'activity_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function companyApplication(): BelongsTo
    {
        return $this->belongsTo(SchoolActivityCompany::class, 'school_activity_company_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    #[Scope]
    protected function pendingAudit(Builder $query): void
    {
        $query->where('audit_status', RcSchoolActivityJobAuditStatus::Pending);
    }

    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('audit_status', RcSchoolActivityJobAuditStatus::Approved);
    }

    #[Scope]
    protected function rejected(Builder $query): void
    {
        $query->where('audit_status', RcSchoolActivityJobAuditStatus::Rejected);
    }
}
