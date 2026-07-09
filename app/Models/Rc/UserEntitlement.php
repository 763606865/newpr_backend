<?php

namespace App\Models\Rc;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 用户配额/权益（entitlement）
 *
 * @property int $id
 * @property int $user_id
 * @property string $entitlement_type
 * @property int $quantity
 * @property int $remaining
 * @property int|null $plan_id
 * @property string|null $source
 * @property int|null $source_id
 * @property Carbon|null $expires_at
 * @property array|null $extra
 */
#[Table('rc_user_entitlements')]
#[Fillable([
    'user_id',
    'entitlement_type',
    'quantity',
    'remaining',
    'plan_id',
    'source',
    'source_id',
    'expires_at',
    'extra',
])]
class UserEntitlement extends Model
{
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'quantity' => 'integer',
            'remaining' => 'integer',
            'plan_id' => 'integer',
            'expires_at' => 'datetime',
            'extra' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(UserEntitlementUsage::class, 'entitlement_id');
    }
}
