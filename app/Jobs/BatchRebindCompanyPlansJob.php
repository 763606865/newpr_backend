<?php

namespace App\Jobs;

use App\Models\AdminUser;
use App\Models\Company;
use App\Models\Oa\Biz\Plan;
use App\Services\CompanyOperationLogService;
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

    public function __construct(
        public readonly int $planId,
        public readonly ?int $operatorId = null,
    ) {}

    public function handle(
        SysPlanService $sysPlanService,
        CompanyOperationLogService $logService,
    ): void {
        $plan = Plan::query()->findOrFail($this->planId);
        $companyIds = $sysPlanService->companyIdsWithCurrentPlan($this->planId);
        $remark = '批量重绑：'.now()->toDateTimeString();
        $successCount = 0;
        $failedCount = 0;
        $operator = $this->operatorId
            ? AdminUser::query()->find($this->operatorId)
            : null;

        foreach ($companyIds as $companyId) {
            $company = Company::query()->find($companyId);

            if (! $company instanceof Company) {
                $failedCount++;

                continue;
            }

            try {
                $beforePlan = $logService->snapshotCurrentPlan($company);
                $sysPlanService->refreshCurrentPlan($company, $remark);
                $afterPlan = $logService->snapshotCurrentPlan($company->fresh());

                if (is_array($afterPlan)) {
                    $logService->recordPlanBatchRebound(
                        $company->fresh(),
                        $beforePlan,
                        $afterPlan,
                        $this->planId,
                        $operator,
                    );
                }

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
