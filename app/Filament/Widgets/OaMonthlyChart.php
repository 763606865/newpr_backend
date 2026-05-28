<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithMonthlyModelData;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Oa\AttendanceSchedule;
use Filament\Widgets\ChartWidget;

class OaMonthlyChart extends ChartWidget
{
    use InteractsWithMonthlyModelData;

    protected int|string|array $columnSpan = 1;

    protected ?string $heading = 'OA 模块月度新增';

    protected ?string $description = '部门、职工和排班按月新增';

    protected ?string $maxHeight = '320px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        return [
            'labels' => $this->monthLabels(),
            'datasets' => [
                [
                    'label' => '部门',
                    'data' => $this->monthlySeriesForModels([Department::class]),
                    'backgroundColor' => '#f59e0b',
                ],
                [
                    'label' => '职工',
                    'data' => $this->monthlySeriesForModels([Employee::class]),
                    'backgroundColor' => '#10b981',
                ],
                [
                    'label' => '考勤排班',
                    'data' => $this->monthlySeriesForModels([AttendanceSchedule::class]),
                    'backgroundColor' => '#6366f1',
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'rectRounded',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'stacked' => true,
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'stacked' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
