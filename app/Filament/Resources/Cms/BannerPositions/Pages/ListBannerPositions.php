<?php

namespace App\Filament\Resources\Cms\BannerPositions\Pages;

use App\Enums\CmsStatus;
use App\Filament\Resources\Cms\BannerPositions\BannerPositionResource;
use App\Filament\Resources\Cms\Widgets\CmsResourceStats;
use App\Models\Cms\BannerPosition;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBannerPositions extends ListRecords
{
    protected static string $resource = BannerPositionResource::class;

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
                'modelClass' => BannerPosition::class,
                'statusCards' => [
                    ['label' => '启用', 'value' => CmsStatus::Enabled->value, 'color' => 'success'],
                    ['label' => '停用', 'value' => CmsStatus::Disabled->value, 'color' => 'gray'],
                ],
            ]),
        ];
    }
}
