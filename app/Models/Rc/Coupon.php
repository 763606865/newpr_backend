<?php

namespace App\Models\Rc;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
