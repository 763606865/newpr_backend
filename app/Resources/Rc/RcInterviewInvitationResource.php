<?php

namespace App\Resources\Rc;

use App\Models\Rc\Interview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcInterviewInvitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Interview) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'interview_at' => $this->resource->interview_at,
            'duration_mins' => $this->resource->duration_mins,
            'mode' => $this->resource->mode?->value,
            'mode_label' => $this->resource->mode?->getLabel(),
            'interviewer_name' => $this->resource->interviewer_name,
            'location' => $this->resource->location,
            'meeting_url' => $this->resource->meeting_url,
            'note' => $this->resource->note,
            'status' => $this->resource->status?->value,
            'status_label' => $this->resource->status?->getLabel(),
        ];
    }
}
