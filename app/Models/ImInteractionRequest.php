<?php

namespace App\Models;

use App\Enums\ImInteractionRequestStatus;
use App\Enums\ImInteractionRequestType;
use App\Models\Rc\UserIm;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * IM 交互请求表
 *
 * @property int $id
 * @property int $conversation_id
 * @property int $sender_user_im_id
 * @property int $receiver_user_im_id
 * @property ImInteractionRequestType $type
 * @property ImInteractionRequestStatus $status
 * @property array<string, mixed>|null $payload
 * @property array<string, mixed>|null $result_payload
 * @property Carbon|null $responded_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ImConversation $conversation
 * @property-read UserIm $senderUserIm
 * @property-read UserIm $receiverUserIm
 */
#[Table('im_interaction_requests')]
#[Fillable([
    'conversation_id',
    'sender_user_im_id',
    'receiver_user_im_id',
    'type',
    'status',
    'payload',
    'result_payload',
    'responded_at',
    'expires_at',
])]
class ImInteractionRequest extends Model
{
    protected function casts(): array
    {
        return [
            'conversation_id' => 'integer',
            'sender_user_im_id' => 'integer',
            'receiver_user_im_id' => 'integer',
            'type' => ImInteractionRequestType::class,
            'status' => ImInteractionRequestStatus::class,
            'payload' => 'array',
            'result_payload' => 'array',
            'responded_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ImConversation::class, 'conversation_id');
    }

    public function senderUserIm(): BelongsTo
    {
        return $this->belongsTo(UserIm::class, 'sender_user_im_id');
    }

    public function receiverUserIm(): BelongsTo
    {
        return $this->belongsTo(UserIm::class, 'receiver_user_im_id');
    }
}
