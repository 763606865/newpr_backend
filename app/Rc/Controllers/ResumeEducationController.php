<?php

namespace App\Rc\Controllers;

use App\Models\Rc\Resume;
use App\Models\Rc\ResumeEducation;
use App\Models\User;
use App\Rc\Requests\ResumeEducationStoreRequest;
use App\Rc\Requests\ResumeEducationUpdateRequest;
use App\Resources\Rc\RcResumeEducationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class ResumeEducationController extends Controller
{
    /**
     * 简历教育经历列表
     *
     * GET /rc/resumes/{id}/educations
     *
     * @throws \Exception
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        /** @var Collection<int, array<string, mixed>> $educations */
        $educations = RcResumeEducationResource::collection(
            $resume->educations()->orderByDesc('sort')->orderByDesc('id')->get(),
        )->resolve($request);

        return $this->success(['educations' => $educations]);
    }

    /**
     * 简历教育经历详情
     *
     * GET /rc/resumes/{id}/educations/{educationId}
     *
     * @throws \Exception
     */
    public function show(Request $request, int $id, int $educationId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $education = $this->findOwnedEducation($resume->id, $educationId);

        if (! $education instanceof ResumeEducation) {
            return $this->error('教育经历不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success((new RcResumeEducationResource($education))->resolve($request));
    }

    /**
     * 新增简历教育经历
     *
     * POST /rc/resumes/{id}/educations
     *
     * @throws \Exception
     */
    public function store(ResumeEducationStoreRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $education = new ResumeEducation;
        $education->fill($request->validated());
        $education->resume_id = $resume->id;
        $education->user_id = $user->id;
        $education->save();

        return $this->success((new RcResumeEducationResource($education->fresh()))->resolve($request));
    }

    /**
     * 编辑简历教育经历
     *
     * PUT /rc/resumes/{id}/educations/{educationId}
     *
     * @throws \Exception
     */
    public function update(ResumeEducationUpdateRequest $request, int $id, int $educationId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $education = $this->findOwnedEducation($resume->id, $educationId);

        if (! $education instanceof ResumeEducation) {
            return $this->error('教育经历不存在。', Response::HTTP_NOT_FOUND);
        }

        $education->fill($request->validated());
        $education->save();

        return $this->success((new RcResumeEducationResource($education->fresh()))->resolve($request));
    }

    /**
     * 删除简历教育经历
     *
     * DELETE /rc/resumes/{id}/educations/{educationId}
     *
     * @throws \Exception
     */
    public function destroy(Request $request, int $id, int $educationId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $education = $this->findOwnedEducation($resume->id, $educationId);

        if (! $education instanceof ResumeEducation) {
            return $this->error('教育经历不存在。', Response::HTTP_NOT_FOUND);
        }

        $education->delete();

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

    private function findOwnedEducation(int $resumeId, int $educationId): ?ResumeEducation
    {
        /** @var User $user */
        $user = $this->user();

        $education = ResumeEducation::query()
            ->whereKey($educationId)
            ->where('resume_id', $resumeId)
            ->where('user_id', $user->id)
            ->first();

        return $education instanceof ResumeEducation ? $education : null;
    }
}
