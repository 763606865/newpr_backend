<?php

namespace App\Resources\Rc;

use App\Models\ImInteractionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ImInteractionRequest
 */
class ImInteractionRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_user_im_id' => $this->sender_user_im_id,
            'receiver_user_im_id' => $this->receiver_user_im_id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->getLabel(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->getLabel(),
            'payload' => $this->payload,
            'result_payload' => $this->result_payload,
            'responded_at' => $this->responded_at,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
