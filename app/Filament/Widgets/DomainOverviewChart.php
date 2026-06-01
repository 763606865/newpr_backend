<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithMonthlyModelData;
use App\Models\AdminUser;
use App\Models\Biz\Plan;
use App\Models\Client\Feature;
use App\Models\Client\Menu as ClientMenu;
use App\Models\Cms\Ad;
use App\Models\Cms\Announcement;
use App\Models\Cms\Article;
use App\Models\Cms\ArticleCategory;
use App\Models\Cms\ArticleTag;
use App\Models\Cms\Banner;
use App\Models\Cms\BannerPosition;
use App\Models\Cms\FriendLink;
use App\Models\Cms\Menu;
use App\Models\Cms\SiteConfig;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Oa\AttendanceRule;
use App\Models\Oa\AttendanceSchedule;
use App\Models\Oa\LeaveType;
use App\Models\Rc\Application;
use App\Models\Rc\Interview;
use App\Models\Rc\Job;
use App\Models\Rc\Offer;
use App\Models\Rc\Resume;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DomainOverviewChart extends ChartWidget
{
    use InteractsWithMonthlyModelData;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = '四大板块近12个月趋势';

    protected ?string $description = '按月统计各板块新增记录量';

    protected ?string $maxHeight = '360px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $homeModels = [
            AdminUser::class,
            Role::class,
            Permission::class,
            User::class,
            Company::class,
            Plan::class,
            Feature::class,
            ClientMenu::class,
        ];
        $oaModels = [
            Department::class,
            Employee::class,
            AttendanceRule::class,
            AttendanceSchedule::class,
            LeaveType::class,
        ];
        $cmsModels = [
            Article::class,
            Banner::class,
            Ad::class,
            Announcement::class,
            FriendLink::class,
            Menu::class,
            SiteConfig::class,
            BannerPosition::class,
            ArticleCategory::class,
            ArticleTag::class,
        ];
        $rcModels = [
            Job::class,
            Resume::class,
            Application::class,
            Interview::class,
            Offer::class,
            UserIdentity::class,
        ];

        return [
            'labels' => $this->monthLabels(),
            'datasets' => [
                [
                    'label' => '首页',
                    'data' => $this->monthlySeriesForModels($homeModels),
                    'borderColor' => '#64748b',
                    'backgroundColor' => 'rgba(100, 116, 139, 0.12)',
                    'tension' => 0.35,
                    'fill' => true,
                    'pointRadius' => 2,
                    'pointHoverRadius' => 5,
                ],
                [
                    'label' => 'OA',
                    'data' => $this->monthlySeriesForModels($oaModels),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.12)',
                    'tension' => 0.35,
                    'fill' => true,
                    'pointRadius' => 2,
                    'pointHoverRadius' => 5,
                ],
                [
                    'label' => 'CMS',
                    'data' => $this->monthlySeriesForModels($cmsModels),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.12)',
                    'tension' => 0.35,
                    'fill' => true,
                    'pointRadius' => 2,
                    'pointHoverRadius' => 5,
                ],
                [
                    'label' => 'RC招聘',
                    'data' => $this->monthlySeriesForModels($rcModels),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.12)',
                    'tension' => 0.35,
                    'fill' => true,
                    'pointRadius' => 2,
                    'pointHoverRadius' => 5,
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
                        'pointStyle' => 'circle',
                        'padding' => 18,
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
