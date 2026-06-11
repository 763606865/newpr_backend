<?php

namespace App\Services;

use App\Enums\RcApplicationFlowActionType;
use App\Enums\RcApplicationSourceType;
use App\Enums\RcApplicationStatus;
use App\Enums\RcJobStageStatus;
use App\Enums\RcResumeStatus;
use App\Models\Company;
use App\Models\Rc\Application;
use App\Models\Rc\ApplicationFlow;
use App\Models\Rc\Job;
use App\Models\Rc\JobStage;
use App\Models\Rc\Resume;
use App\Models\User;
use App\Resources\Rc\RcResumeResource;
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
            ->with(['job.company', 'job.position', 'resume', 'company'])
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
    private function buildResumeSnapshot(Resume $resume): array
    {
        $resume->loadMissing([
            'works' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
            'educations' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
            'intentions' => static fn ($relation) => $relation->orderByDesc('updated_at')->orderByDesc('id'),
        ]);

        return (new RcResumeResource($resume))->resolve(request());
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
}
