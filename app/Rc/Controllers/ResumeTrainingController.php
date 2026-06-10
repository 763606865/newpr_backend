<?php

namespace App\Rc\Controllers;

use App\Models\Rc\Resume;
use App\Models\Rc\ResumeTraining;
use App\Models\User;
use App\Rc\Controllers\Concerns\FindsOwnedResume;
use App\Rc\Requests\ResumeTrainingStoreRequest;
use App\Rc\Requests\ResumeTrainingUpdateRequest;
use App\Resources\Rc\RcResumeTrainingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class ResumeTrainingController extends Controller
{
    use FindsOwnedResume;

    /**
     * 简历培训经历列表
     *
     * GET /rc/resumes/{id}/trainings
     *
     * @throws \Exception
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        /** @var Collection<int, array<string, mixed>> $trainings */
        $trainings = RcResumeTrainingResource::collection(
            $resume->trainings()->orderByDesc('sort')->orderByDesc('id')->get(),
        )->resolve($request);

        return $this->success(['trainings' => $trainings]);
    }

    /**
     * 简历培训经历详情
     *
     * GET /rc/resumes/{id}/trainings/{trainingId}
     *
     * @throws \Exception
     */
    public function show(Request $request, int $id, int $trainingId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $training = $this->findOwnedResumeItem(ResumeTraining::class, $resume->id, $trainingId);

        if (! $training instanceof ResumeTraining) {
            return $this->error('培训经历不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success((new RcResumeTrainingResource($training))->resolve($request));
    }

    /**
     * 新增简历培训经历
     *
     * POST /rc/resumes/{id}/trainings
     *
     * @throws \Exception
     */
    public function store(ResumeTrainingStoreRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $training = new ResumeTraining;
        $training->fill($request->validated());
        $training->resume_id = $resume->id;
        $training->user_id = $user->id;
        $training->save();

        return $this->success((new RcResumeTrainingResource($training->fresh()))->resolve($request));
    }

    /**
     * 编辑简历培训经历
     *
     * PUT /rc/resumes/{id}/trainings/{trainingId}
     *
     * @throws \Exception
     */
    public function update(ResumeTrainingUpdateRequest $request, int $id, int $trainingId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $training = $this->findOwnedResumeItem(ResumeTraining::class, $resume->id, $trainingId);

        if (! $training instanceof ResumeTraining) {
            return $this->error('培训经历不存在。', Response::HTTP_NOT_FOUND);
        }

        $training->fill($request->validated());
        $training->save();

        return $this->success((new RcResumeTrainingResource($training->fresh()))->resolve($request));
    }

    /**
     * 删除简历培训经历
     *
     * DELETE /rc/resumes/{id}/trainings/{trainingId}
     *
     * @throws \Exception
     */
    public function destroy(Request $request, int $id, int $trainingId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $training = $this->findOwnedResumeItem(ResumeTraining::class, $resume->id, $trainingId);

        if (! $training instanceof ResumeTraining) {
            return $this->error('培训经历不存在。', Response::HTTP_NOT_FOUND);
        }

        $training->delete();

        return $this->success((object) []);
    }
}
