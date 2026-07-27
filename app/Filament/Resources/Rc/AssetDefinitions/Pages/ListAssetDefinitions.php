<?php

namespace App\Filament\Resources\Rc\AssetDefinitions\Pages;

use App\Filament\Resources\Rc\AssetDefinitions\AssetDefinitionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssetDefinitions extends ListRecords
{
    protected static string $resource = AssetDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
