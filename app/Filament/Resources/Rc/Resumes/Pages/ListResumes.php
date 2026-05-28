<?php

namespace App\Filament\Resources\Rc\Resumes\Pages;

use App\Enums\RcResumeStatus;
use App\Filament\Resources\Rc\Resumes\ResumeResource;
use App\Filament\Resources\Rc\Widgets\RcResourceStats;
use App\Models\Rc\Resume;
use Filament\Resources\Pages\ListRecords;

class ListResumes extends ListRecords
{
    protected static string $resource = ResumeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RcResourceStats::make([
                'modelClass' => Resume::class,
                'todayColumn' => 'created_at',
                'todayLabel' => '今日新增简历',
                'statusCards' => [
                    ['label' => '在线简历', 'value' => RcResumeStatus::Normal->value, 'color' => 'success'],
                    ['label' => '停用简历', 'value' => RcResumeStatus::Disabled->value, 'color' => 'gray'],
                ],
            ]),
        ];
    }
}
