<?php

namespace Tests\Feature\Rc;

use App\Enums\UserGender;
use App\Models\Rc\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ResumeAvatarUploadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_upload_avatar_requires_authentication(): void
    {
        $response = $this->patch('/api/resume/1/avatar/upload', [
            'file' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $this->assertContains($response->status(), [401, 422, 500]);
    }

    public function test_upload_avatar_returns_not_found_when_resume_does_not_belong_to_user(): void
    {
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $resume = $this->createResume($otherUser);

        $response = $this
            ->actingAs($currentUser, 'rc')
            ->patch('/api/resume/'.$resume->id.'/avatar/upload', [
                'file' => UploadedFile::fake()->image('avatar.jpg'),
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '简历不存在。');
    }

    public function test_upload_avatar_rejects_non_image_file(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $response = $this
            ->actingAs($user, 'rc')
            ->patch('/api/resume/'.$resume->id.'/avatar/upload', [
                'file' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(422);
    }

    public function test_upload_avatar_stores_file_and_updates_resume(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $disk = Mockery::mock();
        $disk->shouldReceive('put')
            ->once()
            ->andReturnTrue();
        $disk->shouldReceive('url')
            ->once()
            ->andReturn('https://cdn.example.com/uploads/rc/avatar/2026/06/03/avatar.jpg');

        Storage::shouldReceive('disk')
            ->with('oss')
            ->andReturn($disk);

        $response = $this
            ->actingAs($user, 'rc')
            ->patch('/api/resume/'.$resume->id.'/avatar/upload', [
                'file' => UploadedFile::fake()->image('avatar.jpg'),
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.id', $resume->id)
            ->assertJsonPath('data.display_avatar', 'https://cdn.example.com/uploads/rc/avatar/2026/06/03/avatar.jpg');

        $avatar = $response->json('data.avatar');

        $this->assertIsString($avatar);
        $this->assertStringStartsWith('uploads/rc/avatar/', $avatar);
        $this->assertDatabaseHas('rc_resumes', [
            'id' => $resume->id,
            'avatar' => $avatar,
        ]);
    }

    public function test_upload_primary_resume_avatar_fills_empty_user_avatar(): void
    {
        $user = User::factory()->create([
            'gender' => UserGender::Unknown,
        ]);
        $resume = $this->createResume($user, [
            'is_primary' => 1,
        ]);

        $disk = Mockery::mock();
        $disk->shouldReceive('put')
            ->once()
            ->andReturnTrue();
        $disk->shouldReceive('url')
            ->once()
            ->andReturn('https://cdn.example.com/uploads/rc/avatar/2026/06/03/avatar.jpg');

        Storage::shouldReceive('disk')
            ->with('oss')
            ->andReturn($disk);

        $response = $this
            ->actingAs($user, 'rc')
            ->patch('/api/resume/'.$resume->id.'/avatar/upload', [
                'file' => UploadedFile::fake()->image('avatar.jpg'),
            ]);

        $response->assertOk();

        $user->refresh();

        $this->assertSame($response->json('data.avatar'), $user->avatar);
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
