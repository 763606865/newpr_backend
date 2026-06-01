<?php

namespace App\Resources\Cms;

use App\Models\Cms\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class CmsMenuCollection extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Collection) {
            return [];
        }

        $payloads = CmsMenuResource::collection(
            $this->resource
                ->filter(fn (mixed $menu): bool => $menu instanceof Menu)
                ->sortBy([
                    ['sort', 'asc'],
                    ['id', 'asc'],
                ])
                ->values(),
        )->resolve($request);

        return tree($payloads);
    }
}
