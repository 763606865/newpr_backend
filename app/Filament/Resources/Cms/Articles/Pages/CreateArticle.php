<?php

namespace App\Filament\Resources\Cms\Articles\Pages;

use App\Filament\Resources\Cms\Articles\ArticleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;
}
