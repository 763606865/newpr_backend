<?php

namespace App\Resources\Rc;

use App\Models\Rc\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcNotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Notification) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'user_identity_id' => $this->resource->user_identity_id,
            'user_identity_type' => $this->resource->userIdentity?->identity_type?->value,
            'user_identity_type_label' => $this->resource->userIdentity?->identity_type?->getLabel(),
            'type' => $this->resource->type?->value,
            'type_label' => $this->resource->type?->getLabel(),
            'title' => $this->resource->title,
            'body' => $this->resource->body,
            'payload' => $this->resource->payload,
            'is_read' => $this->resource->isRead(),
            'read_at' => $this->resource->read_at,
            'happened_at' => $this->resource->happened_at,
            'created_at' => $this->resource->created_at,
        ];
    }
}
