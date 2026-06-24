<?php

namespace App\Filament\Resources\Cms\HomeRecommendations\Pages;

use App\Filament\Resources\Cms\HomeRecommendations\Concerns\InteractsWithHomeRecommendationForm;
use App\Filament\Resources\Cms\HomeRecommendations\HomeRecommendationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHomeRecommendation extends CreateRecord
{
    use InteractsWithHomeRecommendationForm;

    protected static string $resource = HomeRecommendationResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->applyRecommendableToFormData($data);
    }
}
