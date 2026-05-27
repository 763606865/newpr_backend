<?php

namespace App\Filament\Resources\Cms\BannerPositions\Pages;

use App\Filament\Resources\Cms\BannerPositions\BannerPositionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBannerPositions extends ListRecords
{
    protected static string $resource = BannerPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
