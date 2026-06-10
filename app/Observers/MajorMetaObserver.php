<?php

namespace App\Observers;

use App\Models\Major;
use App\Services\MetaService;

class MajorMetaObserver
{
    public function created(Major $major): void
    {
        MetaService::make()->forgetMajors();
    }

    public function updated(Major $major): void
    {
        MetaService::make()->forgetMajors();
    }

    public function deleted(Major $major): void
    {
        MetaService::make()->forgetMajors();
    }
}
