<?php

namespace App\Rc\Controllers;

use App\Models\Rc\Resume;
use App\Models\Rc\ResumeWork;
use App\Models\User;
use App\Rc\Requests\ResumeWorkStoreRequest;
use App\Rc\Requests\ResumeWorkUpdateRequest;
use App\Resources\Rc\RcResumeWorkResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class ResumeWorkController extends Controller
{
    /**
     * 简历工作经历列表
     *
     * GET /rc/resumes/{id}/works
     *
     * @throws \Exception
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        /** @var Collection<int, array<string, mixed>> $works */
        $works = RcResumeWorkResource::collection(
            $resume->works()->orderByDesc('sort')->orderByDesc('id')->get(),
        )->resolve($request);

        return $this->success(['works' => $works]);
    }

    /**
     * 简历工作经历详情
     *
     * GET /rc/resumes/{id}/works/{workId}
     *
     * @throws \Exception
     */
    public function show(Request $request, int $id, int $workId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $work = $this->findOwnedWork($resume->id, $workId);

        if (! $work instanceof ResumeWork) {
            return $this->error('工作经验不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success((new RcResumeWorkResource($work))->resolve($request));
    }

    /**
     * 新增简历工作经历
     *
     * POST /rc/resumes/{id}/works
     *
     * @throws \Exception
     */
    public function store(ResumeWorkStoreRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $work = new ResumeWork;
        $work->fill($request->validated());
        $work->resume_id = $resume->id;
        $work->user_id = $user->id;
        $work->save();

        return $this->success((new RcResumeWorkResource($work->fresh()))->resolve($request));
    }

    /**
     * 编辑简历工作经历
     *
     * PUT /rc/resumes/{id}/works/{workId}
     *
     * @throws \Exception
     */
    public function update(ResumeWorkUpdateRequest $request, int $id, int $workId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $work = $this->findOwnedWork($resume->id, $workId);

        if (! $work instanceof ResumeWork) {
            return $this->error('工作经验不存在。', Response::HTTP_NOT_FOUND);
        }

        $work->fill($request->validated());
        $work->save();

        return $this->success((new RcResumeWorkResource($work->fresh()))->resolve($request));
    }

    /**
     * 删除简历工作经历
     *
     * DELETE /rc/resumes/{id}/works/{workId}
     *
     * @throws \Exception
     */
    public function destroy(Request $request, int $id, int $workId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $work = $this->findOwnedWork($resume->id, $workId);

        if (! $work instanceof ResumeWork) {
            return $this->error('工作经验不存在。', Response::HTTP_NOT_FOUND);
        }

        $work->delete();

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

    private function findOwnedWork(int $resumeId, int $workId): ?ResumeWork
    {
        /** @var User $user */
        $user = $this->user();

        $work = ResumeWork::query()
            ->whereKey($workId)
            ->where('resume_id', $resumeId)
            ->where('user_id', $user->id)
            ->first();

        return $work instanceof ResumeWork ? $work : null;
    }
}
