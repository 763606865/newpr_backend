<?php

namespace App\Filament\Resources\Cms\Ads\Pages;

use App\Enums\CmsStatus;
use App\Filament\Resources\Cms\Ads\AdResource;
use App\Filament\Resources\Cms\Widgets\CmsResourceStats;
use App\Models\Cms\Ad;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAds extends ListRecords
{
    protected static string $resource = AdResource::class;

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
                'modelClass' => Ad::class,
                'cityColumn' => 'city_code',
                'statusCards' => [
                    ['label' => '启用', 'value' => CmsStatus::Enabled->value, 'color' => 'success'],
                    ['label' => '停用', 'value' => CmsStatus::Disabled->value, 'color' => 'gray'],
                ],
            ]),
        ];
    }
}
