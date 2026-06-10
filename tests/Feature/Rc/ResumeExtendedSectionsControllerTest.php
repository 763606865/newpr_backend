<?php

namespace Tests\Feature\Rc;

use App\Models\Rc\Resume;
use App\Models\Rc\ResumeProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ResumeExtendedSectionsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_project_endpoints_support_crud(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $storeResponse = $this->actingAs($user, 'rc')
            ->postJson('/rc/resumes/'.$resume->id.'/projects', [
                'project_name' => '招聘系统',
                'role' => '后端负责人',
                'start_date' => '2023-01-01',
                'end_date' => '2024-01-01',
                'sort' => 5,
            ]);

        $storeResponse
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.project_name', '招聘系统');

        $projectId = (int) $storeResponse->json('data.id');

        $this->actingAs($user, 'rc')
            ->getJson('/rc/resumes/'.$resume->id.'/projects')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonCount(1, 'data.projects');

        $this->actingAs($user, 'rc')
            ->putJson('/rc/resumes/'.$resume->id.'/projects/'.$projectId, [
                'role' => '技术负责人',
            ])
            ->assertOk()
            ->assertJsonPath('data.role', '技术负责人');

        $this->actingAs($user, 'rc')
            ->deleteJson('/rc/resumes/'.$resume->id.'/projects/'.$projectId)
            ->assertOk()
            ->assertJsonPath('code', 200);

        $this->assertSoftDeleted('rc_resume_projects', ['id' => $projectId]);
    }

    public function test_training_language_skill_and_certificate_endpoints_support_crud(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $trainingId = (int) $this->actingAs($user, 'rc')
            ->postJson('/rc/resumes/'.$resume->id.'/trainings', [
                'institution_name' => '某某培训机构',
                'course_name' => 'Laravel 进阶',
                'start_date' => '2024-03-01',
                'end_date' => '2024-06-01',
            ])
            ->json('data.id');

        $languageId = (int) $this->actingAs($user, 'rc')
            ->postJson('/rc/resumes/'.$resume->id.'/languages', [
                'language' => '英语',
                'proficiency' => 3,
                'certificate' => 'CET-6',
            ])
            ->json('data.id');

        $skillId = (int) $this->actingAs($user, 'rc')
            ->postJson('/rc/resumes/'.$resume->id.'/skills', [
                'skill_name' => 'PHP',
                'proficiency' => 4,
            ])
            ->json('data.id');

        $certificateId = (int) $this->actingAs($user, 'rc')
            ->postJson('/rc/resumes/'.$resume->id.'/certificates', [
                'name' => 'PMP 项目管理',
                'cert_type' => 1,
                'issue_date' => '2025-01-01',
            ])
            ->json('data.id');

        $this->actingAs($user, 'rc')
            ->getJson('/rc/resumes/'.$resume->id.'/trainings')
            ->assertOk()
            ->assertJsonCount(1, 'data.trainings');

        $this->actingAs($user, 'rc')
            ->getJson('/rc/resumes/'.$resume->id.'/languages/'.$languageId)
            ->assertOk()
            ->assertJsonPath('data.language', '英语')
            ->assertJsonPath('data.proficiency_label', '商务谈判');

        $this->actingAs($user, 'rc')
            ->putJson('/rc/resumes/'.$resume->id.'/skills/'.$skillId, [
                'description' => '5 年服务端开发经验',
            ])
            ->assertOk()
            ->assertJsonPath('data.description', '5 年服务端开发经验');

        $this->actingAs($user, 'rc')
            ->deleteJson('/rc/resumes/'.$resume->id.'/certificates/'.$certificateId)
            ->assertOk();

        $this->assertSoftDeleted('rc_resume_certificates', ['id' => $certificateId]);
        $this->assertDatabaseHas('rc_resume_trainings', ['id' => $trainingId]);
    }

    public function test_portfolio_endpoint_returns_display_urls(): void
    {
        $disk = Mockery::mock();
        $disk->shouldReceive('url')
            ->with('uploads/rc/portfolio/demo.jpg')
            ->andReturn('https://cdn.example.com/uploads/rc/portfolio/demo.jpg');
        $disk->shouldReceive('url')
            ->with('uploads/rc/portfolio/cover.jpg')
            ->andReturn('https://cdn.example.com/uploads/rc/portfolio/cover.jpg');

        Storage::shouldReceive('disk')
            ->twice()
            ->with('oss')
            ->andReturn($disk);

        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $response = $this->actingAs($user, 'rc')
            ->postJson('/rc/resumes/'.$resume->id.'/portfolios', [
                'title' => '个人主页',
                'type' => 2,
                'url' => 'uploads/rc/portfolio/demo.jpg',
                'cover_url' => 'uploads/rc/portfolio/cover.jpg',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.title', '个人主页')
            ->assertJsonPath('data.url', 'uploads/rc/portfolio/demo.jpg')
            ->assertJsonPath('data.display_url', 'https://cdn.example.com/uploads/rc/portfolio/demo.jpg')
            ->assertJsonPath('data.display_cover_url', 'https://cdn.example.com/uploads/rc/portfolio/cover.jpg');
    }

    public function test_section_endpoints_return_404_for_other_users_resume(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherResume = $this->createResume($otherUser);
        $otherProject = ResumeProject::query()->create([
            'resume_id' => $otherResume->id,
            'user_id' => $otherUser->id,
            'project_name' => 'Secret',
            'start_date' => '2020-01-01',
        ]);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/resumes/'.$otherResume->id.'/projects')
            ->assertOk()
            ->assertJsonPath('code', 404);

        $this->actingAs($user, 'rc')
            ->postJson('/rc/resumes/'.$otherResume->id.'/skills', [
                'skill_name' => 'Blocked',
            ])
            ->assertOk()
            ->assertJsonPath('code', 404);

        $this->actingAs($user, 'rc')
            ->deleteJson('/rc/resumes/'.$otherResume->id.'/projects/'.$otherProject->id)
            ->assertOk()
            ->assertJsonPath('code', 404);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createResume(User $user, array $overrides = []): Resume
    {
        return Resume::query()->create(array_merge([
            'user_id' => $user->id,
            'resume_no' => 'RC-'.fake()->unique()->numerify('##########'),
            'title' => 'Test Resume',
            'full_name' => 'Tester',
            'phone' => fake()->unique()->numerify('1##########'),
            'email' => fake()->unique()->safeEmail(),
        ], $overrides));
    }
}
