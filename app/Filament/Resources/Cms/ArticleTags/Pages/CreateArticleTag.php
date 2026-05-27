<?php

namespace App\Filament\Resources\Cms\ArticleTags\Pages;

use App\Filament\Resources\Cms\ArticleTags\ArticleTagResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArticleTag extends CreateRecord
{
    protected static string $resource = ArticleTagResource::class;
}
