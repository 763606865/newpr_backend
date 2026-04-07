<?php

namespace App\Filament\Resources\Staffs\Pages;

use App\Filament\Resources\Staffs\StaffsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStaffs extends ListRecords
{
    protected static string $resource = StaffsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
