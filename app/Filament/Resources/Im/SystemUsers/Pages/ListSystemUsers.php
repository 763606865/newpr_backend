<?php

namespace App\Filament\Resources\Im\SystemUsers\Pages;

use App\Filament\Resources\Im\SystemUsers\SystemUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSystemUsers extends ListRecords
{
    protected static string $resource = SystemUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
