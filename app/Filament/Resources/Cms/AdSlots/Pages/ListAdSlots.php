<?php

namespace App\Filament\Resources\Cms\AdSlots\Pages;

use App\Enums\CmsStatus;
use App\Filament\Resources\Cms\AdSlots\AdSlotResource;
use App\Filament\Resources\Cms\Widgets\CmsResourceStats;
use App\Models\Cms\AdSlot;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdSlots extends ListRecords
{
    protected static string $resource = AdSlotResource::class;

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
                'modelClass' => AdSlot::class,
                'statusCards' => [
                    ['label' => '启用', 'value' => CmsStatus::Enabled->value, 'color' => 'success'],
                    ['label' => '停用', 'value' => CmsStatus::Disabled->value, 'color' => 'gray'],
                ],
            ]),
        ];
    }
}
