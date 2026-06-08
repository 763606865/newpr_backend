<?php

namespace App\Jobs;

use App\Models\Biz\Plan;
use App\Models\Company;
use App\Services\SysPlanService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class BatchRebindCompanyPlansJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public readonly int $planId) {}

    public function handle(SysPlanService $sysPlanService): void
    {
        $plan = Plan::query()->findOrFail($this->planId);
        $companyIds = $sysPlanService->companyIdsWithCurrentPlan($this->planId);
        $remark = '批量重绑：'.now()->toDateTimeString();
        $successCount = 0;
        $failedCount = 0;

        foreach ($companyIds as $companyId) {
            $company = Company::query()->find($companyId);

            if (! $company instanceof Company) {
                $failedCount++;

                continue;
            }

            try {
                $sysPlanService->refreshCurrentPlan($company, $remark);
                $successCount++;
            } catch (Throwable $exception) {
                $failedCount++;

                Log::error('batch rebind company plan failed', [
                    'plan_id' => $this->planId,
                    'company_id' => $companyId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        Log::info('batch rebind company plans finished', [
            'plan_id' => $this->planId,
            'plan_code' => $plan->plan_code,
            'total' => $companyIds->count(),
            'success' => $successCount,
            'failed' => $failedCount,
        ]);
    }
}
