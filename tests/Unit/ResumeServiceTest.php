<?php

namespace Tests\Unit;

use App\Enums\UserGender;
use App\Models\Rc\Resume;
use App\Models\User;
use App\Services\ResumeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_promote_clears_other_primary_resumes(): void
    {
        $user = User::factory()->create();
        $oldPrimary = Resume::query()->create($this->resumeAttributes($user, [
            'title' => 'Old',
            'is_primary' => 1,
        ]));
        $newPrimary = Resume::query()->create($this->resumeAttributes($user, [
            'title' => 'New',
            'is_primary' => 0,
        ]));

        ResumeService::make()->promote($user, $newPrimary);

        $this->assertDatabaseHas('rc_resumes', [
            'id' => $oldPrimary->id,
            'is_primary' => 0,
        ]);
        $this->assertDatabaseHas('rc_resumes', [
            'id' => $newPrimary->id,
            'is_primary' => 0,
        ]);
    }

    public function test_sync_user_profile_only_fills_blank_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'Kept Name',
            'nickname' => null,
            'avatar' => '',
            'gender' => UserGender::Unknown,
        ]);
        $resume = Resume::query()->create($this->resumeAttributes($user, [
            'full_name' => 'Resume Name',
            'avatar' => 'uploads/rc/avatar/resume.jpg',
            'gender' => UserGender::Male->value,
        ]));

        ResumeService::make()->syncUserProfileFromResume($user, $resume);

        $user->refresh();

        $this->assertSame('Kept Name', $user->name);
        $this->assertSame('Resume Name', $user->nickname);
        $this->assertSame('uploads/rc/avatar/resume.jpg', $user->avatar);
        $this->assertSame(UserGender::Male, $user->gender);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function resumeAttributes(User $user, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $user->id,
            'resume_no' => 'RC-'.fake()->unique()->numerify('##########'),
            'title' => 'Test Resume',
            'full_name' => 'Tester',
            'phone' => fake()->unique()->numerify('1##########'),
            'email' => fake()->unique()->safeEmail(),
            'is_primary' => 0,
        ], $overrides);
    }
}
