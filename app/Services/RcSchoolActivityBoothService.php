<?php

namespace App\Services;

use App\Enums\RcSchoolActivityStatus;
use App\Enums\RcSchoolBoothStatus;
use App\Models\Rc\SchoolActivity;
use App\Models\Rc\SchoolActivityBooth;
use App\Models\Rc\SchoolBooth;
use App\Models\Rc\SchoolBoothArea;
use App\Models\School;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RcSchoolActivityBoothService extends Service
{
    public function assertBoothConfigEditable(SchoolActivity $activity): void
    {
        if ($activity->status !== RcSchoolActivityStatus::Draft) {
            throw new InvalidArgumentException('活动已发布或已结束，不可修改展位配置。');
        }

        if ($activity->companyApplications()->exists()) {
            throw new InvalidArgumentException('活动已有企业报名记录，不可修改展位配置。');
        }
    }

    public function findBoothForSchool(School $school, int $boothId): ?SchoolBooth
    {
        if (blank($school->school_code)) {
            return null;
        }

        return SchoolBooth::query()
            ->forSchoolCode($school->school_code)
            ->with(['areas' => fn ($query) => $query->ordered()])
            ->find($boothId);
    }

    public function syncForActivity(SchoolActivity $activity, School $school, int $boothId): Collection
    {
        $booth = $this->findBoothForSchool($school, $boothId);

        if (! $booth instanceof SchoolBooth) {
            throw new InvalidArgumentException('展位模板不存在或不属于当前学校。');
        }

        if ($booth->status !== RcSchoolBoothStatus::Enabled) {
            throw new InvalidArgumentException('所选展位模板未启用。');
        }

        if ($booth->areas->isEmpty()) {
            throw new InvalidArgumentException('所选展位模板尚未配置展区。');
        }

        return DB::transaction(function () use ($activity, $school, $booth): Collection {
            SchoolActivityBooth::query()
                ->where('activity_id', $activity->id)
                ->delete();

            $created = collect();

            foreach ($booth->areas as $area) {
                for ($number = $area->start_no; $number <= $area->end_no; $number++) {
                    $created->push(SchoolActivityBooth::query()->create([
                        'activity_id' => $activity->id,
                        'booth_id' => $booth->id,
                        'school_id' => $school->id,
                        'booth_area_id' => $area->id,
                        'booth_area_code' => $area->code ?? '',
                        'booth_area_name' => $area->name,
                        'booth_no' => $this->formatBoothNo($area, $number),
                        'status' => RcSchoolBoothStatus::Enabled,
                    ]));
                }
            }

            $activity->update(['booth_id' => $booth->id]);

            return $created;
        });
    }

    public function resolveAssignableBooth(SchoolActivity $activity, int $activityBoothId, int $companyId): SchoolActivityBooth
    {
        $activityBooth = SchoolActivityBooth::query()
            ->where('activity_id', $activity->id)
            ->find($activityBoothId);

        if (! $activityBooth instanceof SchoolActivityBooth) {
            throw new InvalidArgumentException('活动展位不存在。');
        }

        if ($activityBooth->status !== RcSchoolBoothStatus::Enabled) {
            throw new InvalidArgumentException('活动展位未启用。');
        }

        if ($activityBooth->company_id !== null && (int) $activityBooth->company_id !== $companyId) {
            throw new InvalidArgumentException('活动展位已被其他企业占用。');
        }

        return $activityBooth;
    }

    public function assignCompany(SchoolActivityBooth $activityBooth, int $companyId): SchoolActivityBooth
    {
        $activityBooth->update(['company_id' => $companyId]);

        return $activityBooth->refresh();
    }

    public function releaseCompany(SchoolActivityBooth $activityBooth): SchoolActivityBooth
    {
        $activityBooth->update(['company_id' => null]);

        return $activityBooth->refresh();
    }

    private function formatBoothNo(SchoolBoothArea $area, int $number): string
    {
        $prefix = filled($area->code) ? (string) $area->code : (string) $area->name;

        return $prefix.'-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT);
    }
}
