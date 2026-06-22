<?php

namespace App\Models\Rc;

use App\Enums\RcSchoolActivityApplyStatus;
use App\Enums\RcSchoolActivityJoinSource;
use App\Models\Company;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 活动关联企业表
 *
 * @property int $id 主键ID
 * @property int $activity_id 活动ID
 * @property int $company_id 企业ID
 * @property int|null $activity_booth_id 活动展位ID
 * @property RcSchoolActivityJoinSource $join_source 报名来源
 * @property RcSchoolActivityApplyStatus $apply_status 申请状态
 * @property Carbon $apply_at 报名提交时间
 * @property string|null $remark 报名备注、院校审核意见
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read SchoolActivity $activity 所属活动
 * @property-read Company $company 参会企业
 * @property-read SchoolActivityBooth|null $activityBooth 分配的活动展位
 *
 * @method static Builder pending()
 * @method static Builder approved()
 * @method static Builder rejected()
 */
#[Table('rc_school_activity_companies')]
#[Fillable([
    'activity_id',
    'company_id',
    'activity_booth_id',
    'join_source',
    'apply_status',
    'apply_at',
    'remark',
])]
class SchoolActivityCompany extends Model
{
    protected $attributes = [
        'apply_status' => RcSchoolActivityApplyStatus::Pending,
        'join_source' => RcSchoolActivityJoinSource::CompanyApply,
    ];

    protected static function booted(): void
    {
        static::creating(function (SchoolActivityCompany $model): void {
            if ($model->apply_at === null) {
                $model->apply_at = now();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'activity_id' => 'integer',
            'company_id' => 'integer',
            'activity_booth_id' => 'integer',
            'join_source' => RcSchoolActivityJoinSource::class,
            'apply_status' => RcSchoolActivityApplyStatus::class,
            'apply_at' => 'datetime',
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

    public function activityBooth(): BelongsTo
    {
        return $this->belongsTo(SchoolActivityBooth::class, 'activity_booth_id');
    }

    public function activityJobs(): HasMany
    {
        return $this->hasMany(SchoolActivityJob::class, 'school_activity_company_id');
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('apply_status', RcSchoolActivityApplyStatus::Pending);
    }

    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('apply_status', RcSchoolActivityApplyStatus::Approved);
    }

    #[Scope]
    protected function rejected(Builder $query): void
    {
        $query->where('apply_status', RcSchoolActivityApplyStatus::Rejected);
    }
}
