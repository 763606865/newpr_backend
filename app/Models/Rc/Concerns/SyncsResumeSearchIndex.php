<?php

namespace App\Models\Rc\Concerns;

use App\Models\Rc\Resume;
use App\Services\RcResumeAggregateService;

trait SyncsResumeSearchIndex
{
    protected bool $wasPrimaryIntentionBeforeDelete = false;

    protected static function bootSyncsResumeSearchIndex(): void
    {
        static::saved(function (self $model): void {
            if ($model->shouldSyncParentResumeSearchIndex()) {
                $model->syncParentResumeSearchIndex();
            }
        });

        static::deleting(function (self $model): void {
            $model->wasPrimaryIntentionBeforeDelete = $model->shouldSyncParentResumeSearchIndexOnDelete();
        });

        static::deleted(function (self $model): void {
            if ($model->wasPrimaryIntentionBeforeDelete) {
                $model->syncParentResumeSearchIndex();
            }
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
}
