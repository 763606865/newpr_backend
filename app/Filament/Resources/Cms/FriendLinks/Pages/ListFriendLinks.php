<?php

namespace App\Filament\Resources\Cms\FriendLinks\Pages;

use App\Enums\CmsStatus;
use App\Filament\Resources\Cms\FriendLinks\FriendLinkResource;
use App\Filament\Resources\Cms\Widgets\CmsResourceStats;
use App\Models\Cms\FriendLink;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFriendLinks extends ListRecords
{
    protected static string $resource = FriendLinkResource::class;

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
                'modelClass' => FriendLink::class,
                'cityColumn' => 'city_code',
                'statusCards' => [
                    ['label' => '启用', 'value' => CmsStatus::Enabled->value, 'color' => 'success'],
                    ['label' => '停用', 'value' => CmsStatus::Disabled->value, 'color' => 'gray'],
                ],
            ]),
        ];
    }
}
