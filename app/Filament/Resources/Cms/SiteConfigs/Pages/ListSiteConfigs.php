<?php

namespace App\Filament\Resources\Cms\SiteConfigs\Pages;

use App\Filament\Resources\Cms\SiteConfigs\SiteConfigResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSiteConfigs extends ListRecords
{
    protected static string $resource = SiteConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
