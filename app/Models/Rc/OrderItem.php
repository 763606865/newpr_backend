<?php

namespace App\Models\Rc;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('rc_order_items')]
#[Fillable([
    'order_id',
    'item_code',
    'item_name',
    'item_type',
    'unit_price',
    'quantity',
    'line_amount',
    'entitlement_snapshot',
    'extra',
])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'item_type' => 'integer',
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'line_amount' => 'decimal:2',
            'entitlement_snapshot' => 'array',
            'extra' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
