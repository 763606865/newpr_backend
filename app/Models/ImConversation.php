<?php

namespace App\Models;

use App\Enums\ImConversationType;
use App\Models\Rc\UserIm;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;

/**
 * IM 会话表
 *
 * @property int $id
 * @property string|null $provider
 * @property string|null $app_code
 * @property string $conversation_no
 * @property ImConversationType|null $conversation_type
 * @property string|null $conversation_key
 * @property string $owner_type
 * @property int $owner_id
 * @property string|null $context_type
 * @property int|null $context_id
 * @property string|null $scene
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $last_message_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|null $owner
 * @property-read Model|null $context
 * @property-read Collection<int, ImConversationMember> $members
 * @property-read Collection<int, UserIm> $userImMembers
 */
#[Table('im_conversations')]
#[Fillable([
    'provider',
    'app_code',
    'conversation_no',
    'conversation_type',
    'conversation_key',
    'owner_type',
    'owner_id',
    'context_type',
    'context_id',
    'scene',
    'metadata',
    'last_message_at',
    'expires_at',
])]
class ImConversation extends Model
{
    protected function casts(): array
    {
        return [
            'conversation_type' => ImConversationType::class,
            'owner_id' => 'integer',
            'context_id' => 'integer',
            'metadata' => 'array',
            'last_message_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function context(): MorphTo
    {
        return $this->morphTo();
    }

    public function members(): HasMany
    {
        return $this->hasMany(ImConversationMember::class, 'conversation_id');
    }

    public function userImMembers(): MorphToMany
    {
        return $this->morphedByMany(UserIm::class, 'member', 'im_conversation_members', 'conversation_id', 'member_id')
            ->withPivot(['role', 'joined_at', 'last_read_at', 'settings'])
            ->withTimestamps();
    }

    /**
     * 通过 conversation_no 查找
     */
    public static function findByConversationNo(string $no): ?self
    {
        return static::query()->where('conversation_no', $no)->first();
    }
}
