<?php

namespace App\Models\Rc;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('rc_payment_transactions')]
#[Fillable([
    'order_id',
    'payment_no',
    'channel',
    'status',
    'amount',
    'currency',
    'provider_trade_no',
    'request_payload',
    'response_payload',
    'expired_at',
    'paid_at',
])]
class PaymentTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'channel' => 'integer',
            'status' => 'integer',
            'amount' => 'decimal:2',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'expired_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
