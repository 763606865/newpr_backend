<?php

namespace App\Models\Rc\Concerns;

use App\Models\Rc\Resume;
use App\Services\RcResumeAggregateService;

trait SyncsResumeSearchIndex
{
    protected static function bootSyncsResumeSearchIndex(): void
    {
        $sync = function (self $model): void {
            if (! $model->resume_id) {
                return;
            }

            $resume = Resume::query()->find($model->resume_id);

            if (! $resume instanceof Resume) {
                return;
            }

            RcResumeAggregateService::make()->sync($resume);
        };

        static::saved($sync);
        static::deleted($sync);
    }
}
