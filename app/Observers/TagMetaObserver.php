<?php

namespace App\Observers;

use App\Models\Cms\Tag;
use App\Services\MetaService;

class TagMetaObserver
{
    public function created(Tag $tag): void
    {
        MetaService::make()->forgetTags();
    }

    public function updated(Tag $tag): void
    {
        MetaService::make()->forgetTags();
    }

    public function deleted(Tag $tag): void
    {
        MetaService::make()->forgetTags();
    }

    public function restored(Tag $tag): void
    {
        MetaService::make()->forgetTags();
    }

    public function forceDeleted(Tag $tag): void
    {
        MetaService::make()->forgetTags();
    }
}
