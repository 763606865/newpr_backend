<?php

namespace App\Filament\Resources\AttendanceRules\Pages;

use App\Filament\Resources\AttendanceRules\AttendanceRulesResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditAttendanceRule extends EditRecord
{
    protected static string $resource = AttendanceRulesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ((! ($data['is_overnight'] ?? 0)) && filled($data['start_time'] ?? null) && filled($data['end_time'] ?? null) && ($data['end_time'] <= $data['start_time'])) {
            throw ValidationException::withMessages([
                'end_time' => '非跨天规则下，下班时间必须大于上班时间。',
            ]);
        }

        return $data;
    }
}
