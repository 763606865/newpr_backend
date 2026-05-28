<?php

namespace App\Filament\Resources\Rc\UserIdentityBinds\Pages;

use App\Filament\Resources\Rc\UserIdentityBinds\UserIdentityBindResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditUserIdentityBind extends EditRecord
{
    protected static string $resource = UserIdentityBindResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
