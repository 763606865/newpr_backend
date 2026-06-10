<?php

namespace App\SApi\Controllers;

use App\Models\Cms\Announcement;
use App\Resources\SApi\SApiAnnouncementResource;
use App\SApi\Requests\AnnouncementIndexRequest;
use Illuminate\Http\JsonResponse;

class AnnouncementController extends Controller
{
    /**
     * 拉取公告列表
     *
     * GET /sapi/announcements
     */
    public function index(AnnouncementIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $cityCode = isset($validated['city_code']) && $validated['city_code'] !== ''
            ? (string) $validated['city_code']
            : null;

        $query = Announcement::query()
            ->enabled()
            ->forCity($cityCode)
            ->createdBetween(
                $validated['created_from'] ?? null,
                $validated['created_to'] ?? null,
            )
            ->orderByDesc('id');

        return $this->indexDataResponse(
            $request,
            $query,
            fn (Announcement $announcement) => (new SApiAnnouncementResource($announcement))->resolve($request),
        );
    }
}
