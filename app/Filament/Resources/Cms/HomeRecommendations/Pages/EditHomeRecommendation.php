<?php

namespace App\Filament\Resources\Cms\HomeRecommendations\Pages;

use App\Filament\Resources\Cms\HomeRecommendations\Concerns\InteractsWithHomeRecommendationForm;
use App\Filament\Resources\Cms\HomeRecommendations\HomeRecommendationResource;
use App\Models\Area;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditHomeRecommendation extends EditRecord
{
    use InteractsWithHomeRecommendationForm;

    protected static string $resource = HomeRecommendationResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $hierarchy = Area::resolveAreaHierarchy($data['city_code'] ?? null);

        $data = array_merge($data, [
            'province_code' => $hierarchy['province_code'],
            'area_city_code' => $hierarchy['city_code'],
        ]);

        return $this->mergeRecommendableIntoFormData($data, $this->getRecord());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->applyRecommendableToFormData($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
