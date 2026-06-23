<?php

namespace App\Observers;

use App\Models\Cms\ArticleTag;
use App\Services\MetaService;

class ArticleTagMetaObserver
{
    public function created(ArticleTag $articleTag): void
    {
        MetaService::make()->forgetArticleTags();
    }

    public function updated(ArticleTag $articleTag): void
    {
        MetaService::make()->forgetArticleTags();
    }

    public function deleted(ArticleTag $articleTag): void
    {
        MetaService::make()->forgetArticleTags();
    }

    public function restored(ArticleTag $articleTag): void
    {
        MetaService::make()->forgetArticleTags();
    }

    public function forceDeleted(ArticleTag $articleTag): void
    {
        MetaService::make()->forgetArticleTags();
    }
}
