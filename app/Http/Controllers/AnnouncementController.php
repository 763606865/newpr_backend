<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnnouncementIndexRequest;
use App\Models\Cms\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * 公告页内容（分页）
     *
     * GET /announcements
     *
     * @throws \Exception
     */
    public function index(AnnouncementIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $perPage = (int) ($validated['per_page'] ?? 15);
        $perPage = max(1, min($perPage, 50));

        $regionCode = $request->regionCode();

        $query = Announcement::query()
            ->enabled()
            ->forRegion($regionCode);

        $tagIds = $request->tagIds();

        if ($tagIds !== []) {
            $query->withTags($tagIds, $request->tagsMatchAll());
        }

        $publisherTypes = $request->publisherTypes();

        if ($publisherTypes !== []) {
            $query->forPublisherTypes($publisherTypes);
        }

        $announcements = $query
            ->orderByDesc('is_top')
            ->orderBy('sort')
            ->orderByDesc('published_at')
            ->paginate(
                perPage: $perPage,
                columns: [
                    'id',
                    'province_code',
                    'city_code',
                    'district_code',
                    'title',
                    'sub_title',
                    'summary',
                    'link_url',
                    'type',
                    'source_name',
                    'published_at',
                    'is_top',
                ],
            );

        return api_response($announcements);
    }

    /**
     * 公告详情
     *
     * GET /announcements/{id}
     *
     * @throws \Exception
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $regionCode = $this->resolveRegionCode($request);

        $announcement = Announcement::query()
            ->enabled()
            ->forRegion($regionCode)
            ->whereKey($id)
            ->firstOrFail();

        return api_response($announcement);
    }

    private function resolveRegionCode(Request $request): ?string
    {
        $districtCode = $request->string('district_code')->toString();

        if ($districtCode !== '') {
            return $districtCode;
        }

        $cityCode = $request->string('city_code')->toString();

        if ($cityCode !== '') {
            return $cityCode;
        }

        $provinceCode = $request->string('province_code')->toString();

        if ($provinceCode !== '') {
            return $provinceCode;
        }

        return null;
    }
}
