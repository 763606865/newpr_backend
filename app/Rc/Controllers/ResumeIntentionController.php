<?php

namespace App\Rc\Controllers;

use App\Models\Rc\Resume;
use App\Models\Rc\ResumeIntention;
use App\Models\User;
use App\Rc\Requests\ResumeIntentionUpsertRequest;
use App\Resources\Rc\RcResumeIntentionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class ResumeIntentionController extends Controller
{
    /**
     * 简历求职意向列表
     *
     * GET /rc/resumes/{id}/intentions
     *
     * @throws \Exception
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        /** @var Collection<int, array<string, mixed>> $intentions */
        $intentions = RcResumeIntentionResource::collection(
            $resume->intentions()->orderByDesc('updated_at')->orderByDesc('id')->get(),
        )->resolve($request);

        return $this->success(['intentions' => $intentions]);
    }

    /**
     * 简历求职意向详情
     *
     * GET /rc/resumes/{id}/intentions/{intentionId}
     *
     * @throws \Exception
     */
    public function show(Request $request, int $id, int $intentionId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $intention = $this->findOwnedIntention($resume->id, $intentionId);

        if (! $intention instanceof ResumeIntention) {
            return $this->error('求职意向不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success((new RcResumeIntentionResource($intention))->resolve($request));
    }

    /**
     * 新增简历求职意向
     *
     * POST /rc/resumes/{id}/intentions
     *
     * @throws \Exception
     */
    public function store(ResumeIntentionUpsertRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $intention = new ResumeIntention;
        $intention->fill($request->validated());
        $intention->resume_id = $resume->id;
        $intention->user_id = $user->id;
        $intention->save();

        return $this->success((new RcResumeIntentionResource($intention->fresh()))->resolve($request));
    }

    /**
     * 编辑简历求职意向
     *
     * PUT /rc/resumes/{id}/intentions/{intentionId}
     *
     * @throws \Exception
     */
    public function update(ResumeIntentionUpsertRequest $request, int $id, int $intentionId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $intention = $this->findOwnedIntention($resume->id, $intentionId);

        if (! $intention instanceof ResumeIntention) {
            return $this->error('求职意向不存在。', Response::HTTP_NOT_FOUND);
        }

        $intention->fill($request->validated());
        $intention->save();

        return $this->success((new RcResumeIntentionResource($intention->fresh()))->resolve($request));
    }

    /**
     * 删除简历求职意向
     *
     * DELETE /rc/resumes/{id}/intentions/{intentionId}
     *
     * @throws \Exception
     */
    public function destroy(Request $request, int $id, int $intentionId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $intention = $this->findOwnedIntention($resume->id, $intentionId);

        if (! $intention instanceof ResumeIntention) {
            return $this->error('求职意向不存在。', Response::HTTP_NOT_FOUND);
        }

        $intention->delete();

        return $this->success((object) []);
    }

    private function findOwnedResume(int $resumeId): ?Resume
    {
        /** @var User $user */
        $user = $this->user();

        return Resume::query()
            ->where('user_id', $user->id)
            ->find($resumeId);
    }

    private function findOwnedIntention(int $resumeId, int $intentionId): ?ResumeIntention
    {
        /** @var User $user */
        $user = $this->user();

        $intention = ResumeIntention::query()
            ->whereKey($intentionId)
            ->where('resume_id', $resumeId)
            ->where('user_id', $user->id)
            ->first();

        return $intention instanceof ResumeIntention ? $intention : null;
    }
}
