<?php

namespace App\Filament\Resources\Cms\BannerPositions\Pages;

use App\Filament\Resources\Cms\BannerPositions\BannerPositionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditBannerPosition extends EditRecord
{
    protected static string $resource = BannerPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
