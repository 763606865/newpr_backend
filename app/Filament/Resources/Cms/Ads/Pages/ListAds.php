<?php

namespace App\Filament\Resources\Cms\Ads\Pages;

use App\Filament\Resources\Cms\Ads\AdResource;
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
}
