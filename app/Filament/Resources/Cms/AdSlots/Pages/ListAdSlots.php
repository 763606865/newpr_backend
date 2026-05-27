<?php

namespace App\Filament\Resources\Cms\AdSlots\Pages;

use App\Filament\Resources\Cms\AdSlots\AdSlotResource;
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
}
