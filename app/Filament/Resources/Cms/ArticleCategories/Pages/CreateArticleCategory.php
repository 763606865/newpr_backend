<?php

namespace App\Filament\Resources\Cms\ArticleCategories\Pages;

use App\Filament\Resources\Cms\ArticleCategories\ArticleCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArticleCategory extends CreateRecord
{
    protected static string $resource = ArticleCategoryResource::class;
}
