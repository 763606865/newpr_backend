<?php

namespace App\Rc\Controllers;

use App\Models\Rc\Resume;
use App\Models\Rc\ResumeCertificate;
use App\Models\User;
use App\Rc\Controllers\Concerns\FindsOwnedResume;
use App\Rc\Requests\ResumeCertificateStoreRequest;
use App\Rc\Requests\ResumeCertificateUpdateRequest;
use App\Resources\Rc\RcResumeCertificateResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class ResumeCertificateController extends Controller
{
    use FindsOwnedResume;

    /**
     * 简历证书/荣誉列表
     *
     * GET /rc/resumes/{id}/certificates
     *
     * @throws \Exception
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        /** @var Collection<int, array<string, mixed>> $certificates */
        $certificates = RcResumeCertificateResource::collection(
            $resume->certificates()->orderByDesc('sort')->orderByDesc('id')->get(),
        )->resolve($request);

        return $this->success(['certificates' => $certificates]);
    }

    /**
     * 简历证书/荣誉详情
     *
     * GET /rc/resumes/{id}/certificates/{certificateId}
     *
     * @throws \Exception
     */
    public function show(Request $request, int $id, int $certificateId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $certificate = $this->findOwnedResumeItem(ResumeCertificate::class, $resume->id, $certificateId);

        if (! $certificate instanceof ResumeCertificate) {
            return $this->error('证书/荣誉不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success((new RcResumeCertificateResource($certificate))->resolve($request));
    }

    /**
     * 新增简历证书/荣誉
     *
     * POST /rc/resumes/{id}/certificates
     *
     * @throws \Exception
     */
    public function store(ResumeCertificateStoreRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $certificate = new ResumeCertificate;
        $certificate->fill($request->validated());
        $certificate->resume_id = $resume->id;
        $certificate->user_id = $user->id;
        $certificate->save();

        return $this->success((new RcResumeCertificateResource($certificate->fresh()))->resolve($request));
    }

    /**
     * 编辑简历证书/荣誉
     *
     * PUT /rc/resumes/{id}/certificates/{certificateId}
     *
     * @throws \Exception
     */
    public function update(ResumeCertificateUpdateRequest $request, int $id, int $certificateId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $certificate = $this->findOwnedResumeItem(ResumeCertificate::class, $resume->id, $certificateId);

        if (! $certificate instanceof ResumeCertificate) {
            return $this->error('证书/荣誉不存在。', Response::HTTP_NOT_FOUND);
        }

        $certificate->fill($request->validated());
        $certificate->save();

        return $this->success((new RcResumeCertificateResource($certificate->fresh()))->resolve($request));
    }

    /**
     * 删除简历证书/荣誉
     *
     * DELETE /rc/resumes/{id}/certificates/{certificateId}
     *
     * @throws \Exception
     */
    public function destroy(Request $request, int $id, int $certificateId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $certificate = $this->findOwnedResumeItem(ResumeCertificate::class, $resume->id, $certificateId);

        if (! $certificate instanceof ResumeCertificate) {
            return $this->error('证书/荣誉不存在。', Response::HTTP_NOT_FOUND);
        }

        $certificate->delete();

        return $this->success((object) []);
    }
}
