<?php

namespace App\Models\Rc;

use App\Enums\RcIdentityType;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class UserIm
 *
 * Represents an IM account bound to a user and optionally a user identity.
 *
 * @package App\Models\Rc
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $user_identity_id
 * @property string $provider
 * @property string|null $app_code
 * @property string|null $external_user_id  Deterministic external id derived from user_identity_id
 * @property string|null $im_user_id        Provider returned IM user id
 * @property array|null $extra
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Rc\UserIdentity|null $userIdentity
 */
#[Table('rc_user_ims')]
#[Fillable([
    'user_id',
    'user_identity_id',
    'identity_type',
    'provider',
    'app_code',
    'external_user_id',
    'im_user_id',
    'extra',
])]
class UserIm extends Model
{
    protected $casts = [
        'extra' => 'array',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'user_identity_id' => 'integer',
            'identity_type' => RcIdentityType::class,
            'extra' => 'array',
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
