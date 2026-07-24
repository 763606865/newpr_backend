<?php

namespace Tests\Unit\Resources\Rc;

use App\Enums\RcResumeStatus;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeCertificate;
use App\Models\Rc\ResumeEducation;
use App\Models\Rc\ResumeLanguage;
use App\Models\Rc\ResumePortfolio;
use App\Models\Rc\ResumeProject;
use App\Models\Rc\ResumeSkill;
use App\Models\Rc\ResumeTraining;
use App\Models\Rc\ResumeWork;
use App\Models\User;
use App\Resources\Rc\RcApplicationResumeResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RcApplicationResumeResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_includes_resume_sections(): void
    {
        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '候选人甲',
            'phone' => '13800138000',
            'email' => 'candidate@example.com',
            'status' => RcResumeStatus::Normal,
        ]);

        ResumeWork::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'company_name' => '示例科技',
            'position' => '后端工程师',
            'position_code' => 'custom-position',
            'start_date' => '2022-01-01',
        ]);

        ResumeEducation::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'school_name' => '浙江大学',
            'major' => '软件工程',
            'start_date' => '2018-09-01',
        ]);

        ResumeProject::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'project_name' => '招聘系统',
            'role' => '后端开发',
            'start_date' => '2023-01-01',
        ]);

        ResumeTraining::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'institution_name' => '培训机构',
            'course_name' => 'Laravel 进阶',
        ]);

        ResumeLanguage::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'language' => '英语',
        ]);

        ResumeSkill::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'skill_name' => 'Laravel',
        ]);

        ResumeCertificate::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'name' => '高级软件工程师',
            'issuer' => '工信部',
        ]);

        ResumePortfolio::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'title' => '个人作品集',
            'url' => 'https://example.com/portfolio',
        ]);

        $data = (new RcApplicationResumeResource($resume))->resolve(request());

        $this->assertSame('候选人甲', $data['full_name']);
        $this->assertSame('后端工程师', $data['works'][0]['position']);
        $this->assertSame('浙江大学', $data['educations'][0]['school_name']);
        $this->assertSame('招聘系统', $data['projects'][0]['project_name']);
        $this->assertSame('Laravel 进阶', $data['trainings'][0]['course_name']);
        $this->assertSame('英语', $data['languages'][0]['language']);
        $this->assertSame('Laravel', $data['skills'][0]['skill_name']);
        $this->assertSame('高级软件工程师', $data['certificates'][0]['name']);
        $this->assertSame('个人作品集', $data['portfolios'][0]['title']);
    }

    public function test_resource_reloads_relations_after_scout_cached_empty_collections(): void
    {
        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '候选人甲',
            'phone' => '13800138000',
            'email' => 'candidate@example.com',
            'status' => RcResumeStatus::Normal,
        ]);

        $resume->toSearchableArray();

        ResumeWork::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'company_name' => '示例科技',
            'position' => '后端工程师',
            'position_code' => 'custom-position',
            'start_date' => '2022-01-01',
        ]);

        $data = (new RcApplicationResumeResource($resume))->resolve(request());

        $this->assertSame('后端工程师', $data['works'][0]['position']);
    }

    public function test_normalize_stored_snapshot_adds_missing_sections(): void
    {
        $normalized = RcApplicationResumeResource::normalizeStoredSnapshot([
            'full_name' => '候选人甲',
            'works' => [['position' => '后端工程师']],
        ]);

        $this->assertSame('候选人甲', $normalized['full_name']);
        $this->assertSame('后端工程师', $normalized['works'][0]['position']);
        $this->assertSame([], $normalized['educations']);
        $this->assertSame([], $normalized['projects']);
        $this->assertSame([], $normalized['trainings']);
        $this->assertSame([], $normalized['languages']);
        $this->assertSame([], $normalized['skills']);
        $this->assertSame([], $normalized['certificates']);
        $this->assertSame([], $normalized['portfolios']);
    }
}
