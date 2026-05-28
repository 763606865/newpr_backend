<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CmsMonthlyChart;
use App\Filament\Widgets\DomainKpiStatsWidget;
use App\Filament\Widgets\DomainOverviewChart;
use App\Filament\Widgets\HomeMonthlyChart;
use App\Filament\Widgets\OaMonthlyChart;
use App\Filament\Widgets\RcMonthlyChart;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = '运营数据大屏';

    protected static ?string $navigationLabel = '仪表盘';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    /** @return array<class-string<Widget>> */
    public function getWidgets(): array
    {
        return [
            DomainKpiStatsWidget::class,
            DomainOverviewChart::class,
            HomeMonthlyChart::class,
            OaMonthlyChart::class,
            CmsMonthlyChart::class,
            RcMonthlyChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
