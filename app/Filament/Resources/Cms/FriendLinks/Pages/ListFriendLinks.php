<?php

namespace App\Filament\Resources\Cms\FriendLinks\Pages;

use App\Filament\Resources\Cms\FriendLinks\FriendLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFriendLinks extends ListRecords
{
    protected static string $resource = FriendLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
