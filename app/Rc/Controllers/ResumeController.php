<?php

namespace App\Rc\Controllers;

use App\Enums\RcResumeRefreshTrigger;
use App\Models\Rc\Resume;
use App\Models\User;
use App\Rc\Requests\ResumeAttachmentStoreRequest;
use App\Rc\Requests\ResumeAvatarUploadRequest;
use App\Rc\Requests\ResumeStoreRequest;
use App\Rc\Requests\ResumeUpdateRequest;
use App\Resources\Rc\RcResumeResource;
use App\Services\RcResumeRefreshService;
use App\Services\RcViewStatsService;
use App\Services\ResumeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ResumeController extends Controller
{
    /**
     * 简历列表
     *
     * GET /rc/resumes
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        $resumes = $user->resumes()
            ->withCount('applications')
            ->orderByDesc('is_primary')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($this->getPerPage($request));

        $viewTotals = RcViewStatsService::make()->getResumeTotalViewsForIds(
            $resumes->getCollection()->pluck('id')->map(fn (mixed $id): int => (int) $id)->all(),
        );

        $resumes->getCollection()->transform(
            function (Resume $resume) use ($request, $viewTotals): array {
                $data = (new RcResumeResource($resume))->resolve($request);
                $data['stats'] = [
                    'views' => $viewTotals[(int) $resume->id] ?? 0,
                    'applications' => (int) $resume->applications_count,
                ];

                return $data;
            },
        );

        return $this->success($resumes);
    }

    /**
     * 简历详情
     *
     * GET /rc/resumes/{id}
     *
     * @throws \Exception
     */
    public function show(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        $resume = Resume::query()
            ->where('user_id', $user->id)
            ->find($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        RcViewStatsService::make()->recordResumeView($resume, $user);

        return $this->success((new RcResumeResource($resume))->resolve($request));
    }

    /**
     * 新增简历
     *
     * POST /rc/resumes
     *
     * @throws \Exception
     */
    public function store(ResumeStoreRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        $payload = $request->validated();

        $resume = DB::transaction(function () use ($payload, $user): Resume {
            $isPrimary = (int) ($payload['is_primary'] ?? 0) === 1;
            $hasPrimaryResume = Resume::query()
                ->where('user_id', $user->id)
                ->where('is_primary', 1)
                ->exists();

            $resume = new Resume;
            $resume->fill($payload);
            $resume->user_id = $user->id;
            $resume->is_primary = ($isPrimary || ! $hasPrimaryResume) ? 1 : 0;
            $resume->save();

            if ($resume->is_primary === 1) {
                ResumeService::make()->promote($user, $resume);
            }

            return $resume;
        });

        return $this->success((new RcResumeResource($resume->fresh()))->resolve($request));
    }

    /**
     * 主动刷新简历
     *
     * POST /rc/resumes/{id}/refresh
     */
    public function refresh(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();
        $resume = Resume::query()
            ->where('user_id', $user->id)
            ->find($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        try {
            RcResumeRefreshService::make()->refresh(
                $resume,
                $user,
                RcResumeRefreshTrigger::Explicit,
                true,
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success((new RcResumeResource($resume->fresh()))->resolve($request));
    }

    /**
     * 编辑简历
     *
     * PUT /rc/resumes/{id}
     *
     * @throws \Exception
     */
    public function update(ResumeUpdateRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        $resume = Resume::query()
            ->where('user_id', $user->id)
            ->find($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $payload = $request->validated();

        DB::transaction(function () use ($payload, $resume, $user): void {
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $resume = Resume::query()
                ->where('user_id', $user->id)
                ->whereKey($resume->id)
                ->lockForUpdate()
                ->firstOrFail();

            $resume->fill($payload);

            if (array_key_exists('is_primary', $payload)) {
                $resume->is_primary = (int) $payload['is_primary'];
            }

            $resume->save();

            if ($resume->wasChanged()) {
                RcResumeRefreshService::make()->refresh(
                    $resume,
                    $user,
                    RcResumeRefreshTrigger::ResumeUpdated,
                );
            }

            if ($resume->is_primary === 1) {
                ResumeService::make()->promote($user, $resume);
            }
        });

        return $this->success((new RcResumeResource($resume->fresh()))->resolve($request));
    }

    /**
     * 上传简历附件
     *
     * POST /rc/resumes/{id}/attachment
     *
     * @throws \Exception
     */
    public function uploadAttachment(ResumeAttachmentStoreRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        $resume = Resume::query()
            ->where('user_id', $user->id)
            ->find($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        try {
            $resume = ResumeService::make()->attachFile($resume, $request->file('file'));
        } catch (\Throwable $exception) {
            return $this->error('简历附件上传失败: '.$exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->success((new RcResumeResource($resume))->resolve($request));
    }

    /**
     * 上传简历头像
     *
     * PATCH /rc/resume/{id}/avatar/upload
     *
     * @throws \Exception
     */
    public function uploadAvatar(ResumeAvatarUploadRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        $resume = Resume::query()
            ->where('user_id', $user->id)
            ->find($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        try {
            $resume = ResumeService::make()->attachAvatar($resume, $request->file('file'));
        } catch (\Throwable $exception) {
            return $this->error('简历头像上传失败: '.$exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->success((new RcResumeResource($resume))->resolve($request));
    }
}
