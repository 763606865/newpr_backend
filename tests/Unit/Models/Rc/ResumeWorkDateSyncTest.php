<?php

namespace Tests\Unit\Models\Rc;

use App\Models\Rc\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ResumeWorkDateSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_resolve_work_start_date_from_work_years_uses_january_first(): void
    {
        Carbon::setTestNow('2026-06-09');

        $this->assertSame(
            '2023-01-01',
            Resume::resolveWorkStartDateFromWorkYears(3),
        );
    }

    public function test_saving_resume_derives_work_start_date_from_work_years(): void
    {
        Carbon::setTestNow('2026-06-09');

        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => 'Work Year Resume',
            'full_name' => 'Work Year User',
            'phone' => '13800000000',
            'email' => 'work-year@example.com',
            'work_years' => 5,
        ]);

        $this->assertSame('2021-01-01', $resume->fresh()->work_start_date);
    }

    public function test_saving_resume_prefers_work_start_date_when_only_date_is_provided(): void
    {
        Carbon::setTestNow('2026-06-09');

        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => 'Work Date Resume',
            'full_name' => 'Work Date User',
            'phone' => '13800000001',
            'email' => 'work-date@example.com',
            'work_start_date' => '2018-07-01',
        ]);

        $resume->refresh();

        $this->assertSame('2018-07-01', $resume->work_start_date);
        $this->assertSame(7, $resume->work_years);
    }

    public function test_saving_resume_keeps_user_work_years_when_both_fields_are_provided(): void
    {
        Carbon::setTestNow('2026-06-09');

        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => 'Conflict Resume',
            'full_name' => 'Conflict User',
            'phone' => '13800000002',
            'email' => 'conflict@example.com',
            'work_start_date' => '2020-03-15',
            'work_years' => 1,
        ]);

        $resume->refresh();

        $this->assertSame('2020-03-15', $resume->work_start_date);
        $this->assertSame(1, $resume->work_years);
    }
}
