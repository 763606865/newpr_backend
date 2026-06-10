<?php

namespace App\Resources\SApi;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SApiUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof User) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'uuid' => $this->resource->uuid,
            'name' => $this->resource->name,
            'nickname' => $this->resource->nickname,
            'phone' => $this->resource->phone,
            'email' => $this->resource->email,
            'hub_user_id' => $this->resource->hub_user_id,
            'gender' => $this->resource->gender?->value ?? $this->resource->gender,
            'avatar' => $this->resource->avatar,
            'display_avatar' => $this->resource->display_avatar,
            'status' => $this->resource->status,
            'last_login_ip' => $this->resource->last_login_ip,
            'last_login_at' => $this->resource->last_login_at,
            'extra' => $this->resource->extra,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
