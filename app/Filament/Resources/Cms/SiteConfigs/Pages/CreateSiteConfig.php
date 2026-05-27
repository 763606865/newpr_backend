<?php

namespace App\Filament\Resources\Cms\SiteConfigs\Pages;

use App\Filament\Resources\Cms\SiteConfigs\SiteConfigResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSiteConfig extends CreateRecord
{
    protected static string $resource = SiteConfigResource::class;
}
