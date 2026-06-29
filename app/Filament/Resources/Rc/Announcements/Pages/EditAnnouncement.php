<?php

namespace App\Filament\Resources\Rc\Announcements\Pages;

use App\Filament\Resources\Rc\Announcements\AnnouncementResource;
use App\Filament\Resources\Rc\Announcements\Concerns\InteractsWithAnnouncementRelationsForm;
use App\Models\Rc\Announcement;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncement extends EditRecord
{
    use InteractsWithAnnouncementRelationsForm;

    protected static string $resource = AnnouncementResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Announcement $announcement */
        $announcement = $this->getRecord();

        return $this->mergeAnnouncementRelationsIntoFormData($data, $announcement);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingCityCodes = $this->extractCityCodesFromFormData($data);
        $this->pendingMajorCodes = $this->extractMajorCodesFromFormData($data);

        $data = $this->removeRelationFieldsFromFormData($data);

        return $this->normalizeAnnouncementFormData($data);
    }

    protected function afterSave(): void
    {
        $this->syncAnnouncementRelations($this->getRecord());
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
