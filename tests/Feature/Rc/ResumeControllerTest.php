<?php

namespace Tests\Feature\Rc;

use App\Models\Rc\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_index_returns_only_current_user_resumes(): void
    {
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $primaryResume = $this->createResume($currentUser, [
            'title' => 'Primary Resume',
            'is_primary' => 1,
        ]);
        $this->createResume($currentUser, [
            'title' => 'Second Resume',
            'is_primary' => 0,
        ]);
        $this->createResume($otherUser, [
            'title' => 'Other User Resume',
            'is_primary' => 1,
        ]);

        $response = $this
            ->actingAs($currentUser, 'rc')
            ->getJson('/rc/resumes?page_size=10');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.data.0.id', $primaryResume->id);

        $this->assertSame('Second Resume', $response->json('data.data.1.title'));
    }

    public function test_show_returns_not_found_when_resume_does_not_belong_to_user(): void
    {
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherResume = $this->createResume($otherUser);

        $response = $this
            ->actingAs($currentUser, 'rc')
            ->getJson('/rc/resumes/'.$otherResume->id);

        $response
            ->assertOk()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '简历不存在。');
    }

    public function test_store_creates_resume_and_sets_first_resume_as_primary(): void
    {
        $user = User::factory()->create();

        $payload = [
            'title' => 'New Resume',
            'full_name' => 'Test User',
            'avatar' => 'uploads/rc/avatar/2026/06/03/example.jpg',
            'phone' => '13800000000',
            'email' => 'resume@example.com',
            'is_primary' => 0,
        ];

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/resumes', $payload);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.title', 'New Resume')
            ->assertJsonPath('data.avatar', 'uploads/rc/avatar/2026/06/03/example.jpg')
            ->assertJsonPath('data.is_primary', 1)
            ->assertJsonPath('data.source_type', 3);

        $this->assertDatabaseHas('rc_resumes', [
            'user_id' => $user->id,
            'title' => 'New Resume',
            'avatar' => 'uploads/rc/avatar/2026/06/03/example.jpg',
            'is_primary' => 1,
        ]);
    }

    public function test_update_can_update_avatar(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user, [
            'avatar' => 'uploads/rc/avatar/2026/06/03/old.jpg',
        ]);

        $response = $this
            ->actingAs($user, 'rc')
            ->putJson('/rc/resumes/'.$resume->id, [
                'avatar' => 'uploads/rc/avatar/2026/06/03/new.jpg',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.avatar', 'uploads/rc/avatar/2026/06/03/new.jpg');

        $this->assertDatabaseHas('rc_resumes', [
            'id' => $resume->id,
            'avatar' => 'uploads/rc/avatar/2026/06/03/new.jpg',
        ]);
    }

    public function test_update_can_switch_primary_resume(): void
    {
        $user = User::factory()->create();

        $oldPrimary = $this->createResume($user, [
            'title' => 'Old Primary',
            'is_primary' => 1,
        ]);
        $targetResume = $this->createResume($user, [
            'title' => 'Target Resume',
            'is_primary' => 0,
        ]);

        $response = $this
            ->actingAs($user, 'rc')
            ->putJson('/rc/resumes/'.$targetResume->id, [
                'title' => 'Updated Resume',
                'is_primary' => 1,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.id', $targetResume->id)
            ->assertJsonPath('data.title', 'Updated Resume')
            ->assertJsonPath('data.is_primary', 1);

        $this->assertDatabaseHas('rc_resumes', [
            'id' => $targetResume->id,
            'is_primary' => 1,
            'title' => 'Updated Resume',
        ]);
        $this->assertDatabaseHas('rc_resumes', [
            'id' => $oldPrimary->id,
            'is_primary' => 0,
        ]);
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
