<?php

namespace App\Filament\Resources\Rc\Reports\Pages;

use App\Enums\RcReportStatus;
use App\Filament\Resources\Rc\Reports\ReportResource;
use App\Filament\Resources\Rc\Widgets\RcResourceStats;
use App\Models\Rc\Report;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListReports extends ListRecords
{
    protected static string $resource = ReportResource::class;

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with(['user', 'creatorIdentity', 'handler', 'reportable']);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RcResourceStats::make([
                'modelClass' => Report::class,
                'todayColumn' => 'created_at',
                'todayLabel' => '今日举报数',
                'statusCards' => [
                    ['label' => '待处理', 'value' => RcReportStatus::Pending->value, 'color' => 'warning'],
                    ['label' => '处理中', 'value' => RcReportStatus::Processing->value, 'color' => 'info'],
                    ['label' => '已处理', 'value' => RcReportStatus::Resolved->value, 'color' => 'success'],
                ],
            ]),
        ];
    }
}
