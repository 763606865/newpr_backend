<?php

namespace App\Filament\Resources\Rc\Applications\Pages;

use App\Enums\RcApplicationStatus;
use App\Filament\Resources\Rc\Applications\ApplicationResource;
use App\Filament\Resources\Rc\Widgets\RcResourceStats;
use App\Models\Rc\Application;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with(['company', 'candidateUser', 'job']);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RcResourceStats::make([
                'modelClass' => Application::class,
                'todayColumn' => 'applied_at',
                'todayLabel' => '今日投递数',
                'statusCards' => [
                    ['label' => '筛选中', 'value' => RcApplicationStatus::Screening->value, 'color' => 'info'],
                    ['label' => '面试中', 'value' => RcApplicationStatus::Interviewing->value, 'color' => 'warning'],
                    ['label' => '录用', 'value' => RcApplicationStatus::Hired->value, 'color' => 'success'],
                ],
            ]),
        ];
    }
}
