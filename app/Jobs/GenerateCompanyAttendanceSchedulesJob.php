<?php

namespace App\Jobs;

use App\Services\AttendanceScheduleGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateCompanyAttendanceSchedulesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $companyId,
        public readonly string $startDate,
        public readonly int $days = 3,
        public readonly ?int $employeeId = null,
        public readonly bool $force = false,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AttendanceScheduleGeneratorService $generator): void
    {
        $summary = $generator->generateForCompany(
            companyId: $this->companyId,
            startDate: $this->startDate,
            days: $this->days,
            employeeId: $this->employeeId,
            force: $this->force,
        );

        Log::info('attendance schedules generated', $summary);
    }
}
