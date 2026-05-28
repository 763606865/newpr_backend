<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithMonthlyModelData;
use App\Models\Rc\Application;
use App\Models\Rc\Interview;
use App\Models\Rc\Job;
use App\Models\Rc\Offer;
use App\Models\Rc\Resume;
use Filament\Widgets\ChartWidget;

class RcMonthlyChart extends ChartWidget
{
    use InteractsWithMonthlyModelData;

    protected int|string|array $columnSpan = 1;

    protected ?string $heading = 'RC 招聘月度新增';

    protected ?string $description = '职位到 Offer 的招聘链路月新增';

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
                    'label' => '职位',
                    'data' => $this->monthlySeriesForModels([Job::class]),
                    'backgroundColor' => '#3b82f6',
                ],
                [
                    'label' => '简历',
                    'data' => $this->monthlySeriesForModels([Resume::class]),
                    'backgroundColor' => '#22c55e',
                ],
                [
                    'label' => '投递',
                    'data' => $this->monthlySeriesForModels([Application::class]),
                    'backgroundColor' => '#f59e0b',
                ],
                [
                    'label' => '面试',
                    'data' => $this->monthlySeriesForModels([Interview::class]),
                    'backgroundColor' => '#8b5cf6',
                ],
                [
                    'label' => 'Offer',
                    'data' => $this->monthlySeriesForModels([Offer::class]),
                    'backgroundColor' => '#14b8a6',
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
