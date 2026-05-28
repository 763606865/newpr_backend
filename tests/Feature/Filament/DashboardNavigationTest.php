<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\System\Features\FeatureResource;
use App\Filament\Resources\System\Menus\MenuResource;
use App\Filament\Resources\System\Plans\PlanResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\CmsMonthlyChart;
use App\Filament\Widgets\DomainKpiStatsWidget;
use App\Filament\Widgets\DomainOverviewChart;
use App\Filament\Widgets\HomeMonthlyChart;
use App\Filament\Widgets\OaMonthlyChart;
use App\Filament\Widgets\RcMonthlyChart;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Tests\TestCase;

class DashboardNavigationTest extends TestCase
{
    public function test_admin_panel_uses_sidebar_navigation_with_four_primary_groups(): void
    {
        $provider = new AdminPanelProvider(app());
        $panel = $provider->panel(Panel::make());

        $this->assertFalse($panel->hasTopNavigation());

        $groupLabels = array_map(
            fn (NavigationGroup|string $group): string => $group instanceof NavigationGroup ? $group->getLabel() : (string) $group,
            $panel->getNavigationGroups(),
        );

        $this->assertSame(['后台管理', 'OA', 'CMS', 'RC招聘'], array_slice($groupLabels, 0, 4));
    }

    public function test_dashboard_registers_the_expected_big_screen_widgets(): void
    {
        $dashboard = app(Dashboard::class);

        $widgets = $dashboard->getWidgets();

        $this->assertCount(6, $widgets);
        $this->assertSame(DomainKpiStatsWidget::class, $widgets[0]);
        $this->assertSame(DomainOverviewChart::class, $widgets[1]);
        $this->assertSame(HomeMonthlyChart::class, $widgets[2]);
        $this->assertSame(OaMonthlyChart::class, $widgets[3]);
        $this->assertSame(CmsMonthlyChart::class, $widgets[4]);
        $this->assertSame(RcMonthlyChart::class, $widgets[5]);
        $this->assertNull(Dashboard::getNavigationGroup());
    }

    public function test_common_backend_resources_are_grouped_under_backend_management(): void
    {
        $this->assertSame('后台管理', UserResource::getNavigationGroup());
        $this->assertSame('后台管理', CompanyResource::getNavigationGroup());
        $this->assertSame('后台管理', FeatureResource::getNavigationGroup());
        $this->assertSame('后台管理', MenuResource::getNavigationGroup());
        $this->assertSame('后台管理', PlanResource::getNavigationGroup());
    }
}
