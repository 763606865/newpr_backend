<?php

namespace App\Rc\Controllers;

use App\Models\Rc\Resume;
use App\Models\Rc\ResumeProject;
use App\Models\User;
use App\Rc\Controllers\Concerns\FindsOwnedResume;
use App\Rc\Requests\ResumeProjectStoreRequest;
use App\Rc\Requests\ResumeProjectUpdateRequest;
use App\Resources\Rc\RcResumeProjectResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class ResumeProjectController extends Controller
{
    use FindsOwnedResume;

    /**
     * 简历项目经历列表
     *
     * GET /rc/resumes/{id}/projects
     *
     * @throws \Exception
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        /** @var Collection<int, array<string, mixed>> $projects */
        $projects = RcResumeProjectResource::collection(
            $resume->projects()->orderByDesc('sort')->orderByDesc('id')->get(),
        )->resolve($request);

        return $this->success(['projects' => $projects]);
    }

    /**
     * 简历项目经历详情
     *
     * GET /rc/resumes/{id}/projects/{projectId}
     *
     * @throws \Exception
     */
    public function show(Request $request, int $id, int $projectId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $project = $this->findOwnedResumeItem(ResumeProject::class, $resume->id, $projectId);

        if (! $project instanceof ResumeProject) {
            return $this->error('项目经历不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success((new RcResumeProjectResource($project))->resolve($request));
    }

    /**
     * 新增简历项目经历
     *
     * POST /rc/resumes/{id}/projects
     *
     * @throws \Exception
     */
    public function store(ResumeProjectStoreRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $project = new ResumeProject;
        $project->fill($request->validated());
        $project->resume_id = $resume->id;
        $project->user_id = $user->id;
        $project->save();

        return $this->success((new RcResumeProjectResource($project->fresh()))->resolve($request));
    }

    /**
     * 编辑简历项目经历
     *
     * PUT /rc/resumes/{id}/projects/{projectId}
     *
     * @throws \Exception
     */
    public function update(ResumeProjectUpdateRequest $request, int $id, int $projectId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $project = $this->findOwnedResumeItem(ResumeProject::class, $resume->id, $projectId);

        if (! $project instanceof ResumeProject) {
            return $this->error('项目经历不存在。', Response::HTTP_NOT_FOUND);
        }

        $project->fill($request->validated());
        $project->save();

        return $this->success((new RcResumeProjectResource($project->fresh()))->resolve($request));
    }

    /**
     * 删除简历项目经历
     *
     * DELETE /rc/resumes/{id}/projects/{projectId}
     *
     * @throws \Exception
     */
    public function destroy(Request $request, int $id, int $projectId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $project = $this->findOwnedResumeItem(ResumeProject::class, $resume->id, $projectId);

        if (! $project instanceof ResumeProject) {
            return $this->error('项目经历不存在。', Response::HTTP_NOT_FOUND);
        }

        $project->delete();

        return $this->success((object) []);
    }
}
