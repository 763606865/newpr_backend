<?php

namespace App\Filament\Resources\AttendanceSchedules\Pages;

use App\Filament\Resources\AttendanceSchedules\AttendanceSchedulesResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditAttendanceSchedule extends EditRecord
{
    protected static string $resource = AttendanceSchedulesResource::class;

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
        if ((! ($data['is_overnight'] ?? 0)) && filled($data['std_start_time'] ?? null) && filled($data['std_end_time'] ?? null) && ($data['std_end_time'] <= $data['std_start_time'])) {
            throw ValidationException::withMessages([
                'std_end_time' => '非跨天班次下，标准下班时间必须大于标准上班时间。',
            ]);
        }

        return $data;
    }
}
