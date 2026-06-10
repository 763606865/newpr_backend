<?php

namespace App\Rc\Controllers;

use App\Models\Rc\Resume;
use App\Models\Rc\ResumeSkill;
use App\Models\User;
use App\Rc\Controllers\Concerns\FindsOwnedResume;
use App\Rc\Requests\ResumeSkillStoreRequest;
use App\Rc\Requests\ResumeSkillUpdateRequest;
use App\Resources\Rc\RcResumeSkillResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class ResumeSkillController extends Controller
{
    use FindsOwnedResume;

    /**
     * 简历专业技能列表
     *
     * GET /rc/resumes/{id}/skills
     *
     * @throws \Exception
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        /** @var Collection<int, array<string, mixed>> $skills */
        $skills = RcResumeSkillResource::collection(
            $resume->skills()->orderByDesc('sort')->orderByDesc('id')->get(),
        )->resolve($request);

        return $this->success(['skills' => $skills]);
    }

    /**
     * 简历专业技能详情
     *
     * GET /rc/resumes/{id}/skills/{skillId}
     *
     * @throws \Exception
     */
    public function show(Request $request, int $id, int $skillId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $skill = $this->findOwnedResumeItem(ResumeSkill::class, $resume->id, $skillId);

        if (! $skill instanceof ResumeSkill) {
            return $this->error('专业技能不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success((new RcResumeSkillResource($skill))->resolve($request));
    }

    /**
     * 新增简历专业技能
     *
     * POST /rc/resumes/{id}/skills
     *
     * @throws \Exception
     */
    public function store(ResumeSkillStoreRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $skill = new ResumeSkill;
        $skill->fill($request->validated());
        $skill->resume_id = $resume->id;
        $skill->user_id = $user->id;
        $skill->save();

        return $this->success((new RcResumeSkillResource($skill->fresh()))->resolve($request));
    }

    /**
     * 编辑简历专业技能
     *
     * PUT /rc/resumes/{id}/skills/{skillId}
     *
     * @throws \Exception
     */
    public function update(ResumeSkillUpdateRequest $request, int $id, int $skillId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $skill = $this->findOwnedResumeItem(ResumeSkill::class, $resume->id, $skillId);

        if (! $skill instanceof ResumeSkill) {
            return $this->error('专业技能不存在。', Response::HTTP_NOT_FOUND);
        }

        $skill->fill($request->validated());
        $skill->save();

        return $this->success((new RcResumeSkillResource($skill->fresh()))->resolve($request));
    }

    /**
     * 删除简历专业技能
     *
     * DELETE /rc/resumes/{id}/skills/{skillId}
     *
     * @throws \Exception
     */
    public function destroy(Request $request, int $id, int $skillId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $skill = $this->findOwnedResumeItem(ResumeSkill::class, $resume->id, $skillId);

        if (! $skill instanceof ResumeSkill) {
            return $this->error('专业技能不存在。', Response::HTTP_NOT_FOUND);
        }

        $skill->delete();

        return $this->success((object) []);
    }
}
