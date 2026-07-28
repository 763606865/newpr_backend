<?php

namespace Tests\Feature\Cms;

use App\Enums\RcBizPlanBillingCycle;
use App\Enums\RcBizPlanProductType;
use App\Enums\RcBizPlanStatus;
use App\Enums\RcBizPlanTargetSide;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Models\Rc\BizPlan;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RcBizPlanControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        $this->createPlan('求职者会员', 'seeker_vip', RcBizPlanTargetSide::JobSeeker, sort: 2);
        $this->createPlan('简历优化', 'resume_optimization', RcBizPlanTargetSide::JobSeeker, sort: 1);
        $this->createPlan('招聘会员', 'recruiter_vip', RcBizPlanTargetSide::Recruiter);
        $this->createPlan(
            '已下架求职套餐',
            'disabled_seeker',
            RcBizPlanTargetSide::JobSeeker,
            RcBizPlanStatus::Disabled,
        );
    }

    public function test_guest_receives_job_seeker_plans(): void
    {
        $this->getJson('/cms/rc/biz-plans')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.plan_code', 'resume_optimization')
            ->assertJsonPath('data.1.plan_code', 'seeker_vip')
            ->assertJsonMissing(['plan_code' => 'recruiter_vip'])
            ->assertJsonMissing(['plan_code' => 'disabled_seeker']);
    }

    public function test_job_seeker_receives_job_seeker_plans(): void
    {
        $user = $this->createUserWithIdentity(RcIdentityType::JobSeeker);

        $this->actingAs($user, 'rc')
            ->getJson('/cms/rc/biz-plans')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.target_side', RcBizPlanTargetSide::JobSeeker->value);
    }

    public function test_recruiter_receives_recruiter_plans(): void
    {
        $user = $this->createUserWithIdentity(RcIdentityType::Recruiter);

        $this->actingAs($user, 'rc')
            ->getJson('/cms/rc/biz-plans')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.plan_code', 'recruiter_vip')
            ->assertJsonPath('data.0.target_side', RcBizPlanTargetSide::Recruiter->value)
            ->assertJsonMissing(['plan_code' => 'seeker_vip']);
    }

    private function createPlan(
        string $name,
        string $code,
        RcBizPlanTargetSide $targetSide,
        RcBizPlanStatus $status = RcBizPlanStatus::Enabled,
        int $sort = 0,
    ): BizPlan {
        return BizPlan::query()->create([
            'plan_name' => $name,
            'plan_code' => $code,
            'price' => 99,
            'duration' => 30,
            'target_side' => $targetSide,
            'product_type' => RcBizPlanProductType::Membership,
            'billing_cycle' => RcBizPlanBillingCycle::Monthly,
            'sort' => $sort,
            'status' => $status,
        ]);
    }

    private function createUserWithIdentity(RcIdentityType $identityType): User
    {
        $user = User::factory()->create();

        UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => $identityType,
            'identity_name' => $identityType->getLabel(),
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        return $user;
    }
}
