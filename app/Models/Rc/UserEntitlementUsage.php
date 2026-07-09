<?php

namespace App\Models\Rc;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('rc_user_entitlement_usages')]
#[Fillable([
    'entitlement_id',
    'user_id',
    'action',
    'delta',
    'balance_after',
    'related_order_id',
    'extra',
])]
class UserEntitlementUsage extends Model
{
    protected function casts(): array
    {
        return [
            'entitlement_id' => 'integer',
            'user_id' => 'integer',
            'delta' => 'integer',
            'balance_after' => 'integer',
            'related_order_id' => 'integer',
            'extra' => 'array',
        ];
    }

    public function entitlement(): BelongsTo
    {
        return $this->belongsTo(UserEntitlement::class, 'entitlement_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
