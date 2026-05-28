<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithMonthlyModelData;
use App\Models\AdminUser;
use App\Models\Cms\Ad;
use App\Models\Cms\Announcement;
use App\Models\Cms\Article;
use App\Models\Cms\Banner;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Oa\AttendanceSchedule;
use App\Models\Rc\Application;
use App\Models\Rc\Interview;
use App\Models\Rc\Job;
use App\Models\Rc\Offer;
use App\Models\Rc\Resume;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class DomainKpiStatsWidget extends StatsOverviewWidget
{
    use InteractsWithMonthlyModelData;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            $this->buildDomainStat('首页', [AdminUser::class, User::class, Company::class], 'gray'),
            $this->buildDomainStat('OA', [Department::class, Employee::class, AttendanceSchedule::class], 'warning'),
            $this->buildDomainStat('CMS', [Article::class, Announcement::class, Banner::class, Ad::class], 'info'),
            $this->buildDomainStat('RC招聘', [Job::class, Resume::class, Application::class, Interview::class, Offer::class], 'success'),
        ];
    }

    /**
     * @param  array<int, class-string<Model>>  $modelClasses
     */
    private function buildDomainStat(string $label, array $modelClasses, string $color): Stat
    {
        $currentMonthTotal = $this->countForCurrentMonth($modelClasses);
        $lastMonthTotal = $this->countForPreviousMonth($modelClasses);
        $difference = $currentMonthTotal - $lastMonthTotal;

        $description = $difference >= 0
            ? sprintf('较上月 +%d', $difference)
            : sprintf('较上月 %d', $difference);

        return Stat::make($label, (string) $currentMonthTotal)
            ->description($description)
            ->descriptionIcon($difference >= 0 ? Heroicon::OutlinedArrowTrendingUp : Heroicon::OutlinedArrowTrendingDown)
            ->color($color);
    }
}
