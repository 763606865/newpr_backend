<?php

namespace App\Filament\Resources\Cms\Menus\Pages;

use App\Filament\Resources\Cms\Menus\Concerns\InteractsWithMenuIdentitiesForm;
use App\Filament\Resources\Cms\Menus\MenuResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditMenu extends EditRecord
{
    use InteractsWithMenuIdentitiesForm;

    protected static string $resource = MenuResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->mergeMenuIdentitiesIntoFormData($data, $this->getRecord());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingIdentityTypes = $this->extractIdentityTypesFromFormData($data);

        return $this->removeIdentityTypesFromFormData($data);
    }

    protected function afterSave(): void
    {
        $this->syncMenuIdentities($this->getRecord());
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
