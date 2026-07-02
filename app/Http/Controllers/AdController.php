<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdIndexRequest;
use App\Models\Cms\Ad;
use App\Models\Cms\AdSlot;
use Illuminate\Http\JsonResponse;

class AdController extends Controller
{
    /**
     * 按广告位编码获取广告
     *
     * GET /cms/ads?code={ad_slot.code}
     *
     * @throws \Exception
     */
    public function index(AdIndexRequest $request): JsonResponse
    {
        $adSlot = AdSlot::query()
            ->enabled()
            ->where('code', '=', $request->slotCode())
            ->with([
                'ads' => fn ($query) => $query
                    ->enabled()
                    ->forCity($request->cityCode())
                    ->orderBy('sort')
                    ->orderBy('id'),
            ])
            ->firstOrFail();

        return $this->success([
            'ad_slot' => $this->serializeAdSlot($adSlot),
            'ads' => $adSlot->ads
                ->map(fn (Ad $ad): array => $this->serializeAd($ad))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAdSlot(AdSlot $adSlot): array
    {
        return [
            'id' => $adSlot->id,
            'name' => $adSlot->name,
            'code' => $adSlot->code,
            'type' => $adSlot->type?->value,
            'type_label' => $adSlot->type?->getLabel(),
            'width' => $adSlot->width,
            'height' => $adSlot->height,
            'remark' => $adSlot->remark,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAd(Ad $ad): array
    {
        $image = $ad->getAttributes()['image'] ?? null;

        return [
            'id' => $ad->id,
            'slot_id' => $ad->slot_id,
            'city_code' => $ad->city_code,
            'title' => $ad->title,
            'type' => $ad->type?->value,
            'type_label' => $ad->type?->getLabel(),
            'image' => is_string($image) && $image !== '' ? ltrim($image, '/') : null,
            'image_url' => $ad->image_url,
            'text_content' => $ad->text_content,
            'code_content' => $ad->code_content,
            'link_url' => $ad->link_url,
            'start_at' => $ad->start_at,
            'end_at' => $ad->end_at,
            'sort' => $ad->sort,
            'extra' => $ad->extra,
        ];
    }
}
