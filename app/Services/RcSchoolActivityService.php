<?php

namespace App\Services;

use App\Enums\RcSchoolActivityOrganizerType;
use App\Enums\RcSchoolActivityStatus;
use App\Enums\RcSchoolActivityType;
use App\Models\Company;
use App\Models\Rc\SchoolActivity;
use App\Models\Rc\SchoolActivitySchool;
use App\Models\School;
use App\Models\SchoolProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RcSchoolActivityService extends Service
{
    /**
     * @return LengthAwarePaginator<int, SchoolActivity>
     */
    public function paginateForSchoolOrganizer(School $school, int $perPage, array $filters = []): LengthAwarePaginator
    {
        if (filled($filters['keyword'] ?? null)) {
            return RcSchoolActivitySearchService::make()->searchForSchoolOrganizer(
                $school->id,
                $perPage,
                $filters,
            );
        }

        $query = SchoolActivity::query()
            ->forOrganizer(RcSchoolActivityOrganizerType::School, $school->id)
            ->withCount(['companyApplications', 'jobs', 'activityBooths'])
            ->orderByDesc('sort')
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if (filled($filters['status'] ?? null)) {
            $query->where('status', (int) $filters['status']);
        }

        if (filled($filters['type'] ?? null)) {
            $query->where('type', (int) $filters['type']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @return LengthAwarePaginator<int, SchoolActivitySchool>
     */
    public function paginateParticipatedForSchool(School $school, int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = SchoolActivitySchool::query()
            ->where('school_id', $school->id)
            ->with([
                'activity' => fn ($activityQuery) => $activityQuery
                    ->withCount(['companyApplications', 'jobs', 'activityBooths'])
                    ->with('organizer'),
            ])
            ->orderByDesc('apply_at')
            ->orderByDesc('id');

        if (filled($filters['apply_status'] ?? null)) {
            $query->where('apply_status', (int) $filters['apply_status']);
        }

        if (filled($filters['type'] ?? null)) {
            $query->whereHas('activity', fn ($activityQuery) => $activityQuery->where('type', (int) $filters['type']));
        }

        if (filled($filters['activity_status'] ?? null)) {
            $query->whereHas('activity', fn ($activityQuery) => $activityQuery->where('status', (int) $filters['activity_status']));
        }

        if (filled($filters['keyword'] ?? null)) {
            $keyword = trim((string) $filters['keyword']);
            $query->whereHas('activity', fn ($activityQuery) => $activityQuery->where('title', 'like', "%{$keyword}%"));
        }

        return $query->paginate($perPage);
    }

    /**
     * @return LengthAwarePaginator<int, SchoolActivity>
     */
    public function paginateForCompanyOrganizer(Company $company, int $perPage, array $filters = []): LengthAwarePaginator
    {
        if (filled($filters['keyword'] ?? null)) {
            $filters['context'] = 'company_organizer';
            $filters['organizer_id'] = $company->id;

            return RcSchoolActivitySearchService::make()->search($perPage, $filters);
        }

        $query = SchoolActivity::query()
            ->forOrganizer(RcSchoolActivityOrganizerType::Company, $company->id)
            ->with(['schools'])
            ->orderByDesc('sort')
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if (filled($filters['status'] ?? null)) {
            $query->where('status', (int) $filters['status']);
        }

        if (filled($filters['type'] ?? null)) {
            $query->where('type', (int) $filters['type']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @return LengthAwarePaginator<int, SchoolActivity>
     */
    public function paginateAvailableForRecruiter(int $perPage, array $filters = []): LengthAwarePaginator
    {
        if (filled($filters['keyword'] ?? null)) {
            return RcSchoolActivitySearchService::make()->searchAvailable($perPage, $filters);
        }

        $query = SchoolActivity::query()
            ->availableForRecruiter()
            ->orderByDesc('sort')
            ->orderByDesc('start_time')
            ->orderByDesc('id');

        if (filled($filters['type'] ?? null)) {
            $query->where('type', (int) $filters['type']);
        }

        return $query->paginate($perPage);
    }

    public function findForSchoolOrganizer(School $school, int $activityId): ?SchoolActivity
    {
        return SchoolActivity::query()
            ->forOrganizer(RcSchoolActivityOrganizerType::School, $school->id)
            ->withCount([
                'companyApplications as company_applications_count',
                'jobs as jobs_count',
                'activityBooths as activity_booths_count',
            ])
            ->find($activityId);
    }

    public function findDetailForSchoolOrganizer(School $school, int $activityId): ?SchoolActivity
    {
        return SchoolActivity::query()
            ->forOrganizer(RcSchoolActivityOrganizerType::School, $school->id)
            ->withCount(['companyApplications', 'jobs', 'activityBooths'])
            ->with([
                'booth.areas' => fn ($query) => $query->ordered(),
                'companyApplications' => fn ($query) => $query
                    ->approved()
                    ->with(['company.profile']),
                'activityBooths' => fn ($query) => $query
                    ->with('company:id,name')
                    ->orderBy('booth_no')
                    ->orderBy('id'),
            ])
            ->find($activityId);
    }

    public function findForCompanyOrganizer(Company $company, int $activityId): ?SchoolActivity
    {
        return SchoolActivity::query()
            ->forOrganizer(RcSchoolActivityOrganizerType::Company, $company->id)
            ->with(['schools'])
            ->find($activityId);
    }

    public function findDetailForCompanyOrganizer(Company $company, int $activityId): ?SchoolActivity
    {
        return SchoolActivity::query()
            ->forOrganizer(RcSchoolActivityOrganizerType::Company, $company->id)
            ->with(['schools'])
            ->withCount([
                'companyApplications as company_applications_count',
                'jobs as jobs_count',
                'activityBooths as activity_booths_count',
            ])
            ->find($activityId);
    }

    public function findPublished(int $activityId): ?SchoolActivity
    {
        return SchoolActivity::query()
            ->published()
            ->with([
                'companyApplications' => fn ($query) => $query
                    ->approved()
                    ->with(['company.profile']),
            ])
            ->withCount([
                'companyApplications as company_applications_count',
                'jobs as jobs_count',
                'activityBooths as activity_booths_count',
            ])
            ->find($activityId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForSchool(School $school, array $data): SchoolActivity
    {
        $boothId = isset($data['booth_id']) ? (int) $data['booth_id'] : null;

        if ($boothId === null) {
            throw new InvalidArgumentException('请选择展位模板。');
        }

        return DB::transaction(function () use ($school, $data, $boothId): SchoolActivity {
            $activity = SchoolActivity::query()->create([
                ...Arr::only($data, [
                    'type',
                    'title',
                    'cover_image',
                    'description',
                    'link_url',
                    'province_code',
                    'city_code',
                    'district_code',
                    'address',
                    'register_start_date',
                    'register_end_date',
                    'start_time',
                    'end_time',
                    'contact_name',
                    'contact_phone',
                    'activity_mode',
                    'is_hot',
                    'sort',
                    'files',
                    'extra',
                    'remark',
                ]),
                'organizer_type' => RcSchoolActivityOrganizerType::School,
                'organizer_id' => $school->id,
                'booth_id' => $boothId,
                'status' => RcSchoolActivityStatus::Draft,
            ]);

            SchoolActivitySchool::query()->firstOrCreate([
                'activity_id' => $activity->id,
                'school_id' => $school->id,
            ]);

            RcSchoolActivityBoothService::make()->syncForActivity($activity, $school, $boothId);

            return $activity->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForCompany(Company $company, array $data): SchoolActivity
    {
        $type = RcSchoolActivityType::tryFrom((int) ($data['type'] ?? RcSchoolActivityType::Presentation->value))
            ?? RcSchoolActivityType::Presentation;

        $this->assertCompanyCreatableType($type);

        $schoolIds = $this->resolveCompanySchoolIds($type, $data['school_codes'] ?? null);

        return DB::transaction(function () use ($company, $data, $schoolIds, $type): SchoolActivity {
            $activity = SchoolActivity::query()->create([
                ...Arr::only($data, [
                    'title',
                    'cover_image',
                    'description',
                    'link_url',
                    'province_code',
                    'city_code',
                    'district_code',
                    'address',
                    'register_start_date',
                    'register_end_date',
                    'start_time',
                    'end_time',
                    'contact_name',
                    'contact_phone',
                    'activity_mode',
                    'is_hot',
                    'sort',
                    'files',
                    'extra',
                    'remark',
                ]),
                'type' => $type,
                'organizer_type' => RcSchoolActivityOrganizerType::Company,
                'organizer_id' => $company->id,
                'status' => RcSchoolActivityStatus::Draft,
            ]);

            $this->syncActivitySchools($activity, $schoolIds);

            return $activity->refresh()->load('schools');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SchoolActivity $activity, array $data): SchoolActivity
    {
        if ($activity->status === RcSchoolActivityStatus::Ended) {
            throw new InvalidArgumentException('已结束的活动不可编辑。');
        }

        if (array_key_exists('type', $data)) {
            $type = RcSchoolActivityType::tryFrom((int) $data['type']);

            if ($activity->organizer_type === RcSchoolActivityOrganizerType::Company && $type instanceof RcSchoolActivityType) {
                $this->assertCompanyCreatableType($type);
            }
        }

        $nextType = array_key_exists('type', $data)
            ? (RcSchoolActivityType::tryFrom((int) $data['type']) ?? $activity->type)
            : $activity->type;

        if ($activity->organizer_type === RcSchoolActivityOrganizerType::Company) {
            $this->assertCompanySchoolTargetProvided(
                $nextType,
                array_key_exists('school_codes', $data) ? ($data['school_codes'] ?? null) : null,
                $activity,
            );
        }

        $boothId = array_key_exists('booth_id', $data) ? (int) $data['booth_id'] : null;

        if ($boothId !== null && $activity->organizer_type === RcSchoolActivityOrganizerType::School) {
            RcSchoolActivityBoothService::make()->assertBoothConfigEditable($activity);
        }

        if (array_key_exists('school_codes', $data)) {
            $this->resolveCompanySchoolIds($nextType, $data['school_codes'] ?? null);
        }

        return DB::transaction(function () use ($activity, $data, $boothId, $nextType): SchoolActivity {
            $activity->fill(Arr::only($data, [
                'type',
                'title',
                'cover_image',
                'description',
                'link_url',
                'province_code',
                'city_code',
                'district_code',
                'address',
                'register_start_date',
                'register_end_date',
                'start_time',
                'end_time',
                'contact_name',
                'contact_phone',
                'activity_mode',
                'is_hot',
                'sort',
                'files',
                'extra',
                'remark',
            ]))->save();

            if ($activity->organizer_type === RcSchoolActivityOrganizerType::Company && array_key_exists('school_codes', $data)) {
                $this->syncActivitySchools(
                    $activity,
                    $this->resolveCompanySchoolIds($nextType, $data['school_codes'] ?? null),
                );
            }

            if ($boothId !== null && $activity->organizer_type === RcSchoolActivityOrganizerType::School && (int) $activity->booth_id !== $boothId) {
                $school = School::query()->find($activity->organizer_id);

                if (! $school instanceof School) {
                    throw new InvalidArgumentException('活动主办方不存在。');
                }

                RcSchoolActivityBoothService::make()->syncForActivity($activity, $school, $boothId);
            }

            return $activity->refresh();
        });
    }

    public function delete(SchoolActivity $activity): void
    {
        if ($activity->status !== RcSchoolActivityStatus::Draft) {
            throw new InvalidArgumentException('仅草稿状态的活动可删除。');
        }

        $activity->delete();
    }

    public function publish(SchoolActivity $activity): SchoolActivity
    {
        if ($activity->status === RcSchoolActivityStatus::Published) {
            throw new InvalidArgumentException('活动已发布，无需重复操作。');
        }

        if ($activity->status === RcSchoolActivityStatus::Ended) {
            throw new InvalidArgumentException('已结束的活动不可发布。');
        }

        if ($activity->organizer_type === RcSchoolActivityOrganizerType::Company) {
            $this->assertCompanyPublishReady($activity);
        } elseif (blank($activity->booth_id)) {
            throw new InvalidArgumentException('请先配置活动展位后再发布。');
        }

        return DB::transaction(function () use ($activity): SchoolActivity {
            $activity->update(['status' => RcSchoolActivityStatus::Published]);

            if ($activity->organizer_type === RcSchoolActivityOrganizerType::Company) {
                RcSchoolActivityApplicationService::make()->ensureOrganizerCompanyApplication($activity->refresh());
            }

            return $activity->refresh();
        });
    }

    public function end(SchoolActivity $activity): SchoolActivity
    {
        if ($activity->status === RcSchoolActivityStatus::Ended) {
            throw new InvalidArgumentException('活动已结束，无需重复操作。');
        }

        $activity->update(['status' => RcSchoolActivityStatus::Ended]);

        return $activity->refresh();
    }

    private function assertCompanyCreatableType(RcSchoolActivityType $type): void
    {
        if (! in_array($type, [RcSchoolActivityType::JobFair, RcSchoolActivityType::Presentation], true)) {
            throw new InvalidArgumentException('企业仅可创建宣讲会或招聘会。');
        }
    }

    private function assertCompanyPublishReady(SchoolActivity $activity): void
    {
        if ($activity->organizer_type !== RcSchoolActivityOrganizerType::Company) {
            return;
        }

        $activity->loadMissing('schools');

        if ($activity->type === RcSchoolActivityType::Presentation && $activity->schools->isEmpty()) {
            throw new InvalidArgumentException('宣讲会请先选择申请入校的目标院校。');
        }

        if ($activity->schools->isNotEmpty()) {
            $this->assertSchoolsAllowCompanyApply($activity->schools);
        }
    }

    private function companyActivityRequiresSchoolTarget(RcSchoolActivityType $type): bool
    {
        return $type === RcSchoolActivityType::Presentation;
    }

    /**
     * @param  array<int, mixed>|null  $schoolCodes
     * @return array<int, int>
     */
    private function resolveCompanySchoolIds(RcSchoolActivityType $type, ?array $schoolCodes): array
    {
        if ($schoolCodes === null) {
            return [];
        }

        $schoolIds = $this->resolveSchoolIdsFromCodes($schoolCodes);

        if ($this->companyActivityRequiresSchoolTarget($type) && $schoolIds === []) {
            throw new InvalidArgumentException('宣讲会请选择申请入校的目标院校。');
        }

        return $schoolIds;
    }

    /**
     * @param  array<int, mixed>|null  $schoolCodes
     */
    private function assertCompanySchoolTargetProvided(
        RcSchoolActivityType $type,
        ?array $schoolCodes,
        SchoolActivity $activity,
    ): void {
        if (! $this->companyActivityRequiresSchoolTarget($type)) {
            return;
        }

        if ($schoolCodes !== null) {
            if ($this->normalizeSchoolCodes($schoolCodes) === []) {
                throw new InvalidArgumentException('宣讲会请选择申请入校的目标院校。');
            }

            return;
        }

        if (! $activity->schools()->exists()) {
            throw new InvalidArgumentException('宣讲会请选择申请入校的目标院校。');
        }
    }

    /**
     * @param  Collection<int, School>  $schools
     */
    private function assertSchoolsAllowCompanyApply(Collection $schools): void
    {
        foreach ($schools as $school) {
            if (blank($school->school_code)) {
                continue;
            }

            $profile = SchoolProfile::query()->where('school_code', $school->school_code)->first();

            if ($profile !== null && ! $profile->allow_company_apply_activity) {
                throw new InvalidArgumentException("院校「{$school->name}」暂未开放企业自主进校申请。");
            }
        }
    }

    /**
     * @param  array<int, mixed>  $schoolCodes
     * @return array<int, int>
     */
    private function resolveSchoolIdsFromCodes(array $schoolCodes): array
    {
        $schoolCodes = $this->normalizeSchoolCodes($schoolCodes);

        if ($schoolCodes === []) {
            return [];
        }

        $schools = School::query()->whereIn('school_code', $schoolCodes)->get();

        if ($schools->count() !== count($schoolCodes)) {
            throw new InvalidArgumentException('存在无效的目标院校代码。');
        }

        $this->assertSchoolsAllowCompanyApply($schools);

        return $schools->pluck('id')->map(static fn (mixed $id): int => (int) $id)->values()->all();
    }

    /**
     * @param  array<int, mixed>  $schoolCodes
     * @return array<int, string>
     */
    private function normalizeSchoolCodes(array $schoolCodes): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $schoolCode): string => trim((string) $schoolCode), $schoolCodes),
            static fn (string $schoolCode): bool => $schoolCode !== '',
        )));
    }

    /**
     * @param  array<int, int>  $schoolIds
     */
    private function syncActivitySchools(SchoolActivity $activity, array $schoolIds): void
    {
        if ($schoolIds === []) {
            SchoolActivitySchool::query()
                ->where('activity_id', $activity->id)
                ->delete();

            return;
        }

        SchoolActivitySchool::query()
            ->where('activity_id', $activity->id)
            ->whereNotIn('school_id', $schoolIds)
            ->delete();

        foreach ($schoolIds as $schoolId) {
            SchoolActivitySchool::query()->firstOrCreate([
                'activity_id' => $activity->id,
                'school_id' => $schoolId,
            ]);
        }
    }
}
