<?php

namespace App\Resources\Rc;

use App\Models\Rc\ApplicationFlow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcApplicationFlowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof ApplicationFlow) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'company_id' => $this->resource->company_id,
            'application_id' => $this->resource->application_id,
            'from_stage_id' => $this->resource->from_stage_id,
            'to_stage_id' => $this->resource->to_stage_id,
            'action_type' => $this->resource->action_type?->value,
            'action_type_label' => $this->resource->action_type?->getLabel(),
            'operator_user_id' => $this->resource->operator_user_id,
            'note' => $this->resource->note,
            'happened_at' => $this->resource->happened_at,
            'extra' => $this->resource->extra,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
