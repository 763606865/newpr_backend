<?php

namespace App\Models\Rc;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 用户 VIP / 增值权益
 *
 * @property int $id
 * @property int $user_id
 * @property int $vip_level
 * @property int $refresh_quota
 * @property int $exposure_quota
 * @property Carbon|null $last_refresh_at
 * @property int|null $plan_id
 * @property Carbon|null $expires_at
 * @property array|null $extra
 */
#[Table('rc_user_vips')]
#[Fillable([
    'user_id',
    'vip_level',
    'refresh_quota',
    'exposure_quota',
    'last_refresh_at',
    'plan_id',
    'expires_at',
    'extra',
])]
class UserVip extends Model
{
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'vip_level' => 'integer',
            'refresh_quota' => 'integer',
            'exposure_quota' => 'integer',
            'last_refresh_at' => 'datetime',
            'plan_id' => 'integer',
            'expires_at' => 'datetime',
            'extra' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
