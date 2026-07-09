<?php

namespace App\Models\Rc;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 优惠券核销记录
 *
 * @property int $id
 * @property int $coupon_id
 * @property int $user_id
 * @property int|null $order_id
 * @property Carbon|null $used_at
 * @property array|null $extra
 */
#[Table('rc_coupon_redemptions')]
#[Fillable([
    'coupon_id',
    'user_id',
    'order_id',
    'used_at',
    'extra',
])]
class CouponRedemption extends Model
{
    protected function casts(): array
    {
        return [
            'coupon_id' => 'integer',
            'user_id' => 'integer',
            'order_id' => 'integer',
            'used_at' => 'datetime',
            'extra' => 'array',
        ];
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
