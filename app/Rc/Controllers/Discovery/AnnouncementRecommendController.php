<?php

namespace App\Rc\Controllers\Discovery;

use App\Discovery\Recommendation\AnnouncementRecommendationContext;
use App\Models\Rc\Announcement;
use App\Models\User;
use App\Rc\Controllers\Controller;
use App\Resources\Rc\RcAnnouncementResource;
use App\Services\RcAnnouncementRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementRecommendController extends Controller
{
    /**
     * 求职者招聘公告推荐（支持未登录访问）
     *
     * GET /rc/talent/announcements/recommend
     */
    public function index(Request $request): JsonResponse
    {
        $result = RcAnnouncementRecommendationService::make()->recommend(
            new AnnouncementRecommendationContext(
                user: $this->optionalUser(),
                cityHint: $request->input('city_code') ?? $request->header('X-City-Code'),
            ),
            $this->getPerPage($request),
        );

        $paginator = $result['paginator'];
        $paginator->getCollection()->transform(
            static fn (Announcement $announcement): array => (new RcAnnouncementResource($announcement))->resolve($request),
        );

        $payload = $paginator->toArray();
        $payload['recommendation'] = $result['criteria']->toRecommendationMeta();

        return $this->success($payload);
    }

    private function optionalUser(): ?User
    {
        $user = auth()->guard('rc')->user();

        return $user instanceof User ? $user : null;
    }
}
