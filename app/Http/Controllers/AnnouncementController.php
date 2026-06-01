<?php

namespace App\Http\Controllers;

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
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);
        $perPage = max(1, min($perPage, 50));

        $cityCode = $request->string('city_code')->toString();
        $cityCode = $cityCode !== '' ? $cityCode : null;

        $announcements = Announcement::query()
            ->enabled()
            ->forCity($cityCode)
            ->orderByDesc('is_top')
            ->orderBy('sort')
            ->orderByDesc('published_at')
            ->paginate(
                perPage: $perPage,
                columns: [
                    'id',
                    'city_code',
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
        $cityCode = $request->string('city_code')->toString();
        $cityCode = $cityCode !== '' ? $cityCode : null;

        $announcement = Announcement::query()
            ->enabled()
            ->forCity($cityCode)
            ->whereKey($id)
            ->firstOrFail();

        return api_response($announcement);
    }
}
