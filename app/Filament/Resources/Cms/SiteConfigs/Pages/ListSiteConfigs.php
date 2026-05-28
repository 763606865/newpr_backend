<?php

namespace App\Filament\Resources\Cms\SiteConfigs\Pages;

use App\Enums\CmsStatus;
use App\Filament\Resources\Cms\SiteConfigs\SiteConfigResource;
use App\Filament\Resources\Cms\Widgets\CmsResourceStats;
use App\Models\Cms\SiteConfig;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSiteConfigs extends ListRecords
{
    protected static string $resource = SiteConfigResource::class;

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
                'modelClass' => SiteConfig::class,
                'cityColumn' => 'city_code',
                'statusCards' => [
                    ['label' => '启用', 'value' => CmsStatus::Enabled->value, 'color' => 'success'],
                    ['label' => '停用', 'value' => CmsStatus::Disabled->value, 'color' => 'gray'],
                ],
            ]),
        ];
    }
}
