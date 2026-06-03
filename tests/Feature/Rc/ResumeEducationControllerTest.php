<?php

namespace Tests\Feature\Rc;

use App\Models\Rc\Resume;
use App\Models\Rc\ResumeEducation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeEducationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_index_returns_educations_of_current_resume(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);
        $otherResume = $this->createResume($user, ['title' => 'Other Resume']);

        $firstEducation = $this->createEducation($resume, ['sort' => 1, 'school_name' => 'School A']);
        $secondEducation = $this->createEducation($resume, ['sort' => 2, 'school_name' => 'School B']);
        $this->createEducation($otherResume, ['sort' => 99, 'school_name' => 'Ignored']);

        $response = $this->actingAs($user, 'rc')
            ->getJson('/rc/resumes/'.$resume->id.'/educations');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonCount(2, 'data.educations')
            ->assertJsonPath('data.educations.0.id', $secondEducation->id)
            ->assertJsonPath('data.educations.1.id', $firstEducation->id);
    }

    public function test_store_update_and_destroy_education(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $storeResponse = $this->actingAs($user, 'rc')
            ->postJson('/rc/resumes/'.$resume->id.'/educations', [
                'school_name' => 'Zhejiang University',
                'major' => 'Computer Science',
                'degree' => 3,
                'education_type' => 1,
                'start_date' => '2018-09-01',
                'end_date' => '2022-06-30',
                'is_current' => 0,
                'sort' => 10,
            ]);

        $storeResponse
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.school_name', 'Zhejiang University')
            ->assertJsonPath('data.major', 'Computer Science');

        $educationId = (int) $storeResponse->json('data.id');

        $updateResponse = $this->actingAs($user, 'rc')
            ->putJson('/rc/resumes/'.$resume->id.'/educations/'.$educationId, [
                'major' => 'Software Engineering',
                'is_current' => 1,
            ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.major', 'Software Engineering')
            ->assertJsonPath('data.is_current', 1);

        $deleteResponse = $this->actingAs($user, 'rc')
            ->deleteJson('/rc/resumes/'.$resume->id.'/educations/'.$educationId);

        $deleteResponse->assertOk()->assertJsonPath('code', 200);

        $this->assertSoftDeleted('rc_resume_educations', ['id' => $educationId]);
    }

    public function test_education_endpoints_return_404_for_other_users_resume(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherResume = $this->createResume($otherUser);
        $otherEducation = $this->createEducation($otherResume);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/resumes/'.$otherResume->id.'/educations')
            ->assertOk()
            ->assertJsonPath('code', 404);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/resumes/'.$otherResume->id.'/educations/'.$otherEducation->id)
            ->assertOk()
            ->assertJsonPath('code', 404);

        $this->actingAs($user, 'rc')
            ->postJson('/rc/resumes/'.$otherResume->id.'/educations', [
                'school_name' => 'Blocked',
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
    private function createEducation(Resume $resume, array $overrides = []): ResumeEducation
    {
        return ResumeEducation::query()->create(array_merge([
            'resume_id' => $resume->id,
            'user_id' => $resume->user_id,
            'school_name' => 'Example University',
            'start_date' => '2020-09-01',
            'degree' => 3,
            'education_type' => 1,
            'is_current' => 0,
            'sort' => 0,
        ], $overrides));
    }
}
