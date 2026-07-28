<?php

namespace Tests\Feature\Rc;

use App\Enums\RcAssetCode;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcResumeRefreshQuotaType;
use App\Models\Rc\AssetAccount;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeRefreshLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class ResumeRefreshControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('get')->zeroOrMoreTimes()->andReturn(null);
        $connection->shouldReceive('setex')->zeroOrMoreTimes()->andReturn(true);
        Redis::shouldReceive('connection')->zeroOrMoreTimes()->andReturn($connection);
    }

    public function test_first_refresh_of_day_uses_free_quota(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $this->actingAs($user, 'rc')
            ->postJson('/rc/resumes/'.$resume->id.'/refresh')
            ->assertOk()
            ->assertJsonPath('data.refreshed_at', fn (mixed $value): bool => filled($value));

        $this->assertDatabaseHas('rc_resume_refresh_logs', [
            'user_id' => $user->id,
            'resume_id' => $resume->id,
            'refresh_date' => now()->toDateString(),
            'quota_type' => RcResumeRefreshQuotaType::FreeDaily->value,
            'quota_key' => 'free_daily',
            'asset_ledger_id' => null,
        ]);
    }

    public function test_paid_refresh_can_be_used_multiple_times_in_one_day(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);
        AssetAccount::query()->create([
            'owner_type' => RcAssetOwnerType::User,
            'owner_id' => $user->id,
            'asset_code' => RcAssetCode::ResumeRefresh->value,
            'asset_name' => RcAssetCode::ResumeRefresh->getLabel(),
            'balance' => 2,
            'frozen_balance' => 0,
        ]);

        $this->actingAs($user, 'rc')->postJson('/rc/resumes/'.$resume->id.'/refresh')->assertOk();
        $this->travel(1)->minute();
        $this->actingAs($user, 'rc')->postJson('/rc/resumes/'.$resume->id.'/refresh')->assertOk();
        $this->travel(1)->minute();
        $this->actingAs($user, 'rc')->postJson('/rc/resumes/'.$resume->id.'/refresh')->assertOk();

        $this->assertDatabaseCount('rc_resume_refresh_logs', 3);
        $this->assertDatabaseHas('rc_asset_accounts', [
            'owner_type' => RcAssetOwnerType::User->value,
            'owner_id' => $user->id,
            'asset_code' => RcAssetCode::ResumeRefresh->value,
            'balance' => 0,
        ]);
        $this->assertDatabaseCount('rc_asset_ledgers', 2);
        $this->assertSame(
            2,
            ResumeRefreshLog::query()
                ->where('quota_type', RcResumeRefreshQuotaType::Asset->value)
                ->count(),
        );
    }

    public function test_explicit_refresh_reports_when_daily_and_paid_quota_are_unavailable(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $this->actingAs($user, 'rc')->postJson('/rc/resumes/'.$resume->id.'/refresh')->assertOk();

        $this->actingAs($user, 'rc')
            ->postJson('/rc/resumes/'.$resume->id.'/refresh')
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '今日免费刷新机会已使用，且简历刷新权益不足。');

        $this->assertDatabaseCount('rc_resume_refresh_logs', 1);
    }

    public function test_resume_updates_do_not_refresh_again_without_paid_quota(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $this->actingAs($user, 'rc')
            ->putJson('/rc/resumes/'.$resume->id, ['personal_advantage' => '第一次更新'])
            ->assertOk();
        $firstRefreshedAt = $resume->refresh()->refreshed_at;

        $this->travel(1)->hour();

        $this->actingAs($user, 'rc')
            ->putJson('/rc/resumes/'.$resume->id, ['personal_advantage' => '第二次更新'])
            ->assertOk();

        $this->assertTrue($resume->refresh()->refreshed_at->equalTo($firstRefreshedAt));
        $this->assertDatabaseCount('rc_resume_refresh_logs', 1);
    }

    private function createResume(User $user): Resume
    {
        return Resume::query()->create([
            'user_id' => $user->id,
            'title' => '测试简历',
            'full_name' => '测试用户',
            'phone' => '13800009000',
            'email' => 'refresh@example.com',
        ]);
    }
}
