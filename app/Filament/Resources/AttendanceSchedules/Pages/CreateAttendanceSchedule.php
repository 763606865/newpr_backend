<?php

namespace App\Filament\Resources\AttendanceSchedules\Pages;

use App\Filament\Resources\AttendanceSchedules\AttendanceSchedulesResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateAttendanceSchedule extends CreateRecord
{
    protected static string $resource = AttendanceSchedulesResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ((! ($data['is_overnight'] ?? 0)) && filled($data['std_start_time'] ?? null) && filled($data['std_end_time'] ?? null) && ($data['std_end_time'] <= $data['std_start_time'])) {
            throw ValidationException::withMessages([
                'std_end_time' => '非跨天班次下，标准下班时间必须大于标准上班时间。',
            ]);
        }

        return $data;
    }
}
