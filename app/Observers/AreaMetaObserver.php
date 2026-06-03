<?php

namespace App\Observers;

use App\Models\Area;
use App\Services\MetaService;

class AreaMetaObserver
{
    public function created(Area $area): void
    {
        MetaService::make()->forgetAreas();
    }

    public function updated(Area $area): void
    {
        MetaService::make()->forgetAreas();
    }

    public function deleted(Area $area): void
    {
        MetaService::make()->forgetAreas();
    }

    public function restored(Area $area): void
    {
        MetaService::make()->forgetAreas();
    }

    public function forceDeleted(Area $area): void
    {
        MetaService::make()->forgetAreas();
    }
}
