<?php

namespace Tests\Unit\Models\Rc;

use App\Enums\RcEducationLevel;
use App\Enums\RcResumeStatus;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeEducation;
use App\Models\Rc\ResumeWork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeSearchableTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_searchable_array_flattens_related_experiences(): void
    {
        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '张三',
            'phone' => '13800138000',
            'email' => 'zhangsan@example.com',
            'highest_education_level' => RcEducationLevel::Bachelor,
            'current_city_code' => '360102',
            'status' => RcResumeStatus::Normal,
        ]);

        ResumeWork::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'company_name' => '杭州示例科技有限公司',
            'position' => '高级后端工程师',
            'start_date' => '2022-01-01',
            'description' => '负责招聘系统 API 开发',
        ]);

        ResumeEducation::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'school_name' => '浙江大学',
            'major' => '计算机科学与技术',
            'start_date' => '2018-09-01',
        ]);

        $searchable = $resume->fresh(['works', 'educations'])->toSearchableArray();

        $this->assertSame('rc_resumes', $resume->searchableAs());
        $this->assertTrue($resume->shouldBeSearchable());
        $this->assertStringContainsString('杭州示例科技有限公司', $searchable['company_names']);
        $this->assertStringContainsString('高级后端工程师', $searchable['positions']);
        $this->assertStringContainsString('浙江大学', $searchable['school_names']);
        $this->assertStringContainsString('计算机科学与技术', $searchable['majors']);
        $this->assertSame('3601', $searchable['current_city_code_prefix']);
    }

    public function test_disabled_resume_should_not_be_searchable(): void
    {
        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '停用简历',
            'full_name' => '李四',
            'phone' => '13800138001',
            'email' => 'lisi@example.com',
            'status' => RcResumeStatus::Disabled,
        ]);

        $this->assertFalse($resume->shouldBeSearchable());
    }
}
