<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithMonthlyModelData;
use App\Models\AdminUser;
use App\Models\Company;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Spatie\Permission\Models\Role;

class HomeMonthlyChart extends ChartWidget
{
    use InteractsWithMonthlyModelData;

    protected int|string|array $columnSpan = 1;

    protected ?string $heading = '首页模块月度新增';

    protected ?string $description = '管理员、角色、用户与企业月新增';

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
                    'label' => '管理员',
                    'data' => $this->monthlySeriesForModels([AdminUser::class]),
                    'backgroundColor' => '#f59e0b',
                ],
                [
                    'label' => '角色',
                    'data' => $this->monthlySeriesForModels([Role::class]),
                    'backgroundColor' => '#94a3b8',
                ],
                [
                    'label' => '用户',
                    'data' => $this->monthlySeriesForModels([User::class]),
                    'backgroundColor' => '#22c55e',
                ],
                [
                    'label' => '企业',
                    'data' => $this->monthlySeriesForModels([Company::class]),
                    'backgroundColor' => '#3b82f6',
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
