<?php

namespace App\Services;

use App\Enums\RcResumeStatus;
use App\Models\Rc\Resume;

class RcResumeDiscoveryService extends Service
{
    public function findDiscoverableResume(int $resumeId, bool $withDetails = false): ?Resume
    {
        $query = Resume::query();

        if ($withDetails) {
            $query->with([
                'works' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
                'educations' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
                'intentions' => static fn ($relation) => $relation->orderByDesc('updated_at')->orderByDesc('id'),
            ]);
        }

        $resume = $query->find($resumeId);

        if (! $resume instanceof Resume) {
            return null;
        }

        $status = $resume->status instanceof RcResumeStatus
            ? $resume->status
            : RcResumeStatus::tryFrom((int) $resume->status);

        return $status === RcResumeStatus::Normal ? $resume : null;
    }
}
