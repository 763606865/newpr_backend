<?php

namespace App\Observers;

use App\Models\Cms\ArticleCategory;
use App\Services\MetaService;

class ArticleCategoryMetaObserver
{
    public function created(ArticleCategory $articleCategory): void
    {
        MetaService::make()->forgetArticleCategories();
    }

    public function updated(ArticleCategory $articleCategory): void
    {
        MetaService::make()->forgetArticleCategories();
    }

    public function deleted(ArticleCategory $articleCategory): void
    {
        MetaService::make()->forgetArticleCategories();
    }

    public function restored(ArticleCategory $articleCategory): void
    {
        MetaService::make()->forgetArticleCategories();
    }

    public function forceDeleted(ArticleCategory $articleCategory): void
    {
        MetaService::make()->forgetArticleCategories();
    }
}
