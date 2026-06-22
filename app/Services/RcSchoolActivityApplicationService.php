<?php

namespace App\Services;

use App\Enums\RcSchoolActivityApplyStatus;
use App\Enums\RcSchoolActivityJobAuditStatus;
use App\Enums\RcSchoolActivityJoinSource;
use App\Enums\RcSchoolActivityOrganizerType;
use App\Enums\RcSchoolActivityStatus;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\Rc\SchoolActivity;
use App\Models\Rc\SchoolActivityBooth;
use App\Models\Rc\SchoolActivityCompany;
use App\Models\Rc\SchoolActivityJob;
use App\Models\School;
use App\Models\SchoolProfile;
use App\Support\ScoutQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RcSchoolActivityApplicationService extends Service
{
    public function findCompanyApplication(SchoolActivity $activity, Company $company): ?SchoolActivityCompany
    {
        return SchoolActivityCompany::query()
            ->where('activity_id', $activity->id)
            ->where('company_id', $company->id)
            ->first();
    }

    public function findCompanyApplicationById(SchoolActivity $activity, int $applicationId): ?SchoolActivityCompany
    {
        return SchoolActivityCompany::query()
            ->where('activity_id', $activity->id)
            ->with(['company', 'activityBooth', 'activityJobs.job'])
            ->find($applicationId);
    }

    /**
     * @return LengthAwarePaginator<int, SchoolActivityCompany>
     */
    public function paginateCompanyApplications(SchoolActivity $activity, int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = SchoolActivityCompany::query()
            ->where('activity_id', $activity->id)
            ->with(['company', 'activityBooth'])
            ->withCount('activityJobs')
            ->orderByDesc('apply_at')
            ->orderByDesc('id');

        if (filled($filters['apply_status'] ?? null)) {
            $query->where('apply_status', (int) $filters['apply_status']);
        }

        if (filled($filters['join_source'] ?? null)) {
            $query->where('join_source', (int) $filters['join_source']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @return LengthAwarePaginator<int, SchoolActivityCompany>
     */
    public function paginateParticipatedForCompany(Company $company, int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = SchoolActivityCompany::query()
            ->where('company_id', $company->id)
            ->with(['activity', 'activityBooth'])
            ->withCount('activityJobs')
            ->orderByDesc('apply_at')
            ->orderByDesc('id');

        if (filled($filters['apply_status'] ?? null)) {
            $query->where('apply_status', (int) $filters['apply_status']);
        }

        if (filled($filters['type'] ?? null)) {
            $query->whereHas('activity', fn ($builder) => $builder->where('type', (int) $filters['type']));
        }

        if (filled($filters['keyword'] ?? null)) {
            $keyword = ScoutQuery::escape((string) $filters['keyword']);
            $activityIds = SchoolActivity::search($keyword)->keys()->all();
            $query->whereIn('activity_id', $activityIds !== [] ? $activityIds : [0]);
        }

        if (filled($filters['activity_status'] ?? null)) {
            $query->whereHas('activity', fn ($builder) => $builder->where('status', (int) $filters['activity_status']));
        }

        return $query->paginate($perPage);
    }

    public function ensureOrganizerCompanyApplication(SchoolActivity $activity): SchoolActivityCompany
    {
        if ($activity->organizer_type !== RcSchoolActivityOrganizerType::Company || ! $activity->organizer_id) {
            throw new InvalidArgumentException('仅企业主办的活动可同步主办方参会记录。');
        }

        return SchoolActivityCompany::query()->firstOrCreate(
            [
                'activity_id' => $activity->id,
                'company_id' => (int) $activity->organizer_id,
            ],
            [
                'join_source' => RcSchoolActivityJoinSource::Organizer,
                'apply_status' => RcSchoolActivityApplyStatus::Approved,
            ],
        );
    }

    /**
     * @param  array{name: string, credit_code: string, contact_phone: string}  $companyData
     */
    public function registerCompanyViaInvite(SchoolActivity $activity, array $companyData): SchoolActivityCompany
    {
        if ($activity->status !== RcSchoolActivityStatus::Published) {
            throw new InvalidArgumentException('活动未发布，暂不可接受邀请。');
        }

        $company = RcCompanyService::make()->findOrCreateFromInvite($companyData);

        $existing = $this->findCompanyApplication($activity, $company);

        if ($existing instanceof SchoolActivityCompany) {
            throw new InvalidArgumentException('该企业已关联此活动。');
        }

        return $this->inviteCompany($activity, $company->id);
    }

    public function inviteCompany(
        SchoolActivity $activity,
        int $companyId,
        ?int $activityBoothId = null,
        ?string $remark = null,
    ): SchoolActivityCompany {
        $this->assertSchoolOrganizerActivity($activity);

        if (SchoolActivityCompany::query()->where('activity_id', $activity->id)->where('company_id', $companyId)->exists()) {
            throw new InvalidArgumentException('该企业已关联此活动。');
        }

        $activityBooth = null;

        if ($activityBoothId !== null) {
            $activityBooth = RcSchoolActivityBoothService::make()->resolveAssignableBooth(
                $activity,
                $activityBoothId,
                $companyId,
            );
        }

        return DB::transaction(function () use ($activity, $companyId, $activityBoothId, $remark, $activityBooth): SchoolActivityCompany {
            $application = SchoolActivityCompany::query()->create([
                'activity_id' => $activity->id,
                'company_id' => $companyId,
                'activity_booth_id' => $activityBoothId,
                'join_source' => RcSchoolActivityJoinSource::SchoolInvite,
                'apply_status' => RcSchoolActivityApplyStatus::Approved,
                'remark' => $remark,
            ]);

            if ($activityBooth instanceof SchoolActivityBooth) {
                RcSchoolActivityBoothService::make()->assignCompany($activityBooth, $companyId);
            }

            $application = $application->refresh();

            RcNotificationService::make()->notifySchoolActivityCompanyInvited($application);

            return $application;
        });
    }

    public function applyAsCompany(SchoolActivity $activity, Company $company, ?string $remark = null): SchoolActivityCompany
    {
        if ($activity->status !== RcSchoolActivityStatus::Published) {
            throw new InvalidArgumentException('活动未发布，暂不可申请。');
        }

        if (! SchoolActivity::query()->whereKey($activity->id)->registerOpen()->exists()) {
            throw new InvalidArgumentException('活动不在报名时间内。');
        }

        $existing = $this->findCompanyApplication($activity, $company);

        if ($existing instanceof SchoolActivityCompany) {
            throw new InvalidArgumentException('您已申请参加该活动。');
        }

        $this->assertCompanyApplyAllowed($activity);

        return SchoolActivityCompany::query()->create([
            'activity_id' => $activity->id,
            'company_id' => $company->id,
            'join_source' => RcSchoolActivityJoinSource::CompanyApply,
            'apply_status' => RcSchoolActivityApplyStatus::Pending,
            'remark' => $remark,
        ]);
    }

    public function approveCompanyApplication(
        SchoolActivityCompany $application,
        ?int $activityBoothId = null,
        ?string $remark = null,
    ): SchoolActivityCompany {
        if ($application->apply_status !== RcSchoolActivityApplyStatus::Pending) {
            throw new InvalidArgumentException('仅待审核的企业申请可审批通过。');
        }

        $boothId = $activityBoothId ?? $application->activity_booth_id;
        $activityBooth = null;

        if ($boothId !== null) {
            $activityBooth = RcSchoolActivityBoothService::make()->resolveAssignableBooth(
                $application->activity,
                (int) $boothId,
                (int) $application->company_id,
            );
        }

        return DB::transaction(function () use ($application, $boothId, $remark, $activityBooth): SchoolActivityCompany {
            if ($application->activity_booth_id !== null && $application->activity_booth_id !== $boothId) {
                $previousBooth = SchoolActivityBooth::query()->find($application->activity_booth_id);

                if ($previousBooth instanceof SchoolActivityBooth) {
                    RcSchoolActivityBoothService::make()->releaseCompany($previousBooth);
                }
            }

            $application->fill([
                'apply_status' => RcSchoolActivityApplyStatus::Approved,
                'activity_booth_id' => $boothId,
                'remark' => $remark ?? $application->remark,
            ])->save();

            if ($activityBooth instanceof SchoolActivityBooth) {
                RcSchoolActivityBoothService::make()->assignCompany($activityBooth, (int) $application->company_id);
            }

            $application = $application->refresh();

            RcNotificationService::make()->notifySchoolActivityCompanyApproved($application);

            return $application;
        });
    }

    public function rejectCompanyApplication(SchoolActivityCompany $application, ?string $remark = null): SchoolActivityCompany
    {
        if ($application->apply_status !== RcSchoolActivityApplyStatus::Pending) {
            throw new InvalidArgumentException('仅待审核的企业申请可驳回。');
        }

        $application->fill([
            'apply_status' => RcSchoolActivityApplyStatus::Rejected,
            'remark' => $remark ?? $application->remark,
        ])->save();

        return $application->refresh();
    }

    /**
     * @param  array<int, int>  $jobIds
     * @return Collection<int, SchoolActivityJob>
     */
    public function submitJobs(SchoolActivityCompany $application, array $jobIds): Collection
    {
        if ($application->apply_status !== RcSchoolActivityApplyStatus::Approved) {
            throw new InvalidArgumentException('企业申请尚未通过，无法提交职位。');
        }

        $jobIds = collect($jobIds)->unique()->values();

        if ($jobIds->isEmpty()) {
            throw new InvalidArgumentException('请至少选择一个职位。');
        }

        $jobs = Job::query()
            ->where('company_id', $application->company_id)
            ->whereIn('id', $jobIds)
            ->get();

        if ($jobs->count() !== $jobIds->count()) {
            throw new InvalidArgumentException('存在不属于当前企业的职位。');
        }

        return DB::transaction(function () use ($application, $jobIds): Collection {
            $created = collect();

            foreach ($jobIds as $jobId) {
                $activityJob = SchoolActivityJob::query()->firstOrCreate(
                    [
                        'activity_id' => $application->activity_id,
                        'job_id' => (int) $jobId,
                    ],
                    [
                        'company_id' => $application->company_id,
                        'school_activity_company_id' => $application->id,
                        'audit_status' => RcSchoolActivityJobAuditStatus::Pending,
                    ],
                );

                if (! $activityJob->wasRecentlyCreated && $activityJob->trashed()) {
                    $activityJob->restore();
                    $activityJob->update([
                        'school_activity_company_id' => $application->id,
                        'audit_status' => RcSchoolActivityJobAuditStatus::Pending,
                        'reject_reason' => null,
                        'audit_at' => null,
                    ]);
                }

                $created->push($activityJob->refresh()->load('job'));
            }

            return $created;
        });
    }

    /**
     * @return LengthAwarePaginator<int, SchoolActivityJob>
     */
    public function paginateActivityJobs(SchoolActivity $activity, int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = SchoolActivityJob::query()
            ->where('activity_id', $activity->id)
            ->with(['job', 'company', 'companyApplication'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if (filled($filters['audit_status'] ?? null)) {
            $query->where('audit_status', (int) $filters['audit_status']);
        }

        if (filled($filters['company_id'] ?? null)) {
            $query->where('company_id', (int) $filters['company_id']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @return LengthAwarePaginator<int, SchoolActivityJob>
     */
    public function paginateCompanyActivityJobs(SchoolActivityCompany $application, int $perPage): LengthAwarePaginator
    {
        return SchoolActivityJob::query()
            ->where('school_activity_company_id', $application->id)
            ->with('job')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findActivityJob(SchoolActivity $activity, int $activityJobId): ?SchoolActivityJob
    {
        return SchoolActivityJob::query()
            ->where('activity_id', $activity->id)
            ->with(['job', 'company', 'companyApplication'])
            ->find($activityJobId);
    }

    public function approveActivityJob(SchoolActivityJob $activityJob): SchoolActivityJob
    {
        if ($activityJob->audit_status !== RcSchoolActivityJobAuditStatus::Pending) {
            throw new InvalidArgumentException('仅待审核的职位可审批通过。');
        }

        $activityJob->update([
            'audit_status' => RcSchoolActivityJobAuditStatus::Approved,
            'reject_reason' => null,
            'audit_at' => now(),
        ]);

        return $activityJob->refresh();
    }

    public function rejectActivityJob(SchoolActivityJob $activityJob, string $reason): SchoolActivityJob
    {
        if ($activityJob->audit_status !== RcSchoolActivityJobAuditStatus::Pending) {
            throw new InvalidArgumentException('仅待审核的职位可驳回。');
        }

        $activityJob->update([
            'audit_status' => RcSchoolActivityJobAuditStatus::Rejected,
            'reject_reason' => $reason,
            'audit_at' => now(),
        ]);

        return $activityJob->refresh();
    }

    private function assertSchoolOrganizerActivity(SchoolActivity $activity): void
    {
        if ($activity->organizer_type !== RcSchoolActivityOrganizerType::School || ! $activity->organizer_id) {
            throw new InvalidArgumentException('仅学校主办的活动支持此操作。');
        }
    }

    private function assertCompanyApplyAllowed(SchoolActivity $activity): void
    {
        if ($activity->organizer_type !== RcSchoolActivityOrganizerType::School || ! $activity->organizer_id) {
            return;
        }

        $school = School::query()->find($activity->organizer_id);

        if (! $school instanceof School || blank($school->school_code)) {
            return;
        }

        $profile = SchoolProfile::query()->where('school_code', $school->school_code)->first();

        if ($profile !== null && ! $profile->allow_company_apply_activity) {
            throw new InvalidArgumentException('该院校暂未开放企业自主申请。');
        }
    }
}
