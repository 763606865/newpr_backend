<?php

namespace Tests\Feature\Rc;

use App\Models\Rc\Resume;
use App\Models\Rc\ResumeIntention;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ResumeIntentionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_index_returns_all_intentions_of_current_users_resume(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);
        $otherResume = $this->createResume($user, ['title' => 'Other Resume']);

        $firstIntention = $this->createIntention($resume, [
            'job_status' => 1,
            'expected_city_code' => '330100',
        ]);
        $secondIntention = $this->createIntention($resume, [
            'job_status' => 3,
            'expected_city_code' => '310100',
        ]);
        $this->createIntention($otherResume, [
            'job_status' => 2,
            'expected_city_code' => '440100',
        ]);

        $response = $this->actingAs($user, 'rc')
            ->getJson('/rc/resumes/'.$resume->id.'/intentions');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonCount(2, 'data.intentions')
            ->assertJsonPath('data.intentions.0.id', $secondIntention->id)
            ->assertJsonPath('data.intentions.1.id', $firstIntention->id);
    }

    public function test_show_returns_single_intention(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);
        $intention = $this->createIntention($resume, [
            'job_status' => 3,
            'employment_type' => 1,
            'expected_city_code' => '330100',
            'salary_min' => 10000,
            'salary_max' => 20000,
        ]);

        $response = $this->actingAs($user, 'rc')
            ->getJson('/rc/resumes/'.$resume->id.'/intentions/'.$intention->id);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.id', $intention->id)
            ->assertJsonPath('data.job_status', 3)
            ->assertJsonPath('data.employment_type', 1)
            ->assertJsonPath('data.expected_city_code', '330100');
    }

    public function test_store_and_update_can_manage_multiple_intentions(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $firstPayload = [
            'job_status' => 3,
            'employment_type' => 1,
            'expected_city_code' => '330100',
            'expected_industry_codes' => ['A01', 'A02'],
            'expected_position_id' => 1001,
            'salary_min' => 10000,
            'salary_max' => 20000,
            'salary_unit' => 1,
            'available_date' => '2026-06-15',
        ];

        $createResponse = $this->actingAs($user, 'rc')
            ->postJson('/rc/resumes/'.$resume->id.'/intentions', $firstPayload);

        $createResponse
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.job_status', 3)
            ->assertJsonPath('data.employment_type', 1)
            ->assertJsonPath('data.expected_city_code', '330100')
            ->assertJsonPath('data.expected_industry_codes.0', 'A01')
            ->assertJsonPath('data.expected_position_id', 1001)
            ->assertJsonPath('data.salary_min', '10000.00')
            ->assertJsonPath('data.salary_max', '20000.00')
            ->assertJsonPath('data.salary_unit', 1);

        $this->assertSame(
            '2026-06-15',
            Carbon::parse((string) $createResponse->json('data.available_date'))
                ->timezone(config('app.timezone'))
                ->toDateString(),
        );

        $this->assertDatabaseHas('rc_resume_intentions', [
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'job_status' => 3,
            'salary_unit' => 1,
        ]);

        $secondCreateResponse = $this->actingAs($user, 'rc')
            ->postJson('/rc/resumes/'.$resume->id.'/intentions', [
                'job_status' => 1,
                'expected_city_code' => '110100',
                'salary_min' => 15000,
                'salary_max' => 30000,
            ]);

        $secondCreateResponse
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.job_status', 1)
            ->assertJsonPath('data.expected_city_code', '110100');

        $this->assertDatabaseCount('rc_resume_intentions', 2);

        $intentionId = (int) $createResponse->json('data.id');

        $updateResponse = $this->actingAs($user, 'rc')
            ->putJson('/rc/resumes/'.$resume->id.'/intentions/'.$intentionId, [
                'job_status' => 1,
                'salary_max' => 25000,
            ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.job_status', 1)
            ->assertJsonPath('data.salary_max', '25000.00');

        $this->assertDatabaseHas('rc_resume_intentions', [
            'id' => $intentionId,
            'job_status' => 1,
            'salary_max' => 25000,
        ]);
    }

    public function test_destroy_soft_deletes_intention(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);
        $intention = $this->createIntention($resume);

        $response = $this->actingAs($user, 'rc')
            ->deleteJson('/rc/resumes/'.$resume->id.'/intentions/'.$intention->id);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200);

        $this->assertSoftDeleted('rc_resume_intentions', [
            'id' => $intention->id,
        ]);
    }

    public function test_intention_endpoints_return_404_for_other_users_resume_or_intention(): void
    {
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherResume = $this->createResume($otherUser);
        $otherIntention = $this->createIntention($otherResume);

        $indexResponse = $this->actingAs($currentUser, 'rc')
            ->getJson('/rc/resumes/'.$otherResume->id.'/intentions');

        $indexResponse
            ->assertOk()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '简历不存在。');

        $showResponse = $this->actingAs($currentUser, 'rc')
            ->getJson('/rc/resumes/'.$otherResume->id.'/intentions/'.$otherIntention->id);

        $showResponse
            ->assertOk()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '简历不存在。');

        $storeResponse = $this->actingAs($currentUser, 'rc')
            ->postJson('/rc/resumes/'.$otherResume->id.'/intentions', ['job_status' => 1]);

        $storeResponse
            ->assertOk()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '简历不存在。');

        $updateResponse = $this->actingAs($currentUser, 'rc')
            ->putJson('/rc/resumes/'.$otherResume->id.'/intentions/'.$otherIntention->id, ['job_status' => 1]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '简历不存在。');

        $deleteResponse = $this->actingAs($currentUser, 'rc')
            ->deleteJson('/rc/resumes/'.$otherResume->id.'/intentions/'.$otherIntention->id);

        $deleteResponse
            ->assertOk()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '简历不存在。');
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
    private function createIntention(Resume $resume, array $overrides = []): ResumeIntention
    {
        return ResumeIntention::query()->create(array_merge([
            'resume_id' => $resume->id,
            'user_id' => $resume->user_id,
            'job_status' => 1,
            'salary_unit' => 1,
        ], $overrides));
    }
}
