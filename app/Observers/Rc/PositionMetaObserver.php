<?php

namespace App\Observers\Rc;

use App\Models\Rc\Position;
use App\Services\MetaService;

class PositionMetaObserver
{
    public function created(Position $position): void
    {
        MetaService::make()->forgetPositions();
    }

    public function updated(Position $position): void
    {
        MetaService::make()->forgetPositions();
    }

    public function deleted(Position $position): void
    {
        MetaService::make()->forgetPositions();
    }

    public function restored(Position $position): void
    {
        MetaService::make()->forgetPositions();
    }

    public function forceDeleted(Position $position): void
    {
        MetaService::make()->forgetPositions();
    }
}
