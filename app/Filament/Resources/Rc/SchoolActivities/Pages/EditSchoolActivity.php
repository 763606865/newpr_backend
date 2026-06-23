<?php

namespace App\Filament\Resources\Rc\SchoolActivities\Pages;

use App\Filament\Resources\Rc\SchoolActivities\SchoolActivityResource;
use App\Models\Area;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditSchoolActivity extends EditRecord
{
    protected static string $resource = SchoolActivityResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $areaCodes = Area::resolveFormAreaCodes(
            $data['province_code'] ?? null,
            $data['city_code'] ?? null,
            $data['district_code'] ?? null,
        );

        return array_merge($data, $areaCodes);
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
