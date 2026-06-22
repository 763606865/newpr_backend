<?php

namespace App\Models\Rc;

use App\Enums\RcSchoolBoothStatus;
use App\Models\Company;
use App\Models\Model;
use App\Models\School;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 活动展位表
 *
 * @property int $id 主键ID
 * @property int $activity_id 活动ID
 * @property int $booth_id 展位模板ID
 * @property int|null $school_id 学校ID
 * @property int|null $company_id 企业ID
 * @property int|null $booth_area_id 展区ID
 * @property string $booth_area_code 展区Code快照
 * @property string $booth_area_name 展区名称快照
 * @property string $booth_no 展位编号
 * @property string|null $price 价格
 * @property Carbon|null $start_at 生效开始时间
 * @property Carbon|null $end_at 生效结束时间
 * @property RcSchoolBoothStatus $status 状态
 * @property string|null $remark 备注
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read SchoolActivity $activity 所属活动
 * @property-read SchoolBooth $booth 展位模板
 * @property-read School|null $school 关联学校
 * @property-read Company|null $company 关联企业
 * @property-read SchoolBoothArea|null $boothArea 关联展区
 *
 * @method static Builder enabled()
 * @method static Builder disabled()
 * @method static Builder forActivity(int $activityId)
 * @method static Builder available()
 */
#[Table('rc_school_activity_booths')]
#[Fillable([
    'activity_id',
    'booth_id',
    'school_id',
    'company_id',
    'booth_area_id',
    'booth_area_code',
    'booth_area_name',
    'booth_no',
    'price',
    'start_at',
    'end_at',
    'status',
    'remark',
])]
class SchoolActivityBooth extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'status' => RcSchoolBoothStatus::Enabled,
    ];

    protected function casts(): array
    {
        return [
            'activity_id' => 'integer',
            'booth_id' => 'integer',
            'school_id' => 'integer',
            'company_id' => 'integer',
            'booth_area_id' => 'integer',
            'price' => 'decimal:2',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'status' => RcSchoolBoothStatus::class,
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(SchoolActivity::class, 'activity_id');
    }

    public function booth(): BelongsTo
    {
        return $this->belongsTo(SchoolBooth::class, 'booth_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function boothArea(): BelongsTo
    {
        return $this->belongsTo(SchoolBoothArea::class, 'booth_area_id');
    }

    public function companyApplications(): HasMany
    {
        return $this->hasMany(SchoolActivityCompany::class, 'activity_booth_id');
    }

    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where('status', RcSchoolBoothStatus::Enabled);
    }

    #[Scope]
    protected function disabled(Builder $query): void
    {
        $query->where('status', RcSchoolBoothStatus::Disabled);
    }

    #[Scope]
    protected function forActivity(Builder $query, int $activityId): void
    {
        $query->where('activity_id', $activityId);
    }

    #[Scope]
    protected function available(Builder $query): void
    {
        $query->whereNull('company_id');
    }
}
