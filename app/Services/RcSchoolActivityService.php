<?php

namespace App\Services;

use App\Enums\RcSchoolActivityOrganizerType;
use App\Enums\RcSchoolActivityStatus;
use App\Models\Rc\SchoolActivity;
use App\Models\Rc\SchoolActivitySchool;
use App\Models\School;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
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
            ->withCount(['companyApplications', 'jobs', 'activityBooths'])
            ->find($activityId);
    }

    public function findDetailForSchoolOrganizer(School $school, int $activityId): ?SchoolActivity
    {
        return SchoolActivity::query()
            ->forOrganizer(RcSchoolActivityOrganizerType::School, $school->id)
            ->withCount(['companyApplications', 'jobs', 'activityBooths'])
            ->with([
                'booth.areas' => fn ($query) => $query->ordered(),
                'activityBooths' => fn ($query) => $query
                    ->with('company:id,name')
                    ->orderBy('booth_no')
                    ->orderBy('id'),
            ])
            ->find($activityId);
    }

    public function findPublished(int $activityId): ?SchoolActivity
    {
        return SchoolActivity::query()
            ->published()
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
    public function update(SchoolActivity $activity, array $data): SchoolActivity
    {
        if ($activity->status === RcSchoolActivityStatus::Ended) {
            throw new InvalidArgumentException('已结束的活动不可编辑。');
        }

        $boothId = array_key_exists('booth_id', $data) ? (int) $data['booth_id'] : null;

        if ($boothId !== null) {
            RcSchoolActivityBoothService::make()->assertBoothConfigEditable($activity);
        }

        return DB::transaction(function () use ($activity, $data, $boothId): SchoolActivity {
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
                'is_hot',
                'sort',
                'files',
                'extra',
                'remark',
            ]))->save();

            if ($boothId !== null && (int) $activity->booth_id !== $boothId) {
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

        if (blank($activity->booth_id)) {
            throw new InvalidArgumentException('请先配置活动展位后再发布。');
        }

        $activity->update(['status' => RcSchoolActivityStatus::Published]);

        return $activity->refresh();
    }

    public function end(SchoolActivity $activity): SchoolActivity
    {
        if ($activity->status === RcSchoolActivityStatus::Ended) {
            throw new InvalidArgumentException('活动已结束，无需重复操作。');
        }

        $activity->update(['status' => RcSchoolActivityStatus::Ended]);

        return $activity->refresh();
    }
}
