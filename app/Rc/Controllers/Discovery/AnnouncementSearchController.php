<?php

namespace App\Rc\Controllers\Discovery;

use App\Models\Rc\Announcement;
use App\Models\Rc\UserIdentity;
use App\Rc\Controllers\Controller;
use App\Resources\Rc\RcAnnouncementResource;
use App\Services\RcAnnouncementSearchService;
use App\Services\RcIdentityOrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AnnouncementSearchController extends Controller
{
    /**
     * 求职者搜索招聘公告
     *
     * GET /rc/talent/announcements
     */
    public function index(Request $request): JsonResponse
    {
        if (! $this->resolveJobSeekerIdentity() instanceof UserIdentity) {
            return $this->error('请先切换为求职者身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paginator = RcAnnouncementSearchService::make()->search(
            $this->getPerPage($request),
            $request->only([
                'keyword',
                'city_code',
                'employment_type',
                'education_level',
                'graduation_year',
                'major_code',
                'publisher_type',
                'publisher_types',
                'tag_ids',
                'tags_match_all',
                'apply_open',
            ]),
        );

        $paginator->getCollection()->transform(
            fn (Announcement $announcement): array => (new RcAnnouncementResource($announcement))->resolve($request),
        );

        return $this->success($paginator);
    }

    private function resolveJobSeekerIdentity(): ?UserIdentity
    {
        return RcIdentityOrganizationService::make()->resolveJobSeekerIdentity($this->user());
    }
}
