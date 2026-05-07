<?php

namespace App\Resources\Oa;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunicateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'companies' => $this->resource->map(fn($company) => new CompanyResource($company))->all(),
        ];
    }
}
