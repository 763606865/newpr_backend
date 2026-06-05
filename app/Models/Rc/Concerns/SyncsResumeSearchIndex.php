<?php

namespace App\Models\Rc\Concerns;

use App\Models\Rc\Resume;

trait SyncsResumeSearchIndex
{
    protected static function bootSyncsResumeSearchIndex(): void
    {
        $sync = function (self $model): void {
            $resume = $model->resume;

            if (! $resume instanceof Resume) {
                return;
            }

            if ($resume->shouldBeSearchable()) {
                $resume->searchable();
            } else {
                $resume->unsearchable();
            }
        };

        static::saved($sync);
        static::deleted($sync);
    }
}
