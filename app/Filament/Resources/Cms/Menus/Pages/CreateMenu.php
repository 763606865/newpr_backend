<?php

namespace App\Filament\Resources\Cms\Menus\Pages;

use App\Filament\Resources\Cms\Menus\Concerns\InteractsWithMenuIdentitiesForm;
use App\Filament\Resources\Cms\Menus\MenuResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMenu extends CreateRecord
{
    use InteractsWithMenuIdentitiesForm;

    protected static string $resource = MenuResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingIdentityTypes = $this->extractIdentityTypesFromFormData($data);

        return $this->removeIdentityTypesFromFormData($data);
    }

    protected function afterCreate(): void
    {
        $this->syncMenuIdentities($this->getRecord());
    }
}
