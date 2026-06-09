<?php

namespace Tests\Unit\Services;

use App\Enums\RcCurrentIdentity;
use App\Enums\RcEducationLevel;
use App\Enums\RcResumeJobStatus;
use App\Enums\RcSalaryUnit;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeEducation;
use App\Models\Rc\ResumeIntention;
use App\Models\Rc\ResumeWork;
use App\Models\User;
use App\Services\RcResumeAggregateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RcResumeAggregateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_updates_expected_salary_from_latest_intention(): void
    {
        $resume = $this->createResume();

        ResumeIntention::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $resume->user_id,
            'salary_min' => 8000,
            'salary_max' => 12000,
            'salary_unit' => RcSalaryUnit::Month,
            'updated_at' => Carbon::parse('2026-01-01 10:00:00'),
        ]);

        ResumeIntention::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $resume->user_id,
            'salary_min' => 15000,
            'salary_max' => 25000,
            'salary_unit' => RcSalaryUnit::Month,
            'updated_at' => Carbon::parse('2026-06-01 10:00:00'),
        ]);

        RcResumeAggregateService::make()->sync($resume->fresh());

        $resume->refresh();

        $this->assertSame('15000.00', (string) $resume->expected_salary_min);
        $this->assertSame('25000.00', (string) $resume->expected_salary_max);
        $this->assertSame(RcSalaryUnit::Month, $resume->expected_salary_unit);
    }

    public function test_sync_updates_highest_education_level_from_educations(): void
    {
        $resume = $this->createResume();

        ResumeEducation::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $resume->user_id,
            'school_name' => '示例大学',
            'degree' => RcEducationLevel::Bachelor,
            'start_date' => '2016-09-01',
            'end_date' => '2020-06-30',
        ]);

        ResumeEducation::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $resume->user_id,
            'school_name' => '示例研究生院',
            'degree' => RcEducationLevel::Master,
            'start_date' => '2020-09-01',
            'end_date' => '2023-06-30',
        ]);

        RcResumeAggregateService::make()->sync($resume->fresh());

        $this->assertSame(RcEducationLevel::Master, $resume->fresh()->highest_education_level);
    }

    public function test_sync_updates_work_start_date_and_work_years_from_works(): void
    {
        Carbon::setTestNow('2026-06-09');

        $resume = $this->createResume();

        ResumeWork::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $resume->user_id,
            'company_name' => '乙公司',
            'position' => '工程师',
            'start_date' => '2020-03-01',
            'end_date' => '2023-12-31',
        ]);

        ResumeWork::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $resume->user_id,
            'company_name' => '甲公司',
            'position' => '开发',
            'start_date' => '2018-07-01',
            'end_date' => '2020-02-29',
        ]);

        RcResumeAggregateService::make()->sync($resume->fresh());

        $resume->refresh();

        $this->assertSame('2018-07-01', $resume->work_start_date);
        $this->assertSame(7, $resume->work_years);
        $this->assertSame(RcCurrentIdentity::WorkingPerson, $resume->current_identity);
    }

    public function test_sync_marks_fresh_graduate_from_intention_job_status(): void
    {
        $resume = $this->createResume();

        ResumeIntention::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $resume->user_id,
            'job_status' => RcResumeJobStatus::FreshGraduate,
        ]);

        RcResumeAggregateService::make()->sync($resume->fresh());

        $resume->refresh();

        $this->assertSame(1, $resume->is_fresh_graduate);
        $this->assertSame(RcCurrentIdentity::Student, $resume->current_identity);
    }

    public function test_sync_clears_aggregate_fields_when_child_records_removed(): void
    {
        $resume = $this->createResume();

        $intention = ResumeIntention::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $resume->user_id,
            'salary_min' => 10000,
            'salary_max' => 15000,
        ]);

        $education = ResumeEducation::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $resume->user_id,
            'school_name' => '示例大学',
            'degree' => RcEducationLevel::Bachelor,
            'start_date' => '2016-09-01',
            'end_date' => '2020-06-30',
        ]);

        $work = ResumeWork::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $resume->user_id,
            'company_name' => '示例公司',
            'position' => '工程师',
            'start_date' => '2020-03-01',
        ]);

        $resume->refresh();
        $this->assertNotNull($resume->expected_salary_min);
        $this->assertNotNull($resume->highest_education_level);
        $this->assertNotNull($resume->work_start_date);

        $intention->delete();
        $education->delete();
        $work->delete();

        $resume->refresh();

        $this->assertNull($resume->expected_salary_min);
        $this->assertNull($resume->expected_salary_max);
        $this->assertNull($resume->highest_education_level);
        $this->assertNull($resume->work_start_date);
        $this->assertNull($resume->work_years);
        $this->assertSame(0, $resume->is_fresh_graduate);
    }

    private function createResume(): Resume
    {
        $user = User::factory()->create();

        return Resume::query()->create([
            'user_id' => $user->id,
            'title' => '测试简历',
            'full_name' => '张三',
            'phone' => '13800000000',
            'email' => 'zhang@example.com',
        ]);
    }
}
