<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithMonthlyModelData;
use App\Models\Cms\Ad;
use App\Models\Cms\Announcement;
use App\Models\Cms\Article;
use App\Models\Cms\Banner;
use Filament\Widgets\ChartWidget;

class CmsMonthlyChart extends ChartWidget
{
    use InteractsWithMonthlyModelData;

    protected int|string|array $columnSpan = 1;

    protected ?string $heading = 'CMS 模块月度新增';

    protected ?string $description = '文章、公告、Banner 与广告按月新增';

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
                    'label' => '文章',
                    'data' => $this->monthlySeriesForModels([Article::class]),
                    'backgroundColor' => '#3b82f6',
                ],
                [
                    'label' => '公告',
                    'data' => $this->monthlySeriesForModels([Announcement::class]),
                    'backgroundColor' => '#f59e0b',
                ],
                [
                    'label' => 'Banner',
                    'data' => $this->monthlySeriesForModels([Banner::class]),
                    'backgroundColor' => '#8b5cf6',
                ],
                [
                    'label' => '广告',
                    'data' => $this->monthlySeriesForModels([Ad::class]),
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
