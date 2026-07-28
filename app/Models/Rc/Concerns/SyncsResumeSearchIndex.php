<?php

namespace App\Models\Rc\Concerns;

use App\Enums\RcResumeRefreshTrigger;
use App\Models\Rc\Resume;
use App\Models\User;
use App\Services\RcResumeAggregateService;
use App\Services\RcResumeRefreshService;

trait SyncsResumeSearchIndex
{
    protected bool $wasPrimaryIntentionBeforeDelete = false;

    protected static function bootSyncsResumeSearchIndex(): void
    {
        static::saved(function (self $model): void {
            if ($model->shouldSyncParentResumeSearchIndex()) {
                $model->syncParentResumeSearchIndex();
            }

            $model->refreshParentResume();
        });

        static::deleting(function (self $model): void {
            $model->wasPrimaryIntentionBeforeDelete = $model->shouldSyncParentResumeSearchIndexOnDelete();
        });

        static::deleted(function (self $model): void {
            if ($model->wasPrimaryIntentionBeforeDelete) {
                $model->syncParentResumeSearchIndex();
            }

            $model->refreshParentResume();
        });
    }

    protected function shouldSyncParentResumeSearchIndex(): bool
    {
        return true;
    }

    protected function shouldSyncParentResumeSearchIndexOnDelete(): bool
    {
        return $this->shouldSyncParentResumeSearchIndex();
    }

    protected function syncParentResumeSearchIndex(): void
    {
        if (! $this->resume_id) {
            return;
        }

        $resume = Resume::query()->find($this->resume_id);

        if (! $resume instanceof Resume) {
            return;
        }

        RcResumeAggregateService::make()->sync($resume);
    }

    protected function refreshParentResume(): void
    {
        if (! $this->resume_id) {
            return;
        }

        $resume = Resume::query()->find($this->resume_id);

        if (! $resume instanceof Resume) {
            return;
        }

        $user = User::query()->find($resume->user_id);

        if (! $user instanceof User) {
            return;
        }

        RcResumeRefreshService::make()->refresh(
            $resume,
            $user,
            RcResumeRefreshTrigger::ResumeUpdated,
        );
    }
}
