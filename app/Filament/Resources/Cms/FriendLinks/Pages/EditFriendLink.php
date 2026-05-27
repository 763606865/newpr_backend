<?php

namespace App\Filament\Resources\Cms\FriendLinks\Pages;

use App\Filament\Resources\Cms\FriendLinks\FriendLinkResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditFriendLink extends EditRecord
{
    protected static string $resource = FriendLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
