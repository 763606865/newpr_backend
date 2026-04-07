<?php

namespace App\Filament\Resources\Staffs\Pages;

use App\Filament\Resources\Staffs\StaffsResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditStaffs extends EditRecord
{
    protected static string $resource = StaffsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
