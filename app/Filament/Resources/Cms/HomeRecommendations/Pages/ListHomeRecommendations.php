<?php

namespace App\Filament\Resources\Cms\HomeRecommendations\Pages;

use App\Filament\Resources\Cms\HomeRecommendations\HomeRecommendationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHomeRecommendations extends ListRecords
{
    protected static string $resource = HomeRecommendationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
