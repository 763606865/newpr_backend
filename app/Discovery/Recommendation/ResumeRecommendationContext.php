<?php

namespace App\Discovery\Recommendation;

use App\Enums\RcJobStatus;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\User;
use Illuminate\Support\Carbon;

final class ResumeRecommendationContext
{
    public function __construct(
        public readonly User $user,
        public readonly Company $company,
        public readonly ?int $jobIdHint = null,
    ) {}

    public function resolvedJob(): ?Job
    {
        if ($this->jobIdHint !== null) {
            $job = Job::query()
                ->where('company_id', $this->company->id)
                ->whereKey($this->jobIdHint)
                ->first();

            return $this->isRecommendableJob($job) ? $job : null;
        }

        $job = Job::query()
            ->where('company_id', $this->company->id)
            ->where('status', RcJobStatus::Published->value)
            ->where(function ($query): void {
                $query
                    ->whereNull('expired_at')
                    ->orWhere('expired_at', '>=', now());
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        return $this->isRecommendableJob($job) ? $job : null;
    }

    private function isRecommendableJob(?Job $job): bool
    {
        if (! $job instanceof Job) {
            return false;
        }

        if ($job->status !== RcJobStatus::Published) {
            return false;
        }

        $expiredAt = $job->expired_at;

        return ! ($expiredAt instanceof Carbon && $expiredAt->isPast());
    }
}
