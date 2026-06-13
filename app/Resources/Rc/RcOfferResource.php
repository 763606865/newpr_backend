<?php

namespace App\Resources\Rc;

use App\Models\Rc\Offer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcOfferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Offer) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'offer_no' => $this->resource->offer_no,
            'salary' => $this->resource->salary,
            'salary_unit' => $this->resource->salary_unit?->value,
            'salary_unit_label' => $this->resource->salary_unit?->getLabel(),
            'has_probation' => $this->resource->has_probation,
            'remuneration_note' => $this->resource->remuneration_note,
            'attendance_note' => $this->resource->attendance_note,
            'entry_date' => $this->resource->entry_date,
            'expire_date' => $this->resource->expire_date,
            'status' => $this->resource->status?->value,
            'status_label' => $this->resource->status?->getLabel(),
            'sent_at' => $this->resource->sent_at,
            'replied_at' => $this->resource->replied_at,
            'note' => $this->resource->note,
            'extra' => $this->resource->extra,
            'email_sent_at' => $this->resource->email_sent_at,
            'sms_sent_at' => $this->resource->sms_sent_at,
        ];
    }
}
