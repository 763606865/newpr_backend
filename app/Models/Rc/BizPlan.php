<?php

namespace App\Models\Rc;

use App\Enums\RcBizPlanBillingCycle;
use App\Enums\RcBizPlanProductType;
use App\Enums\RcBizPlanStatus;
use App\Enums\RcBizPlanTargetSide;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * RC 商业化方案（BizPlan）
 *
 * @property int $id
 * @property string $plan_name
 * @property string $plan_code
 * @property float $price
 * @property int $duration
 * @property RcBizPlanTargetSide $target_side
 * @property RcBizPlanProductType $product_type
 * @property RcBizPlanBillingCycle $billing_cycle
 * @property array|null $quota_rules
 * @property array|null $extra
 * @property RcBizPlanStatus $status
 */
#[Table('rc_biz_plans')]
#[Fillable([
    'plan_name',
    'plan_code',
    'price',
    'duration',
    'target_side',
    'product_type',
    'billing_cycle',
    'sort',
    'remark',
    'quota_rules',
    'status',
    'extra',
])]
class BizPlan extends Model
{
    protected $attributes = [
        'price' => 0,
        'duration' => 0,
        'target_side' => RcBizPlanTargetSide::Recruiter,
        'product_type' => RcBizPlanProductType::JobPosting,
        'billing_cycle' => RcBizPlanBillingCycle::OneTime,
        'sort' => 0,
        'status' => RcBizPlanStatus::Enabled,
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration' => 'integer',
            'target_side' => RcBizPlanTargetSide::class,
            'product_type' => RcBizPlanProductType::class,
            'billing_cycle' => RcBizPlanBillingCycle::class,
            'sort' => 'integer',
            'quota_rules' => 'array',
            'status' => RcBizPlanStatus::class,
            'extra' => 'array',
        ];
    }
}
