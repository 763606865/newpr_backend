<?php

namespace App\Filament\Resources\Cms\Menus\Pages;

use App\Enums\CmsStatus;
use App\Filament\Resources\Cms\Menus\MenuResource;
use App\Filament\Resources\Cms\Widgets\CmsResourceStats;
use App\Models\Cms\Menu;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMenus extends ListRecords
{
    protected static string $resource = MenuResource::class;

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
                'modelClass' => Menu::class,
                'statusCards' => [
                    ['label' => '启用', 'value' => CmsStatus::Enabled->value, 'color' => 'success'],
                    ['label' => '停用', 'value' => CmsStatus::Disabled->value, 'color' => 'gray'],
                ],
            ]),
        ];
    }
}
