<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * IM 会话成员表
 *
 * @property int $id
 * @property int $conversation_id
 * @property string $member_type
 * @property int $member_id
 * @property string|null $role
 * @property Carbon|null $joined_at
 * @property Carbon|null $last_read_at
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ImConversation $conversation
 * @property-read Model|null $member
 */
#[Table('im_conversation_members')]
#[Fillable([
    'conversation_id',
    'member_type',
    'member_id',
    'role',
    'joined_at',
    'last_read_at',
    'settings',
])]
class ImConversationMember extends Model
{
    protected function casts(): array
    {
        return [
            'conversation_id' => 'integer',
            'member_id' => 'integer',
            'joined_at' => 'datetime',
            'last_read_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ImConversation::class, 'conversation_id');
    }

    public function member(): MorphTo
    {
        return $this->morphTo();
    }
}
