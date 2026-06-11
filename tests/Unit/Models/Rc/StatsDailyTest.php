<?php

namespace Tests\Unit\Models\Rc;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\Rc\JobStatsDaily;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeStatsDaily;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StatsDailyTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_stats_daily_relationships_and_defaults(): void
    {
        Carbon::setTestNow('2026-06-11');

        $company = Company::query()->create([
            'name' => '示例企业',
            'credit_code' => '91360100MA0000000Z',
            'status' => CompanyStatus::Enabled,
        ]);

        $user = User::factory()->create();

        $job = Job::query()->create([
            'company_id' => $company->id,
            'creator_user_id' => $user->id,
            'code' => 'JOB-20260611-001',
            'title' => '后端工程师',
        ]);

        $stats = JobStatsDaily::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'job_id' => $job->id,
            'stat_date' => '2026-06-11',
        ]);

        $this->assertSame(0, $stats->views_total);
        $this->assertSame(0, $stats->views_uv);
        $this->assertSame('2026-06-11', $stats->stat_date->format('Y-m-d'));
        $this->assertTrue($job->statsDaily->contains($stats));
        $this->assertSame($job->id, $stats->job->id);
        $this->assertSame($company->id, $stats->company->id);
        $this->assertSame($user->id, $stats->user->id);
    }

    public function test_job_stats_daily_enforces_unique_job_and_date(): void
    {
        $company = Company::query()->create([
            'name' => '示例企业',
            'credit_code' => '91360100MA0000001A',
            'status' => CompanyStatus::Enabled,
        ]);

        $job = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-20260611-002',
            'title' => '产品经理',
        ]);

        JobStatsDaily::query()->create([
            'company_id' => $company->id,
            'job_id' => $job->id,
            'stat_date' => '2026-06-11',
            'views_total' => 10,
            'views_uv' => 6,
        ]);

        $this->expectException(QueryException::class);

        JobStatsDaily::query()->create([
            'company_id' => $company->id,
            'job_id' => $job->id,
            'stat_date' => '2026-06-11',
            'views_total' => 3,
            'views_uv' => 2,
        ]);
    }

    public function test_resume_stats_daily_relationships_and_defaults(): void
    {
        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '张三的简历',
            'full_name' => '张三',
            'phone' => '13800001111',
            'email' => 'zhang@example.com',
        ]);

        $stats = ResumeStatsDaily::query()->create([
            'user_id' => $user->id,
            'resume_id' => $resume->id,
            'stat_date' => '2026-06-11',
            'views_total' => 20,
            'views_uv' => 12,
        ]);

        $this->assertSame(20, $stats->views_total);
        $this->assertSame(12, $stats->views_uv);
        $this->assertTrue($resume->statsDaily->contains($stats));
        $this->assertSame($resume->id, $stats->resume->id);
        $this->assertSame($user->id, $stats->user->id);
    }

    public function test_resume_stats_daily_enforces_unique_resume_and_date(): void
    {
        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '李四的简历',
            'full_name' => '李四',
            'phone' => '13800002222',
            'email' => 'li@example.com',
        ]);

        ResumeStatsDaily::query()->create([
            'user_id' => $user->id,
            'resume_id' => $resume->id,
            'stat_date' => '2026-06-11',
        ]);

        $this->expectException(QueryException::class);

        ResumeStatsDaily::query()->create([
            'user_id' => $user->id,
            'resume_id' => $resume->id,
            'stat_date' => '2026-06-11',
        ]);
    }
}
