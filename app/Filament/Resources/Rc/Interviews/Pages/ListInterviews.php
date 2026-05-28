<?php

namespace App\Filament\Resources\Rc\Interviews\Pages;

use App\Enums\RcInterviewStatus;
use App\Filament\Resources\Rc\Interviews\InterviewResource;
use App\Filament\Resources\Rc\Widgets\RcResourceStats;
use App\Models\Rc\Interview;
use Filament\Resources\Pages\ListRecords;

class ListInterviews extends ListRecords
{
    protected static string $resource = InterviewResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RcResourceStats::make([
                'modelClass' => Interview::class,
                'todayColumn' => 'interview_at',
                'todayLabel' => '今日面试',
                'statusCards' => [
                    ['label' => '已安排', 'value' => RcInterviewStatus::Scheduled->value, 'color' => 'warning'],
                    ['label' => '已完成', 'value' => RcInterviewStatus::Finished->value, 'color' => 'success'],
                    ['label' => '已取消', 'value' => RcInterviewStatus::Cancelled->value, 'color' => 'danger'],
                ],
            ]),
        ];
    }
}
