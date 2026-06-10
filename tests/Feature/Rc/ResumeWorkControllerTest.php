<?php

namespace Tests\Feature\Rc;

use App\Models\Rc\Position;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeWork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ResumeWorkControllerTest extends TestCase
{
    use RefreshDatabase;

    private const POSITION_CODE = 'backend-developer';

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        Position::query()->create([
            'name' => '后端开发',
            'code' => self::POSITION_CODE,
            'sort' => 1,
        ]);
    }

    public function test_index_returns_works_of_current_resume(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);
        $otherResume = $this->createResume($user, ['title' => 'Other Resume']);

        $firstWork = $this->createWork($resume, ['sort' => 1, 'position_code' => self::POSITION_CODE]);
        $secondWork = $this->createWork($resume, ['sort' => 2, 'position_code' => self::POSITION_CODE]);
        $this->createWork($otherResume, ['sort' => 99, 'position_code' => self::POSITION_CODE]);

        $response = $this->actingAs($user, 'rc')
            ->getJson('/rc/resumes/'.$resume->id.'/works');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonCount(2, 'data.works')
            ->assertJsonPath('data.works.0.id', $secondWork->id)
            ->assertJsonPath('data.works.1.id', $firstWork->id);
    }

    public function test_store_update_and_destroy_work(): void
    {
        Carbon::setTestNow('2026-06-09');

        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $storeResponse = $this->actingAs($user, 'rc')
            ->postJson('/rc/resumes/'.$resume->id.'/works', [
                'company_name' => 'Acme Inc',
                'position_code' => self::POSITION_CODE,
                'employment_type' => 1,
                'start_date' => '2022-01-01',
                'end_date' => '2023-01-01',
                'is_current' => 0,
                'sort' => 10,
            ]);

        $storeResponse
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.company_name', 'Acme Inc')
            ->assertJsonPath('data.position_code', self::POSITION_CODE)
            ->assertJsonPath('data.position', '后端开发');

        $resume->refresh();
        $this->assertSame('2022-01-01', $resume->work_start_date);
        $this->assertSame(4, $resume->work_years);

        $workId = (int) $storeResponse->json('data.id');

        Position::query()->create([
            'name' => '高级后端开发',
            'code' => 'senior-backend-developer',
            'sort' => 2,
        ]);

        $updateResponse = $this->actingAs($user, 'rc')
            ->putJson('/rc/resumes/'.$resume->id.'/works/'.$workId, [
                'position_code' => 'senior-backend-developer',
                'is_current' => 1,
            ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.position_code', 'senior-backend-developer')
            ->assertJsonPath('data.position', '高级后端开发')
            ->assertJsonPath('data.is_current', 1);

        $deleteResponse = $this->actingAs($user, 'rc')
            ->deleteJson('/rc/resumes/'.$resume->id.'/works/'.$workId);

        $deleteResponse->assertOk()->assertJsonPath('code', 200);

        $this->assertSoftDeleted('rc_resume_works', ['id' => $workId]);
    }

    public function test_work_endpoints_return_404_for_other_users_resume(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherResume = $this->createResume($otherUser);
        $otherWork = $this->createWork($otherResume);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/resumes/'.$otherResume->id.'/works')
            ->assertOk()
            ->assertJsonPath('code', 404);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/resumes/'.$otherResume->id.'/works/'.$otherWork->id)
            ->assertOk()
            ->assertJsonPath('code', 404);

        $this->actingAs($user, 'rc')
            ->postJson('/rc/resumes/'.$otherResume->id.'/works', [
                'company_name' => 'Blocked',
                'position_code' => self::POSITION_CODE,
                'start_date' => '2020-01-01',
            ])
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createWork(Resume $resume, array $overrides = []): ResumeWork
    {
        return ResumeWork::query()->create(array_merge([
            'resume_id' => $resume->id,
            'user_id' => $resume->user_id,
            'company_name' => 'Example Co',
            'position_code' => self::POSITION_CODE,
            'position' => '后端开发',
            'start_date' => '2021-01-01',
            'employment_type' => 1,
            'is_current' => 0,
            'sort' => 0,
        ], $overrides));
    }
}
