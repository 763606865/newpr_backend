<?php

namespace App\Models;

use App\Enums\RcIdentityType;
use App\Models\Rc\UserIdentity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class UserThirdPartyAccount
 *
 * Represents a third-party account binding for a user (WeChat, ecosystem SSO, etc.),
 * optionally scoped to a specific user identity.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $user_identity_id
 * @property RcIdentityType|null $identity_type
 * @property string $provider
 * @property string|null $app_code
 * @property string|null $open_id
 * @property string|null $union_id
 * @property string|null $external_user_id
 * @property array|null $extra
 * @property Carbon|null $bound_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read UserIdentity|null $userIdentity
 */
#[Table('user_third_party_accounts')]
#[Fillable([
    'user_id',
    'user_identity_id',
    'identity_type',
    'provider',
    'app_code',
    'open_id',
    'union_id',
    'external_user_id',
    'extra',
    'bound_at',
])]
class UserThirdPartyAccount extends Model
{
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'user_identity_id' => 'integer',
            'identity_type' => RcIdentityType::class,
            'extra' => 'array',
            'bound_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function userIdentity(): BelongsTo
    {
        return $this->belongsTo(UserIdentity::class, 'user_identity_id');
    }
}
