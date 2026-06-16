<?php

namespace App\Filament\Resources\Cms\Banners\Pages;

use App\Filament\Resources\Cms\Banners\BannerResource;
use App\Models\Area;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditBanner extends EditRecord
{
    protected static string $resource = BannerResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $hierarchy = Area::resolveAreaHierarchy($data['city_code'] ?? null);

        return array_merge($data, [
            'province_code' => $hierarchy['province_code'],
            'area_city_code' => $hierarchy['city_code'],
        ]);
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
