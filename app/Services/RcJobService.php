<?php

namespace App\Services;

use App\Enums\RcIdentityType;
use App\Enums\RcJobStatus;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use App\Support\ScoutQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RcJobService extends Service
{
    public function resolveRecruiterCompany(User $user): ?Company
    {
        $identity = RcCompanyService::make()->resolveRecruiterIdentity($user);

        if (! $identity instanceof UserIdentity) {
            return null;
        }

        if ($identity->identity_type !== RcIdentityType::Recruiter || $identity->organization_type !== 'company' || ! $identity->organization_id) {
            return null;
        }

        return Company::query()->find($identity->organization_id);
    }

    public function findForCompany(Company $company, int $jobId): ?Job
    {
        return Job::query()
            ->where('company_id', $company->id)
            ->with(['position', 'department', 'creator'])
            ->find($jobId);
    }

    /**
     * @return LengthAwarePaginator<int, Job>
     */
    public function paginateForCompany(Company $company, int $perPage, array $filters = []): LengthAwarePaginator
    {
        if (filled($filters['keyword'] ?? null)) {
            return $this->searchForCompany($company, $perPage, $filters);
        }

        $query = Job::query()
            ->where('company_id', $company->id)
            ->with(['position'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if (filled($filters['status'] ?? null)) {
            $query->where('status', (int) $filters['status']);
        }

        if (filled($filters['employment_type'] ?? null)) {
            $query->where('employment_type', (int) $filters['employment_type']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Job>
     */
    private function searchForCompany(Company $company, int $perPage, array $filters): LengthAwarePaginator
    {
        $builder = Job::search(ScoutQuery::escape((string) $filters['keyword']))
            ->where('company_id', $company->id);

        if (filled($filters['status'] ?? null)) {
            $builder->where('status', (int) $filters['status']);
        }

        if (filled($filters['employment_type'] ?? null)) {
            $builder->where('employment_type', (int) $filters['employment_type']);
        }

        return $builder
            ->orderBy('updated_at', 'desc')
            ->query(fn ($query) => $query->with(['position']))
            ->paginate($perPage);
    }

    public function create(User $user, Company $company, array $payload): Job
    {
        return DB::transaction(function () use ($user, $company, $payload): Job {
            $status = $this->resolveStatus($payload);
            $attributes = $this->mapAttributes($payload, $company, $user, $status);

            $job = new Job;
            $job->fill($attributes);

            if ($status === RcJobStatus::Published) {
                $this->applyPublish($job);
            }

            $job->save();

            return $job->refresh()->load(['position', 'department', 'creator']);
        });
    }

    public function update(Job $job, array $payload): Job
    {
        return DB::transaction(function () use ($job, $payload): Job {
            $status = array_key_exists('status', $payload)
                ? $this->resolveStatus($payload)
                : $job->status;

            $attributes = $this->mapAttributes($payload, $job->company, $job->creator, $status, $job);
            $job->fill(Arr::except($attributes, ['code', 'company_id', 'creator_user_id']));

            if ($status === RcJobStatus::Published && $job->published_at === null) {
                $this->applyPublish($job);
            } elseif ($status !== RcJobStatus::Published) {
                $job->status = $status;
            }

            $job->save();

            return $job->refresh()->load(['position', 'department', 'creator']);
        });
    }

    public function publish(Job $job): Job
    {
        $message = $this->publishableMessage($job);

        if ($message !== null) {
            throw new \InvalidArgumentException($message);
        }

        return DB::transaction(function () use ($job): Job {
            $this->applyPublish($job);
            $job->save();

            return $job->refresh()->load(['position', 'department', 'creator']);
        });
    }

    public function pause(Job $job): Job
    {
        $job->status = RcJobStatus::Paused;
        $job->save();

        return $job->refresh()->load(['position', 'department', 'creator']);
    }

    public function close(Job $job): Job
    {
        $job->status = RcJobStatus::Closed;
        $job->save();

        return $job->refresh()->load(['position', 'department', 'creator']);
    }

    public function delete(Job $job): void
    {
        $job->delete();
    }

    public function publishableMessage(Job $job): ?string
    {
        if (blank($job->title)) {
            return '请填写职位名称。';
        }

        if (blank($job->position_code)) {
            return '请选择职位类别。';
        }

        if (blank($job->description)) {
            return '请填写职位描述。';
        }

        if (blank($job->workplace)) {
            return '请输入工作地址。';
        }

        if ($job->education_level === null) {
            return '请选择最低学历。';
        }

        if ($job->headcount < 1) {
            return '请填写招聘人数。';
        }

        return null;
    }

    public function generateCode(Company $company): string
    {
        do {
            $code = 'JOB'.now()->format('YmdHis').strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while (Job::query()->where('company_id', $company->id)->where('code', $code)->exists());

        return $code;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function mapAttributes(array $payload, Company $company, ?User $creator, RcJobStatus $status, ?Job $existing = null): array
    {
        $extra = $this->mergeExtra($payload, $existing?->extra ?? []);

        $attributes = [
            'company_id' => $company->id,
            'creator_user_id' => $creator?->id ?? $existing?->creator_user_id,
            'code' => $existing?->code ?? $this->generateCode($company),
            'title' => (string) ($payload['title'] ?? $existing?->title ?? ''),
            'employment_type' => (int) ($payload['employment_type'] ?? $existing?->employment_type?->value ?? 1),
            'position_code' => $payload['position_code'] ?? $existing?->position_code,
            'city_code' => $payload['city_code'] ?? $existing?->city_code,
            'workplace' => $payload['workplace'] ?? $existing?->workplace,
            'salary_min' => $payload['salary_min'] ?? $existing?->salary_min,
            'salary_max' => $payload['salary_max'] ?? $existing?->salary_max,
            'salary_unit' => (int) ($payload['salary_unit'] ?? $existing?->salary_unit?->value ?? 1),
            'experience_min' => $payload['experience_min'] ?? $existing?->experience_min,
            'experience_max' => $payload['experience_max'] ?? $existing?->experience_max,
            'education_level' => isset($payload['education_level'])
                ? (int) $payload['education_level']
                : $existing?->education_level,
            'headcount' => (int) ($payload['headcount'] ?? $existing?->headcount ?? 1),
            'description' => $payload['description'] ?? $existing?->description,
            'requirement' => $payload['requirement'] ?? $existing?->requirement,
            'benefit' => $payload['benefit'] ?? $existing?->benefit,
            'status' => $status,
            'expired_at' => $payload['expired_at'] ?? $existing?->expired_at,
            'extra' => $extra,
        ];

        if (array_key_exists('department_id', $payload)) {
            $attributes['department_id'] = $payload['department_id'];
        } elseif ($existing) {
            $attributes['department_id'] = $existing->department_id;
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveStatus(array $payload): RcJobStatus
    {
        if (! array_key_exists('status', $payload) || $payload['status'] === null) {
            return RcJobStatus::Draft;
        }

        return RcJobStatus::from((int) $payload['status']);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $currentExtra
     * @return array<string, mixed>
     */
    private function mergeExtra(array $payload, array $currentExtra): array
    {
        $extra = $currentExtra;

        if (array_key_exists('keywords', $payload)) {
            $extra['keywords'] = collect($payload['keywords'] ?? [])
                ->filter(static fn (mixed $keyword): bool => filled($keyword))
                ->map(static fn (mixed $keyword): string => trim((string) $keyword))
                ->unique()
                ->values()
                ->all();
        }

        if (array_key_exists('show_headcount', $payload)) {
            $extra['show_headcount'] = (bool) $payload['show_headcount'];
        } elseif (! array_key_exists('show_headcount', $extra)) {
            $extra['show_headcount'] = true;
        }

        return $extra;
    }

    private function applyPublish(Job $job): void
    {
        $message = $this->publishableMessage($job);

        if ($message !== null) {
            throw new \InvalidArgumentException($message);
        }

        $job->status = RcJobStatus::Published;
        $job->published_at ??= now();
    }
}
