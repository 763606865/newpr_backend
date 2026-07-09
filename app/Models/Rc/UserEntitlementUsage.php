<?php

namespace App\Models\Rc;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 用户配额使用记录
 *
 * @property int $id
 * @property int $entitlement_id
 * @property int $user_id
 * @property string $action
 * @property int $delta
 * @property int|null $balance_after
 * @property int|null $related_order_id
 * @property array|null $extra
 */
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
