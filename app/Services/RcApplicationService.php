<?php

namespace App\Services;

use App\Enums\RcApplicationFlowActionType;
use App\Enums\RcApplicationSourceType;
use App\Enums\RcApplicationStatus;
use App\Enums\RcInterviewMode;
use App\Enums\RcInterviewStatus;
use App\Enums\RcJobStageStatus;
use App\Enums\RcOfferStatus;
use App\Enums\RcResumeStatus;
use App\Enums\RcSalaryUnit;
use App\Models\Company;
use App\Models\Rc\Application;
use App\Models\Rc\ApplicationFlow;
use App\Models\Rc\Interview;
use App\Models\Rc\Job;
use App\Models\Rc\JobStage;
use App\Models\Rc\Offer;
use App\Models\Rc\Resume;
use App\Models\User;
use App\Resources\Rc\RcApplicationResumeResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RcApplicationService extends Service
{
    public function hasUserAppliedToJob(User $user, Job $job): bool
    {
        return isset($this->getAppliedJobIdsForUser($user, [$job->id])[$job->id]);
    }

    /**
     * 批量查询用户已投递（非撤回）的职位 ID，返回 job_id => true 映射便于 O(1) 判断。
     *
     * @param  list<int>  $jobIds
     * @return array<int, true>
     */
    public function getAppliedJobIdsForUser(User $user, array $jobIds): array
    {
        if ($jobIds === []) {
            return [];
        }

        $appliedJobIds = Application::query()
            ->where('candidate_user_id', $user->id)
            ->whereIn('job_id', $jobIds)
            ->where('status', '!=', RcApplicationStatus::Withdrawn->value)
            ->pluck('job_id')
            ->map(fn (mixed $jobId): int => (int) $jobId)
            ->all();

        return array_fill_keys($appliedJobIds, true);
    }

    public function apply(User $user, Job $job, ?int $resumeId = null): Application
    {
        if (! $job->isPubliclySearchable()) {
            throw new InvalidArgumentException('该职位暂不可投递。');
        }

        $resume = $this->resolveResume($user, $resumeId);
        $defaultStage = $this->resolveDefaultStage($job);

        return DB::transaction(function () use ($user, $job, $resume, $defaultStage): Application {
            $existing = Application::query()
                ->where('company_id', $job->company_id)
                ->where('job_id', $job->id)
                ->where('candidate_user_id', $user->id)
                ->first();

            if ($existing instanceof Application && $existing->status !== RcApplicationStatus::Withdrawn) {
                throw new InvalidArgumentException('您已投递过该职位。');
            }

            $snapshot = $this->buildResumeSnapshot($resume);
            $appliedAt = now();

            if ($existing instanceof Application) {
                $existing->fill([
                    'resume_id' => $resume->id,
                    'current_stage_id' => $defaultStage?->id,
                    'source_type' => RcApplicationSourceType::Direct,
                    'status' => RcApplicationStatus::Pending,
                    'applied_at' => $appliedAt,
                    'withdrawn_at' => null,
                    'resume_snapshot' => $snapshot,
                ]);
                $existing->save();

                $this->recordFlow(
                    application: $existing,
                    user: $user,
                    toStageId: $defaultStage?->id,
                    actionType: RcApplicationFlowActionType::Transfer,
                    note: '求职者重新投递',
                );

                return $existing->refresh()->load(['job.company', 'job.position', 'resume', 'company']);
            }

            $application = Application::query()->create([
                'company_id' => $job->company_id,
                'job_id' => $job->id,
                'candidate_user_id' => $user->id,
                'resume_id' => $resume->id,
                'current_stage_id' => $defaultStage?->id,
                'source_type' => RcApplicationSourceType::Direct,
                'status' => RcApplicationStatus::Pending,
                'applied_at' => $appliedAt,
                'resume_snapshot' => $snapshot,
            ]);

            $this->recordFlow(
                application: $application,
                user: $user,
                toStageId: $defaultStage?->id,
                actionType: RcApplicationFlowActionType::Transfer,
                note: '求职者主动投递',
            );

            return $application->load(['job.company', 'job.position', 'resume', 'company']);
        });
    }

    public function withdraw(User $user, Application $application): Application
    {
        if ($application->candidate_user_id !== $user->id) {
            throw new InvalidArgumentException('无权操作该投递记录。');
        }

        if (! $this->isWithdrawable($application)) {
            throw new InvalidArgumentException('当前状态不可撤回。');
        }

        return DB::transaction(function () use ($user, $application): Application {
            $application->fill([
                'status' => RcApplicationStatus::Withdrawn,
                'withdrawn_at' => now(),
            ]);
            $application->save();

            $this->recordFlow(
                application: $application,
                user: $user,
                toStageId: null,
                fromStageId: $application->current_stage_id,
                actionType: RcApplicationFlowActionType::Withdraw,
                note: '求职者撤回投递',
            );

            return $application->refresh()->load(['job.company', 'job.position', 'resume', 'company']);
        });
    }

    /**
     * @return LengthAwarePaginator<int, Application>
     */
    public function paginateForCandidate(User $user, int $perPage): LengthAwarePaginator
    {
        return Application::query()
            ->where('candidate_user_id', $user->id)
            ->with(['job.company', 'job.position', 'resume', 'company'])
            ->orderByDesc('applied_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findForCandidate(User $user, int $applicationId): ?Application
    {
        return Application::query()
            ->where('candidate_user_id', $user->id)
            ->with([
                'job.company',
                'job.position',
                'resume.works' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
                'resume.educations' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
                'resume.languages' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
                'resume.skills' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
                'company',
            ])
            ->whereKey($applicationId)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Application>
     */
    public function paginateForCompany(Company $company, int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = Application::query()
            ->where('company_id', $company->id)
            ->with(['job.position', 'company']);

        if (filled($filters['job_id'] ?? null)) {
            $query->where('job_id', (int) $filters['job_id']);
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', (int) $filters['status']);
        }

        return $query
            ->orderByDesc('applied_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findForCompany(Company $company, int $applicationId): ?Application
    {
        return Application::query()
            ->where('company_id', $company->id)
            ->with(['job.company', 'job.position', 'company'])
            ->whereKey($applicationId)
            ->first();
    }

    public function markScreeningOnRecruiterView(User $operator, Application $application): Application
    {
        if ($application->status !== RcApplicationStatus::Pending) {
            return $application;
        }

        return DB::transaction(function () use ($operator, $application): Application {
            $fromStageId = $application->current_stage_id;

            $application->fill([
                'status' => RcApplicationStatus::Screening,
            ]);
            $application->save();

            $this->recordFlow(
                application: $application,
                user: $operator,
                fromStageId: $fromStageId,
                toStageId: $application->current_stage_id,
                actionType: RcApplicationFlowActionType::Transfer,
                note: '招聘方查看投递详情',
            );

            return $this->refreshForRecruiter($application);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function inviteInterview(User $operator, Application $application, array $payload): Application
    {
        $this->assertStatusIn($application, [
            RcApplicationStatus::Pending,
            RcApplicationStatus::Screening,
        ], '当前状态不可邀请面试。');

        return DB::transaction(function () use ($operator, $application, $payload): Application {
            $fromStageId = $application->current_stage_id;

            Interview::query()->create([
                'company_id' => $application->company_id,
                'application_id' => $application->id,
                'stage_id' => $application->current_stage_id,
                'interviewer_user_id' => filled($payload['interviewer_user_id'] ?? null)
                    ? (int) $payload['interviewer_user_id']
                    : null,
                'interviewer_name' => $payload['interviewer_name'] ?? null,
                'interview_at' => $payload['interview_at'],
                'duration_mins' => filled($payload['duration_mins'] ?? null)
                    ? (int) $payload['duration_mins']
                    : null,
                'mode' => RcInterviewMode::from((int) $payload['mode']),
                'status' => RcInterviewStatus::Scheduled,
                'location' => $payload['location'] ?? null,
                'meeting_url' => $payload['meeting_url'] ?? null,
                'note' => $payload['note'] ?? null,
            ]);

            $application->fill([
                'status' => RcApplicationStatus::Interviewing,
            ]);
            $application->save();

            $this->recordFlow(
                application: $application,
                user: $operator,
                fromStageId: $fromStageId,
                toStageId: $application->current_stage_id,
                actionType: RcApplicationFlowActionType::Transfer,
                note: '招聘方邀请面试',
            );

            return $this->refreshForRecruiter($application);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function sendOffer(User $operator, Application $application, array $payload): Application
    {
        $this->assertStatusIn($application, [
            RcApplicationStatus::Interviewing,
        ], '当前状态不可发送 Offer。');

        return DB::transaction(function () use ($operator, $application, $payload): Application {
            $fromStageId = $application->current_stage_id;
            $sentAt = now();

            $offer = Offer::query()
                ->where('company_id', $application->company_id)
                ->where('application_id', $application->id)
                ->first();

            $offerAttributes = [
                'salary_min' => $payload['salary_min'] ?? null,
                'salary_max' => $payload['salary_max'] ?? null,
                'salary_unit' => filled($payload['salary_unit'] ?? null)
                    ? RcSalaryUnit::from((int) $payload['salary_unit'])
                    : RcSalaryUnit::Month,
                'entry_date' => $payload['entry_date'] ?? null,
                'expire_date' => $payload['expire_date'] ?? null,
                'status' => RcOfferStatus::Sent,
                'sent_at' => $sentAt,
                'note' => $payload['note'] ?? null,
            ];

            if ($offer instanceof Offer) {
                if (in_array($offer->status, [RcOfferStatus::Accepted, RcOfferStatus::Rejected, RcOfferStatus::Revoked], true)) {
                    throw new InvalidArgumentException('该投递的 Offer 已结束，无法重新发送。');
                }

                $offer->fill($offerAttributes);
                $offer->save();
            } else {
                Offer::query()->create([
                    'company_id' => $application->company_id,
                    'application_id' => $application->id,
                    'offer_no' => $this->generateOfferNo($application),
                    ...$offerAttributes,
                ]);
            }

            $application->fill([
                'status' => RcApplicationStatus::Offering,
            ]);
            $application->save();

            $this->recordFlow(
                application: $application,
                user: $operator,
                fromStageId: $fromStageId,
                toStageId: $application->current_stage_id,
                actionType: RcApplicationFlowActionType::Transfer,
                note: '招聘方发送 Offer',
            );

            return $this->refreshForRecruiter($application);
        });
    }

    public function hire(User $operator, Application $application, ?string $note = null): Application
    {
        $this->assertStatusIn($application, [
            RcApplicationStatus::Offering,
        ], '当前状态不可确认录用。');

        return DB::transaction(function () use ($operator, $application, $note): Application {
            $fromStageId = $application->current_stage_id;

            $application->fill([
                'status' => RcApplicationStatus::Hired,
            ]);
            $application->save();

            Offer::query()
                ->where('company_id', $application->company_id)
                ->where('application_id', $application->id)
                ->where('status', RcOfferStatus::Sent->value)
                ->update([
                    'status' => RcOfferStatus::Accepted->value,
                    'replied_at' => now(),
                ]);

            $this->recordFlow(
                application: $application,
                user: $operator,
                fromStageId: $fromStageId,
                toStageId: $application->current_stage_id,
                actionType: RcApplicationFlowActionType::Hire,
                note: $note ?? '招聘方确认录用',
            );

            return $this->refreshForRecruiter($application);
        });
    }

    public function reject(User $operator, Application $application, ?string $note = null): Application
    {
        $this->assertStatusIn($application, [
            RcApplicationStatus::Pending,
            RcApplicationStatus::Screening,
            RcApplicationStatus::Interviewing,
            RcApplicationStatus::Offering,
        ], '当前状态不可淘汰。');

        return DB::transaction(function () use ($operator, $application, $note): Application {
            $fromStageId = $application->current_stage_id;

            $application->fill([
                'status' => RcApplicationStatus::Rejected,
            ]);
            $application->save();

            $this->recordFlow(
                application: $application,
                user: $operator,
                fromStageId: $fromStageId,
                toStageId: $application->current_stage_id,
                actionType: RcApplicationFlowActionType::Reject,
                note: $note ?? '招聘方淘汰候选人',
            );

            return $this->refreshForRecruiter($application);
        });
    }

    private function resolveResume(User $user, ?int $resumeId): Resume
    {
        $query = Resume::query()
            ->where('user_id', $user->id)
            ->where('status', RcResumeStatus::Normal->value);

        if ($resumeId !== null) {
            $resume = $query->whereKey($resumeId)->first();
        } else {
            $resume = (clone $query)
                ->orderByDesc('is_primary')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();
        }

        if (! $resume instanceof Resume) {
            throw new InvalidArgumentException($resumeId !== null ? '简历不存在或不可用。' : '请先创建一份可用简历后再投递。');
        }

        return $resume;
    }

    private function resolveDefaultStage(Job $job): ?JobStage
    {
        return JobStage::query()
            ->where('company_id', $job->company_id)
            ->where('is_default', 1)
            ->where('status', RcJobStageStatus::Enabled->value)
            ->orderBy('sort')
            ->orderBy('id')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveResumeSnapshotForDisplay(Application $application): array
    {
        $snapshot = RcApplicationResumeResource::normalizeStoredSnapshot(
            is_array($application->resume_snapshot) ? $application->resume_snapshot : [],
        );

        if ($this->snapshotContainsRelationData($snapshot)) {
            return $snapshot;
        }

        $resume = Resume::query()->find($application->resume_id);

        if (! $resume instanceof Resume) {
            return $snapshot;
        }

        return (new RcApplicationResumeResource($resume))->resolve(request());
    }

    private function buildResumeSnapshot(Resume $resume): array
    {
        $freshResume = Resume::query()->whereKey($resume->id)->firstOrFail();

        return (new RcApplicationResumeResource($freshResume))->resolve(request());
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function snapshotContainsRelationData(array $snapshot): bool
    {
        foreach (['works', 'educations', 'languages', 'skills'] as $section) {
            if (! empty($snapshot[$section])) {
                return true;
            }
        }

        return false;
    }

    private function isWithdrawable(Application $application): bool
    {
        return in_array($application->status, [
            RcApplicationStatus::Pending,
            RcApplicationStatus::Screening,
            RcApplicationStatus::Interviewing,
            RcApplicationStatus::Offering,
        ], true);
    }

    private function recordFlow(
        Application $application,
        User $user,
        ?int $toStageId = null,
        ?int $fromStageId = null,
        RcApplicationFlowActionType $actionType = RcApplicationFlowActionType::Transfer,
        ?string $note = null,
    ): void {
        ApplicationFlow::query()->create([
            'company_id' => $application->company_id,
            'application_id' => $application->id,
            'from_stage_id' => $fromStageId,
            'to_stage_id' => $toStageId,
            'action_type' => $actionType,
            'operator_user_id' => $user->id,
            'note' => $note,
            'happened_at' => now(),
        ]);
    }

    /**
     * @param  list<RcApplicationStatus>  $allowedStatuses
     */
    private function assertStatusIn(Application $application, array $allowedStatuses, string $message): void
    {
        if (! in_array($application->status, $allowedStatuses, true)) {
            throw new InvalidArgumentException($message);
        }
    }

    private function refreshWithRelations(Application $application): Application
    {
        return $application->refresh()->load(['job.company', 'job.position', 'resume', 'company']);
    }

    private function refreshForRecruiter(Application $application): Application
    {
        return $application->refresh()->load(['job.company', 'job.position', 'company']);
    }

    private function generateOfferNo(Application $application): string
    {
        return sprintf('OFFER-%d-%06d-%s', $application->company_id, $application->id, now()->format('YmdHis'));
    }
}
