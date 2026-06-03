<?php

namespace App\Observers\Rc;

use App\Models\Rc\Industry;
use App\Services\MetaService;

class IndustryMetaObserver
{
    public function created(Industry $industry): void
    {
        MetaService::make()->forgetIndustries();
    }

    public function updated(Industry $industry): void
    {
        MetaService::make()->forgetIndustries();
    }

    public function deleted(Industry $industry): void
    {
        MetaService::make()->forgetIndustries();
    }

    public function restored(Industry $industry): void
    {
        MetaService::make()->forgetIndustries();
    }

    public function forceDeleted(Industry $industry): void
    {
        MetaService::make()->forgetIndustries();
    }
}
