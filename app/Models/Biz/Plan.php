<?php

namespace App\Models\Biz;

use App\Enums\SystemPlanStatus;
use App\Models\Client\Feature;
use App\Models\CompanyPlan;
use App\Models\Model;
use App\Models\ShipCompanyPlan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 企业方案模型
 *
 * @property int $id
 * @property string $plan_name 方案名称
 * @property string $plan_code 唯一标识
 * @property float $price 方案价格
 * @property int $duration 方案时长(天) 0=永久
 * @property int $sort 排序
 * @property string|null $remark 方案描述
 * @property int $status 0=未开始 1=进行中 2=已完成 3=已取消
 * @property string|null $extra 其他扩展属性
 */
#[Table('biz_plans')]
#[Fillable(['plan_name', 'plan_code', 'price', 'duration', 'sort', 'remark', 'status', 'extra'])]
class Plan extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'status' => SystemPlanStatus::class,
            'extra' => 'array',
        ];
    }

    /**
     * 关联的功能点
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, PlanFeature::class, 'plan_id', 'feature_id');
    }

    /**
     * 企业当前方案关联
     */
    public function companyPlans(): HasMany
    {
        return $this->hasMany(CompanyPlan::class, 'plan_id');
    }

    /**
     * 企业历史方案关联
     */
    public function shipCompanyPlans(): HasMany
    {
        return $this->hasMany(ShipCompanyPlan::class, 'plan_id');
    }

    /**
     * 是否永久方案
     */
    public function isPermanent(): bool
    {
        return $this->duration === 0;
    }

    /**
     * 是否进行中
     */
    public function isActive(): bool
    {
        return $this->status === 1;
    }
}
