<?php

namespace App\Filament\Resources\Cms\FriendLinks\Pages;

use App\Filament\Resources\Cms\FriendLinks\FriendLinkResource;
use App\Models\Area;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditFriendLink extends EditRecord
{
    protected static string $resource = FriendLinkResource::class;

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
