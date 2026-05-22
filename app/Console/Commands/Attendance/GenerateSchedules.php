<?php

namespace App\Console\Commands\Attendance;

use App\Enums\CompanyStatus;
use App\Enums\EmployeeStatus;
use App\Jobs\GenerateCompanyAttendanceSchedulesJob;
use App\Models\Oa\AttendanceAssignment;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('attendance:generate-schedules
    {date? : 开始日期，格式 Y-m-d}
    {--company_id= : 指定企业ID}
    {--employee_id= : 指定员工ID}
    {--days=3 : 生成未来天数}
    {--force : 强制覆盖未打卡的已生成排班}')]
#[Description('生成未来几天的考勤表')]
class GenerateSchedules extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $startDate = $this->argument('date')
                ? Carbon::parse((string) $this->argument('date'))->startOfDay()
                : Carbon::today();
        } catch (\Throwable) {
            $this->error('date 参数无效，请使用 Y-m-d 格式。');

            return self::FAILURE;
        }

        $companyId = $this->resolveNullableIntOption('company_id');
        $employeeId = $this->resolveNullableIntOption('employee_id');
        $days = max(1, (int) $this->option('days'));
        $force = (bool) $this->option('force');
        $endDate = $startDate->copy()->addDays($days - 1);

        $companyIds = AttendanceAssignment::query()
            ->where('status', 1)
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))
            ->whereDate('effective_start_date', '<=', $endDate->toDateString())
            ->where(function ($query) use ($startDate): void {
                $query->whereNull('effective_end_date')
                    ->orWhereDate('effective_end_date', '>=', $startDate->toDateString());
            })
            ->whereHas('company', fn ($query) => $query->where('status', CompanyStatus::Enabled->value))
            ->whereHas('employee', fn ($query) => $query->where('status', EmployeeStatus::Active->value))
            ->whereHas('attendanceRule', fn ($query) => $query->where('status', 1))
            ->distinct()
            ->orderBy('company_id')
            ->pluck('company_id');

        if ($companyIds->isEmpty()) {
            $this->warn('未找到需要生成考勤表的企业。');

            return self::SUCCESS;
        }

        foreach ($companyIds as $dispatchCompanyId) {
            GenerateCompanyAttendanceSchedulesJob::dispatch(
                companyId: (int) $dispatchCompanyId,
                startDate: $startDate->toDateString(),
                days: $days,
                employeeId: $employeeId,
                force: $force,
            );
        }

        $this->info(sprintf(
            '已派发 %d 个企业排班生成任务，开始日期：%s，结束日期：%s，天数：%d。',
            $companyIds->count(),
            $startDate->toDateString(),
            $endDate->toDateString(),
            $days,
        ));

        return self::SUCCESS;
    }

    private function resolveNullableIntOption(string $name): ?int
    {
        $value = $this->option($name);

        if ($value === null || $value === '') {
            return null;
        }

        return max(1, (int) $value);
    }
}
