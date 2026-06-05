<?php

namespace Tests\Unit\Services;

use App\Enums\RcEducationLevel;
use App\Enums\RcResumeStatus;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeEducation;
use App\Models\User;
use App\Services\RcResumeSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RcResumeSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_resume_by_school_name(): void
    {
        $user = User::factory()->create();

        $matched = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '张三',
            'phone' => '13800138000',
            'email' => 'zhangsan@example.com',
            'highest_education_level' => RcEducationLevel::Bachelor,
            'status' => RcResumeStatus::Normal,
        ]);

        ResumeEducation::query()->create([
            'resume_id' => $matched->id,
            'user_id' => $user->id,
            'school_name' => '浙江大学',
            'major' => '软件工程',
            'start_date' => '2018-09-01',
        ]);

        $otherUser = User::factory()->create();

        Resume::query()->create([
            'user_id' => $otherUser->id,
            'title' => '另一份简历',
            'full_name' => '李四',
            'phone' => '13800138001',
            'email' => 'lisi@example.com',
            'status' => RcResumeStatus::Normal,
        ]);

        $paginator = RcResumeSearchService::make()->search(15, [
            'keyword' => '浙江大学',
        ]);

        $this->assertSame(1, $paginator->total());
        $this->assertSame('张三', $paginator->items()[0]->full_name);
    }

    public function test_search_excludes_disabled_resumes(): void
    {
        $user = User::factory()->create();

        Resume::query()->create([
            'user_id' => $user->id,
            'title' => '停用简历',
            'full_name' => '王五',
            'phone' => '13800138002',
            'email' => 'wangwu@example.com',
            'status' => RcResumeStatus::Disabled,
        ]);

        $paginator = RcResumeSearchService::make()->search(15, [
            'keyword' => '王五',
        ]);

        $this->assertSame(0, $paginator->total());
    }
}
