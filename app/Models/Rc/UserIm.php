<?php

namespace App\Models\Rc;

use App\Enums\RcIdentityType;
use App\Models\ImConversation;
use App\Models\ImConversationMember;
use App\Models\ImQuickPhrase;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;

/**
 * Class UserIm
 *
 * Represents an IM account bound to a user and optionally a user identity.
 *
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $user_identity_id
 * @property string $provider
 * @property string|null $app_code
 * @property string|null $external_user_id Deterministic external id derived from user_identity_id
 * @property string|null $im_user_id Provider returned IM user id
 * @property array|null $extra
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read UserIdentity|null $userIdentity
 * @property-read Collection<int, ImConversation> $conversations
 * @property-read Collection<int, ImConversationMember> $conversationMembers
 * @property-read Collection<int, ImConversation> $memberConversations
 * @property-read Collection<int, ImQuickPhrase> $quickPhrases
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

    public function quickPhrases(): HasMany
    {
        return $this->hasMany(ImQuickPhrase::class, 'user_im_id');
    }
}
