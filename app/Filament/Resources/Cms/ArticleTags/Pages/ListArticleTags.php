<?php

namespace App\Filament\Resources\Cms\ArticleTags\Pages;

use App\Enums\CmsStatus;
use App\Filament\Resources\Cms\ArticleTags\ArticleTagResource;
use App\Filament\Resources\Cms\Widgets\CmsResourceStats;
use App\Models\Cms\ArticleTag;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListArticleTags extends ListRecords
{
    protected static string $resource = ArticleTagResource::class;

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
                'modelClass' => ArticleTag::class,
                'statusCards' => [
                    ['label' => '启用', 'value' => CmsStatus::Enabled->value, 'color' => 'success'],
                    ['label' => '停用', 'value' => CmsStatus::Disabled->value, 'color' => 'gray'],
                ],
            ]),
        ];
    }
}
