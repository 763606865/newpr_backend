<?php

namespace App\Filament\Resources\Cms\Banners\Pages;

use App\Enums\CmsStatus;
use App\Filament\Resources\Cms\Banners\BannerResource;
use App\Filament\Resources\Cms\Widgets\CmsResourceStats;
use App\Models\Cms\Banner;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBanners extends ListRecords
{
    protected static string $resource = BannerResource::class;

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
                'modelClass' => Banner::class,
                'cityColumn' => 'city_code',
                'statusCards' => [
                    ['label' => '启用', 'value' => CmsStatus::Enabled->value, 'color' => 'success'],
                    ['label' => '停用', 'value' => CmsStatus::Disabled->value, 'color' => 'gray'],
                ],
            ]),
        ];
    }
}
