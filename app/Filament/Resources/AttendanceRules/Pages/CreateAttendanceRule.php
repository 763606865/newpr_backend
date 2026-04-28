<?php

namespace App\Filament\Resources\AttendanceRules\Pages;

use App\Filament\Resources\AttendanceRules\AttendanceRulesResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateAttendanceRule extends CreateRecord
{
    protected static string $resource = AttendanceRulesResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ((! ($data['is_overnight'] ?? 0)) && filled($data['start_time'] ?? null) && filled($data['end_time'] ?? null) && ($data['end_time'] <= $data['start_time'])) {
            throw ValidationException::withMessages([
                'end_time' => '非跨天规则下，下班时间必须大于上班时间。',
            ]);
        }

        return $data;
    }
}
