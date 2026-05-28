<?php

namespace App\Filament\Resources\Cms\ArticleCategories\Pages;

use App\Enums\CmsStatus;
use App\Filament\Resources\Cms\ArticleCategories\ArticleCategoryResource;
use App\Filament\Resources\Cms\Widgets\CmsResourceStats;
use App\Models\Cms\ArticleCategory;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListArticleCategories extends ListRecords
{
    protected static string $resource = ArticleCategoryResource::class;

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
                'modelClass' => ArticleCategory::class,
                'statusCards' => [
                    ['label' => '启用', 'value' => CmsStatus::Enabled->value, 'color' => 'success'],
                    ['label' => '停用', 'value' => CmsStatus::Disabled->value, 'color' => 'gray'],
                ],
            ]),
        ];
    }
}
