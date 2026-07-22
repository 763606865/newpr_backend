<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;

/**
 * IM 系统用户表
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $provider
 * @property string $app_code
 * @property string $external_user_id
 * @property string|null $im_user_id
 * @property string|null $avatar
 * @property bool $is_active
 * @property array<string, mixed>|null $extra
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ImConversation> $conversations
 * @property-read Collection<int, ImConversationMember> $conversationMembers
 * @property-read Collection<int, ImConversation> $memberConversations
 *
 * @method static Builder active()
 */
#[Table('im_system_users')]
#[Fillable([
    'code',
    'name',
    'provider',
    'app_code',
    'external_user_id',
    'im_user_id',
    'avatar',
    'is_active',
    'extra',
])]
class ImSystemUser extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'extra' => 'array',
        ];
    }

    public function conversations(): MorphMany
    {
        return $this->morphMany(ImConversation::class, 'owner');
    }

    public function conversationMembers(): MorphMany
    {
        return $this->morphMany(ImConversationMember::class, 'member');
    }

    public function memberConversations(): MorphToMany
    {
        return $this->morphToMany(ImConversation::class, 'member', 'im_conversation_members', 'member_id', 'conversation_id')
            ->withPivot(['role', 'joined_at', 'last_read_at', 'settings'])
            ->withTimestamps();
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where($this->getTable().'.is_active', '=', true);
    }
}
