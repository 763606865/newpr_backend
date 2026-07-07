<?php

namespace App\Http\Controllers;

use App\Enums\CompanyNatureType;
use App\Enums\CompanyScaleType;
use App\Enums\RcSchoolActivityOrganizerType;
use App\Http\Requests\SchoolActivityCompanyInviteRegisterRequest;
use App\Http\Requests\SchoolActivityIndexRequest;
use App\Http\Requests\SchoolActivitySchoolInviteRegisterRequest;
use App\Models\Company;
use App\Models\Rc\SchoolActivity;
use App\Models\Rc\SchoolActivityCompany;
use App\Models\School;
use App\Resources\Cms\CmsSchoolActivityResource;
use App\Resources\Rc\RcCompanyResource;
use App\Resources\Rc\RcSchoolActivityCompanyResource;
use App\Resources\Rc\RcSchoolActivityResource;
use App\Resources\Rc\RcSchoolActivitySchoolResource;
use App\Services\CmsSchoolActivityService;
use App\Services\RcSchoolActivityApplicationService;
use App\Services\RcSchoolActivityService;
use App\Support\SchoolActivityInviteCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class SchoolActivityController extends Controller
{
    /**
     * 校园活动列表（分页）
     *
     * GET /cms/school-activities
     *
     * @throws \Exception
     */
    public function index(SchoolActivityIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = $this->resolvePerPage($validated);

        $paginator = CmsSchoolActivityService::make()->paginate(
            $perPage,
            $request->searchFilters(),
        );

        $paginator->getCollection()->transform(
            fn (SchoolActivity $activity): array => (new CmsSchoolActivityResource($activity))->resolve($request),
        );

        return api_response($paginator);
    }

    /**
     * 校园活动详情
     *
     * GET /cms/school-activities/{id}
     *
     * @throws \Exception
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $activity = CmsSchoolActivityService::make()->findPublished(
            $id,
            $this->resolveRegionCode($request),
        );

        if (! $activity instanceof SchoolActivity) {
            abort(404);
        }

        return api_response((new CmsSchoolActivityResource($activity))->resolve($request));
    }

    /**
     * 邀请页-活动详情（无需认证）
     *
     * GET /cms/school-activities/invite/{inviteCode}
     *
     * @throws \Exception
     */
    public function showByInviteCode(Request $request, string $inviteCode): JsonResponse
    {
        $activity = $this->resolveActivityByInviteCode($inviteCode);

        if ($activity instanceof JsonResponse) {
            return $activity;
        }

        $activity->load(['organizer', 'schools']);

        [$inviterName, $invitationMessage, $inviteTarget] = $this->buildInvitePresentation($activity);

        return api_response([
            'inviter_name' => $inviterName,
            'invitation_message' => $invitationMessage,
            'invite_target' => $inviteTarget,
            'activity' => (new RcSchoolActivityResource($activity))->resolve($request),
        ]);
    }

    /**
     * 邀请页-提交企业信息并加入活动（无需认证）
     *
     * POST /cms/school-activities/invite/{inviteCode}/companies
     *
     * @throws \Exception
     */
    public function registerCompanyByInviteCode(
        SchoolActivityCompanyInviteRegisterRequest $request,
        string $inviteCode,
    ): JsonResponse {
        $activity = $this->resolveActivityByInviteCode($inviteCode);

        if ($activity instanceof JsonResponse) {
            return $activity;
        }

        try {
            $application = RcSchoolActivityApplicationService::make()->registerCompanyViaInvite(
                $activity,
                [
                    'name' => (string) $request->validated('name'),
                    'credit_code' => (string) $request->validated('credit_code'),
                    'contact_phone' => (string) $request->validated('contact_phone'),
                ],
            );
        } catch (InvalidArgumentException $exception) {
            return $this->errorResponse($exception->getMessage(), 422);
        }

        $application->load(['company', 'activityBooth']);

        return api_response([
            'company' => (new RcCompanyResource($application->company))->resolve($request),
            'application' => (new RcSchoolActivityCompanyResource($application))->resolve($request),
        ]);
    }

    /**
     * 邀请页-提交院校联系人信息并加入活动（无需认证）
     *
     * POST /cms/school-activities/invite/{inviteCode}/schools
     *
     * @throws \Exception
     */
    public function registerSchoolByInviteCode(
        SchoolActivitySchoolInviteRegisterRequest $request,
        string $inviteCode,
    ): JsonResponse {
        $activity = $this->resolveActivityByInviteCode($inviteCode);

        if ($activity instanceof JsonResponse) {
            return $activity;
        }

        $validated = $request->validated();

        try {
            $schoolLink = RcSchoolActivityApplicationService::make()->registerSchoolViaInvite(
                $activity,
                [
                    'school_code' => (string) $validated['school_code'],
                    'contact_name' => (string) $validated['contact_name'],
                    'contact_phone' => (string) $validated['contact_phone'],
                    'contact_email' => $validated['contact_email'] ?? null,
                    'remark' => $validated['remark'] ?? null,
                ],
            );
        } catch (InvalidArgumentException $exception) {
            return $this->errorResponse($exception->getMessage(), 422);
        }

        return api_response([
            'school_application' => (new RcSchoolActivitySchoolResource($schoolLink))->resolve($request),
        ]);
    }

    public function getCompanies(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'scale_type' => ['nullable', 'integer', Rule::enum(CompanyScaleType::class)],
            'nature_type' => ['nullable', 'integer', Rule::enum(CompanyNatureType::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $activity = CmsSchoolActivityService::make()->findPublished(
            $id,
            $this->resolveRegionCode($request),
        );

        if (! $activity instanceof SchoolActivity) {
            abort(404);
        }

        $query = SchoolActivityCompany::query()
            ->where('activity_id', $activity->id)
            ->approved()
            ->with([
                'company.profile',
                'activityJobs' => fn ($query) => $query
                    ->approved()
                    ->with(['job.company.profile']),
            ])
            ->withCount([
                'activityJobs as activity_jobs_count' => fn ($query) => $query->approved(),
            ])
            ->orderByDesc('apply_at')
            ->orderByDesc('id');

        if (filled($validated['scale_type'] ?? null)) {
            $query->whereHas('company.profile', fn ($profileQuery) => $profileQuery->where('scale_type', (int) $validated['scale_type']));
        }

        if (filled($validated['nature_type'] ?? null)) {
            $query->whereHas('company.profile', fn ($profileQuery) => $profileQuery->where('nature_type', (int) $validated['nature_type']));
        }

        $paginator = $query->paginate($this->resolvePerPage($validated));

        $paginator->getCollection()->transform(
            fn (SchoolActivityCompany $application): array => (new RcSchoolActivityCompanyResource($application))->resolve($request),
        );

        return api_response($paginator);
    }

    /**
     * @return array{0: ?string, 1: string, 2: ?string}
     */
    private function buildInvitePresentation(SchoolActivity $activity): array
    {
        if ($activity->organizer_type === RcSchoolActivityOrganizerType::School) {
            $inviterName = $activity->organizer instanceof School
                ? $activity->organizer->name
                : null;

            return [
                $inviterName,
                filled($inviterName)
                    ? "{$inviterName}邀请你参加{$activity->title}"
                    : "邀请你参加{$activity->title}",
                'company',
            ];
        }

        if ($activity->organizer_type === RcSchoolActivityOrganizerType::Company) {
            $inviterName = $activity->organizer instanceof Company
                ? $activity->organizer->name
                : null;

            return [
                $inviterName,
                filled($inviterName)
                    ? "{$inviterName}邀请贵校参与{$activity->title}"
                    : "邀请贵校参与{$activity->title}",
                'school',
            ];
        }

        return [null, "邀请你参加{$activity->title}", null];
    }

    private function resolveActivityByInviteCode(string $inviteCode): SchoolActivity|JsonResponse
    {
        $activityId = SchoolActivityInviteCode::decode($inviteCode);

        if ($activityId === null) {
            return $this->errorResponse('邀请码无效。', 404);
        }

        $activity = RcSchoolActivityService::make()->findPublished($activityId);

        if (! $activity instanceof SchoolActivity) {
            return $this->errorResponse('活动不存在或未发布。', 404);
        }

        return $activity;
    }

    private function resolveRegionCode(Request $request): ?string
    {
        $districtCode = $request->string('district_code')->toString();

        if ($districtCode !== '') {
            return $districtCode;
        }

        $cityCode = $this->resolveCityCode($request);

        if ($cityCode !== null) {
            return $cityCode;
        }

        $provinceCode = $request->string('province_code')->toString();

        if ($provinceCode !== '') {
            return $provinceCode;
        }

        return null;
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        $now = microtime(true);

        return response()->json([
            'code' => $status,
            'message' => $message,
            'meta' => [
                'timestamp' => $now,
                'response_time' => $now - LARAVEL_START,
            ],
        ], $status);
    }
}
