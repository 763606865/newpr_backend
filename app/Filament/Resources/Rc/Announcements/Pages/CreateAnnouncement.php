<?php

namespace App\Filament\Resources\Rc\Announcements\Pages;

use App\Filament\Resources\Rc\Announcements\AnnouncementResource;
use App\Filament\Resources\Rc\Announcements\Concerns\InteractsWithAnnouncementRelationsForm;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncement extends CreateRecord
{
    use InteractsWithAnnouncementRelationsForm;

    protected static string $resource = AnnouncementResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingCityCodes = $this->extractCityCodesFromFormData($data);
        $this->pendingMajorCodes = $this->extractMajorCodesFromFormData($data);

        $data = $this->removeRelationFieldsFromFormData($data);

        return $this->normalizeAnnouncementFormData($data);
    }

    protected function afterCreate(): void
    {
        $this->syncAnnouncementRelations($this->getRecord());
    }
}
