<?php

namespace Tests\Feature\Filament;

use App\Enums\SystemPlanStatus;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Jobs\BatchRebindCompanyPlansJob;
use App\Models\Oa\Biz\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class CompaniesTableBatchRebindActionTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    public function test_batch_rebind_header_action_dispatches_job(): void
    {
        Bus::fake();
        $this->actingAsFilamentAdmin();

        $plan = Plan::query()->create([
            'plan_name' => '标准套餐',
            'plan_code' => 'standard_plan',
            'price' => 999.00,
            'duration' => 365,
            'sort' => 1,
            'status' => SystemPlanStatus::Enabled,
        ]);

        Livewire::test(ListCompanies::class)
            ->callAction('batchRebindPlans', data: [
                'plan_id' => $plan->id,
            ])
            ->assertNotified();

        Bus::assertDispatched(BatchRebindCompanyPlansJob::class, function (BatchRebindCompanyPlansJob $job) use ($plan): bool {
            return $job->planId === $plan->id;
        });
    }
}
