<?php

namespace App\Filament\Resources\Cms\SiteConfigs\Pages;

use App\Filament\Resources\Cms\SiteConfigs\SiteConfigResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditSiteConfig extends EditRecord
{
    protected static string $resource = SiteConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
