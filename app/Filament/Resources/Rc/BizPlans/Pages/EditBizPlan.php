<?php

namespace App\Filament\Resources\Rc\BizPlans\Pages;

use App\Filament\Resources\Rc\BizPlans\BizPlanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBizPlan extends EditRecord
{
    protected static string $resource = BizPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
