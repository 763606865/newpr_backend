<?php

namespace App\Resources\Rc;

use App\Models\ImQuickPhrase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImQuickPhraseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof ImQuickPhrase) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'user_im_id' => $this->resource->user_im_id,
            'title' => $this->resource->title,
            'content' => $this->resource->content,
            'sort' => $this->resource->sort,
            'is_enabled' => $this->resource->is_enabled,
            'used_count' => $this->resource->used_count,
            'last_used_at' => $this->resource->last_used_at,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
