<?php

namespace App\Filament\Resources\Cms\Articles\Pages;

use App\Enums\CmsPublishStatus;
use App\Filament\Resources\Cms\Articles\ArticleResource;
use App\Filament\Resources\Cms\Widgets\CmsResourceStats;
use App\Models\Cms\Article;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListArticles extends ListRecords
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CmsResourceStats::make([
                'modelClass' => Article::class,
                'cityColumn' => 'city_code',
                'statusCards' => [
                    ['label' => '草稿', 'value' => CmsPublishStatus::Draft->value, 'color' => 'gray'],
                    ['label' => '已发布', 'value' => CmsPublishStatus::Published->value, 'color' => 'success'],
                    ['label' => '下线', 'value' => CmsPublishStatus::Offline->value, 'color' => 'danger'],
                ],
            ]),
        ];
    }
}
