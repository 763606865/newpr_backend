<?php

namespace App\Services;

use App\Models\Rc\Job;

class RcJobDiscoveryService extends Service
{
    public function findPublicJob(int $jobId): ?Job
    {
        $job = Job::query()
            ->with(['company', 'position'])
            ->find($jobId);

        if (! $job instanceof Job) {
            return null;
        }

        return $job->isPubliclySearchable() ? $job : null;
    }
}
