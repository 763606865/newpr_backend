<?php

namespace App\Rc\Controllers\Discovery;

use App\Models\Rc\Announcement;
use App\Rc\Controllers\Controller;
use App\Resources\Rc\RcAnnouncementResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AnnouncementDetailController extends Controller
{
    /**
     * 求职者查看招聘公告详情（支持未登录访问）
     *
     * GET /rc/talent/announcements/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $announcement = Announcement::query()
            ->published()
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('expired_at')
                    ->orWhere('expired_at', '>=', now());
            })
            ->with(Announcement::discoveryRelations())
            ->find($id);

        if (! $announcement instanceof Announcement) {
            return $this->error('招聘公告不存在或已下架。', Response::HTTP_NOT_FOUND);
        }

        $data = (new RcAnnouncementResource($announcement))->resolve($request);
        $data['content'] = $announcement->content;

        return $this->success($data);
    }
}
