<?php

namespace App\Models\Rc;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 优惠券
 *
 * @property int $id
 * @property string $code
 * @property string|null $title
 * @property int|null $plan_id
 * @property string $discount_type
 * @property float|null $discount_value
 * @property int $usage_limit
 * @property int|null $total_quantity
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property int $status
 * @property array|null $extra
 */
#[Table('rc_coupons')]
#[Fillable([
    'code',
    'title',
    'plan_id',
    'discount_type',
    'discount_value',
    'usage_limit',
    'total_quantity',
    'starts_at',
    'expires_at',
    'status',
    'extra',
])]
class Coupon extends Model
{
    protected function casts(): array
    {
        return [
            'plan_id' => 'integer',
            'discount_value' => 'decimal:2',
            'usage_limit' => 'integer',
            'total_quantity' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'status' => 'integer',
            'extra' => 'array',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class, 'coupon_id');
    }
}
