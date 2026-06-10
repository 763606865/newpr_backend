<?php

namespace App\Rc\Controllers;

use App\Models\Rc\Resume;
use App\Models\Rc\ResumeLanguage;
use App\Models\User;
use App\Rc\Controllers\Concerns\FindsOwnedResume;
use App\Rc\Requests\ResumeLanguageStoreRequest;
use App\Rc\Requests\ResumeLanguageUpdateRequest;
use App\Resources\Rc\RcResumeLanguageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class ResumeLanguageController extends Controller
{
    use FindsOwnedResume;

    /**
     * 简历语言能力列表
     *
     * GET /rc/resumes/{id}/languages
     *
     * @throws \Exception
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        /** @var Collection<int, array<string, mixed>> $languages */
        $languages = RcResumeLanguageResource::collection(
            $resume->languages()->orderByDesc('sort')->orderByDesc('id')->get(),
        )->resolve($request);

        return $this->success(['languages' => $languages]);
    }

    /**
     * 简历语言能力详情
     *
     * GET /rc/resumes/{id}/languages/{languageId}
     *
     * @throws \Exception
     */
    public function show(Request $request, int $id, int $languageId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $language = $this->findOwnedResumeItem(ResumeLanguage::class, $resume->id, $languageId);

        if (! $language instanceof ResumeLanguage) {
            return $this->error('语言能力不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success((new RcResumeLanguageResource($language))->resolve($request));
    }

    /**
     * 新增简历语言能力
     *
     * POST /rc/resumes/{id}/languages
     *
     * @throws \Exception
     */
    public function store(ResumeLanguageStoreRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $language = new ResumeLanguage;
        $language->fill($request->validated());
        $language->resume_id = $resume->id;
        $language->user_id = $user->id;
        $language->save();

        return $this->success((new RcResumeLanguageResource($language->fresh()))->resolve($request));
    }

    /**
     * 编辑简历语言能力
     *
     * PUT /rc/resumes/{id}/languages/{languageId}
     *
     * @throws \Exception
     */
    public function update(ResumeLanguageUpdateRequest $request, int $id, int $languageId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $language = $this->findOwnedResumeItem(ResumeLanguage::class, $resume->id, $languageId);

        if (! $language instanceof ResumeLanguage) {
            return $this->error('语言能力不存在。', Response::HTTP_NOT_FOUND);
        }

        $language->fill($request->validated());
        $language->save();

        return $this->success((new RcResumeLanguageResource($language->fresh()))->resolve($request));
    }

    /**
     * 删除简历语言能力
     *
     * DELETE /rc/resumes/{id}/languages/{languageId}
     *
     * @throws \Exception
     */
    public function destroy(Request $request, int $id, int $languageId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $language = $this->findOwnedResumeItem(ResumeLanguage::class, $resume->id, $languageId);

        if (! $language instanceof ResumeLanguage) {
            return $this->error('语言能力不存在。', Response::HTTP_NOT_FOUND);
        }

        $language->delete();

        return $this->success((object) []);
    }
}
