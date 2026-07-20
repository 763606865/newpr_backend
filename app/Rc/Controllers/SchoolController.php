<?php

namespace App\Rc\Controllers;

use App\Models\Rc\UserIdentity;
use App\Models\School;
use App\Models\User;
use App\Rc\Requests\SchoolBindRequest;
use App\Rc\Requests\SchoolProfileUpdateRequest;
use App\Resources\Rc\RcSchoolProfileResource;
use App\Resources\Rc\RcSchoolResource;
use App\Resources\Rc\RcUserIdentityResource;
use App\Services\RcSchoolService;
use App\Services\SchoolProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SchoolController extends Controller
{
    /**
     * 绑定已存在学校
     *
     * POST /rc/schools/bind
     */
    public function bind(SchoolBindRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();
        $service = RcSchoolService::make();
        $validated = $request->validated();

        if (! $service->resolveCampusManagerIdentity($user) instanceof UserIdentity) {
            return $this->error('请先切换为校招负责人身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $school = $service->findBySchoolCode((string) $validated['school_code']);

        if (! $school instanceof School) {
            return $this->error('学校不存在。', Response::HTTP_NOT_FOUND);
        }

        $bindableMessage = $service->schoolBindableMessage($school);

        if ($bindableMessage !== null) {
            return $this->error($bindableMessage, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $alreadyBoundMessage = $service->userAlreadyBoundSchoolMessage($user, $school);

        if ($alreadyBoundMessage !== null) {
            return $this->error($alreadyBoundMessage, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $identity = $service->bind(
            $service->prepareCampusManagerIdentityForSchoolBind($user),
            $school,
            (string) $validated['job_title'],
        );

        return $this->success($this->bindPayload($request, $school, $identity));
    }

    /**
     * 当前学校招聘资料
     *
     * GET /rc/schools/profile
     */
    public function profileShow(Request $request): JsonResponse
    {
        $school = $this->resolveSchool();

        if (! $school instanceof School) {
            return $this->error('请先切换为校招负责人身份并绑定学校。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $profile = SchoolProfileService::make()->ensureForSchool($school);
        $profile->load('school');

        return $this->success([
            'profile' => (new RcSchoolProfileResource($profile))->resolve($request),
        ]);
    }

    /**
     * 更新当前学校招聘资料
     *
     * PUT /rc/schools/profile
     */
    public function profileUpdate(SchoolProfileUpdateRequest $request): JsonResponse
    {
        $school = $this->resolveSchool();

        if (! $school instanceof School) {
            return $this->error('请先切换为校招负责人身份并绑定学校。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $profile = SchoolProfileService::make()->ensureForSchool($school);
        $profile = SchoolProfileService::make()->update($profile, $request->validated());

        return $this->success([
            'profile' => (new RcSchoolProfileResource($profile))->resolve($request),
        ]);
    }

    /**
     * @return array{school: array<string, mixed>, identity: array<string, mixed>}
     */
    private function bindPayload(Request $request, School $school, UserIdentity $identity): array
    {
        $school->load('profile');

        return [
            'school' => (new RcSchoolResource($school))->resolve($request),
            'identity' => (new RcUserIdentityResource($identity))->resolve($request),
        ];
    }

    private function resolveSchool(): ?School
    {
        /** @var User $user */
        $user = $this->user();

        return RcSchoolService::make()->resolveCampusManagerSchool($user);
    }
}
