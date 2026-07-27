<?php

namespace App\Filament\Resources\Rc\AssetDefinitions\Pages;

use App\Filament\Resources\Rc\AssetDefinitions\AssetDefinitionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetDefinition extends EditRecord
{
    protected static string $resource = AssetDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
