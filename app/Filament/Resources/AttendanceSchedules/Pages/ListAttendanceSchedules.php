<?php

namespace App\Filament\Resources\AttendanceSchedules\Pages;

use App\Filament\Resources\AttendanceSchedules\AttendanceSchedulesResource;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceSchedules extends ListRecords
{
    protected static string $resource = AttendanceSchedulesResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
