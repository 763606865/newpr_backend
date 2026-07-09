<?php

namespace App\Models\Rc;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * RC 订单
 *
 * @property int $id
 * @property string $order_no
 * @property int $payer_type
 * @property int $payer_id
 * @property int|null $buyer_user_id
 * @property int $scene_type
 * @property string $product_code
 * @property string $product_name
 * @property int $quantity
 * @property float $original_amount
 * @property float $discount_amount
 * @property float $payable_amount
 * @property float $paid_amount
 * @property string $currency
 * @property int $pay_channel
 * @property int $pay_status
 * @property int $order_status
 * @property Carbon|null $expired_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $canceled_at
 * @property array|null $extra
 */
#[Table('rc_orders')]
#[Fillable([
    'order_no',
    'payer_type',
    'payer_id',
    'buyer_user_id',
    'scene_type',
    'product_code',
    'product_name',
    'quantity',
    'original_amount',
    'discount_amount',
    'payable_amount',
    'paid_amount',
    'currency',
    'pay_channel',
    'pay_status',
    'order_status',
    'expired_at',
    'paid_at',
    'canceled_at',
    'extra',
])]
class Order extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'payer_type' => 'integer',
            'payer_id' => 'integer',
            'buyer_user_id' => 'integer',
            'scene_type' => 'integer',
            'quantity' => 'integer',
            'original_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'payable_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'currency' => 'string',
            'pay_channel' => 'integer',
            'pay_status' => 'integer',
            'order_status' => 'integer',
            'expired_at' => 'datetime',
            'paid_at' => 'datetime',
            'canceled_at' => 'datetime',
            'extra' => 'array',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
