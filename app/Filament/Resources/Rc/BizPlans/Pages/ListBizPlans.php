<?php

namespace App\Filament\Resources\Rc\BizPlans\Pages;

use App\Filament\Resources\Rc\BizPlans\BizPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBizPlans extends ListRecords
{
    protected static string $resource = BizPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
