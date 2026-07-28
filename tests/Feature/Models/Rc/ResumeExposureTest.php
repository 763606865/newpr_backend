<?php

namespace Tests\Feature\Models\Rc;

use App\Enums\RcResumeExposureStatus;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeExposure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ResumeExposureTest extends TestCase
{
    use RefreshDatabase;

    public function test_resume_refresh_time_and_exposure_relations_are_persisted(): void
    {
        $user = User::factory()->create();
        $refreshedAt = Carbon::parse('2026-07-28 09:00:00');

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'full_name' => '测试求职者',
            'phone' => '13800000000',
            'email' => 'candidate@example.com',
            'refreshed_at' => $refreshedAt,
        ]);

        $exposure = ResumeExposure::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'started_at' => Carbon::parse('2026-07-28 08:00:00'),
            'expired_at' => Carbon::parse('2026-08-04 08:00:00'),
            'status' => RcResumeExposureStatus::Active,
            'extra' => ['source' => 'asset'],
        ]);

        $this->assertTrue($resume->refresh()->refreshed_at->equalTo($refreshedAt));
        $this->assertTrue($resume->exposures->contains($exposure));
        $this->assertTrue($exposure->resume->is($resume));
        $this->assertTrue($exposure->user->is($user));
        $this->assertSame(RcResumeExposureStatus::Active, $exposure->status);
        $this->assertSame(['source' => 'asset'], $exposure->extra);
    }

    public function test_active_scope_only_returns_effective_exposures(): void
    {
        $user = User::factory()->create();
        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'full_name' => '测试求职者',
            'phone' => '13800000001',
            'email' => 'candidate2@example.com',
        ]);
        $now = Carbon::parse('2026-07-28 10:00:00');

        $activeExposure = $this->createExposure(
            $resume,
            $user,
            RcResumeExposureStatus::Active,
            $now->copy()->subDay(),
            $now->copy()->addDay(),
        );
        $this->createExposure(
            $resume,
            $user,
            RcResumeExposureStatus::Expired,
            $now->copy()->subDays(2),
            $now->copy()->subDay(),
        );
        $this->createExposure(
            $resume,
            $user,
            RcResumeExposureStatus::Pending,
            $now->copy()->addDay(),
            $now->copy()->addDays(2),
        );

        $effectiveExposures = ResumeExposure::query()->active($now)->get();

        $this->assertCount(1, $effectiveExposures);
        $this->assertTrue($effectiveExposures->sole()->is($activeExposure));
    }

    private function createExposure(
        Resume $resume,
        User $user,
        RcResumeExposureStatus $status,
        Carbon $startedAt,
        Carbon $expiredAt,
    ): ResumeExposure {
        return ResumeExposure::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'started_at' => $startedAt,
            'expired_at' => $expiredAt,
            'status' => $status,
        ]);
    }
}
