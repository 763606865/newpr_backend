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
        $regionCode = $request->regionCode();

        $query = Announcement::query()
            ->with('tags')
            ->enabled()
            ->forRegion($regionCode)
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
