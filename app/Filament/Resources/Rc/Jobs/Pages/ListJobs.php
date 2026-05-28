<?php

namespace App\Filament\Resources\Rc\Jobs\Pages;

use App\Enums\RcJobStatus;
use App\Filament\Resources\Rc\Jobs\JobResource;
use App\Filament\Resources\Rc\Widgets\RcResourceStats;
use App\Models\Rc\Job;
use Filament\Resources\Pages\ListRecords;

class ListJobs extends ListRecords
{
    protected static string $resource = JobResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RcResourceStats::make([
                'modelClass' => Job::class,
                'todayColumn' => 'published_at',
                'todayLabel' => '今日发布职位',
                'statusCards' => [
                    ['label' => '已发布', 'value' => RcJobStatus::Published->value, 'color' => 'success'],
                    ['label' => '暂停', 'value' => RcJobStatus::Paused->value, 'color' => 'warning'],
                    ['label' => '关闭', 'value' => RcJobStatus::Closed->value, 'color' => 'gray'],
                ],
            ]),
        ];
    }
}
