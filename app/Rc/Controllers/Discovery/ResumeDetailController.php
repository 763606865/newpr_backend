<?php

namespace App\Rc\Controllers\Discovery;

use App\Models\Company;
use App\Models\Rc\Resume;
use App\Models\User;
use App\Rc\Controllers\Controller;
use App\Resources\Rc\RcResumePreviewResource;
use App\Services\RcJobService;
use App\Services\RcResumeDiscoveryService;
use App\Services\RcViewStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ResumeDetailController extends Controller
{
    /**
     * 招聘方查看候选人简历详情（脱敏）
     *
     * GET /rc/talent/resumes/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $resume = RcResumeDiscoveryService::make()->findDiscoverableResume($id, withDetails: true);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在或不可查看。', Response::HTTP_NOT_FOUND);
        }

        /** @var User $viewer */
        $viewer = $this->user();

        RcViewStatsService::make()->recordResumeView($resume, $viewer);

        return $this->success((new RcResumePreviewResource($resume))->resolve($request));
    }

    private function resolveCompany(): ?Company
    {
        return RcJobService::make()->resolveRecruiterCompany($this->user());
    }
}
