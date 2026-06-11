<?php

namespace App\Rc\Controllers\Discovery;

use App\Models\Rc\Job;
use App\Models\User;
use App\Rc\Controllers\Controller;
use App\Resources\Rc\RcJobResource;
use App\Services\RcApplicationService;
use App\Services\RcJobDiscoveryService;
use App\Services\RcJobFavoriteService;
use App\Services\RcViewStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class JobDetailController extends Controller
{
    /**
     * 求职者查看职位详情（支持未登录访问）
     *
     * GET /rc/talent/jobs/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $job = RcJobDiscoveryService::make()->findPublicJob($id);

        if (! $job instanceof Job) {
            return $this->error('职位不存在或已下架。', Response::HTTP_NOT_FOUND);
        }

        $viewer = auth()->guard('rc')->user();
        RcViewStatsService::make()->recordJobView(
            $job,
            $viewer instanceof User ? $viewer : null,
        );

        $data = (new RcJobResource($job))->resolve($request);

        if ($viewer instanceof User) {
            $data['is_applied'] = RcApplicationService::make()->hasUserAppliedToJob($viewer, $job);
            $data['is_favorited'] = RcJobFavoriteService::make()->isFavorited($viewer, $job->id);
        }

        return $this->success($data);
    }
}
