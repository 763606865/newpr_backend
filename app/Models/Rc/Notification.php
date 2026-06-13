<?php

namespace App\Models\Rc;

use App\Enums\RcNotificationType;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 招聘站内通知表
 *
 * @property int $id 主键ID
 * @property int $user_id 接收用户ID
 * @property int|null $user_identity_id 接收身份ID
 * @property RcNotificationType $type 通知类型
 * @property string $title 通知标题
 * @property string|null $body 通知摘要
 * @property array<string, mixed>|null $payload 业务扩展数据
 * @property Carbon|null $read_at 已读时间
 * @property Carbon|null $happened_at 事件发生时间
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read User $user 接收用户
 * @property-read UserIdentity|null $userIdentity 接收身份
 *
 * @method static Builder unread()
 * @method static Builder visibleToIdentity(?UserIdentity $identity)
 */
#[Table('rc_notifications')]
#[Fillable([
    'user_id',
    'user_identity_id',
    'type',
    'title',
    'body',
    'payload',
    'read_at',
    'happened_at',
])]
class Notification extends Model
{
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'user_identity_id' => 'integer',
            'type' => RcNotificationType::class,
            'payload' => 'array',
            'read_at' => 'datetime',
            'happened_at' => 'datetime',
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

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * 当前身份可见：用户级通知（user_identity_id 为 null）或绑定到指定身份的记录。
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisibleToIdentity(Builder $query, ?UserIdentity $identity): Builder
    {
        if (! $identity instanceof UserIdentity) {
            return $query->whereNull('user_identity_id');
        }

        return $query->where(function (Builder $query) use ($identity): void {
            $query->whereNull('user_identity_id')
                ->orWhere('user_identity_id', $identity->id);
        });
    }
}
