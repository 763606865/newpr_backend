<?php

namespace App\Filament\Resources\System\Plans\Pages;

use App\Filament\Resources\System\Plans\PlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->plan_code !== 'trial_plan'),
        ];
    }
}
