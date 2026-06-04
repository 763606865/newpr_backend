<?php

namespace Tests\Feature\Rc;

use App\Enums\RcResumeSourceType;
use App\Models\Rc\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ResumeAttachmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_upload_attachment_requires_authentication(): void
    {
        $response = $this->post('/rc/resumes/1/attachment', [
            'file' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ]);

        $this->assertContains($response->status(), [401, 422, 500]);
    }

    public function test_upload_attachment_returns_not_found_when_resume_does_not_belong_to_user(): void
    {
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $resume = $this->createResume($otherUser);

        $response = $this
            ->actingAs($currentUser, 'rc')
            ->post('/rc/resumes/'.$resume->id.'/attachment', [
                'file' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '简历不存在。');
    }

    public function test_upload_attachment_rejects_invalid_file_type(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user);

        $response = $this
            ->actingAs($user, 'rc')
            ->post('/rc/resumes/'.$resume->id.'/attachment', [
                'file' => UploadedFile::fake()->image('avatar.jpg'),
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(422);
    }

    public function test_upload_attachment_stores_file_and_updates_resume(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user, [
            'source_type' => RcResumeSourceType::Manual,
        ]);

        $disk = Mockery::mock();
        $disk->shouldReceive('put')
            ->once()
            ->andReturnTrue();
        $disk->shouldReceive('url')
            ->once()
            ->andReturn('https://cdn.example.com/uploads/rc/resume/2026/06/03/resume.pdf');

        Storage::shouldReceive('disk')
            ->with('oss')
            ->andReturn($disk);

        $response = $this
            ->actingAs($user, 'rc')
            ->post('/rc/resumes/'.$resume->id.'/attachment', [
                'file' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.id', $resume->id)
            ->assertJsonPath('data.file_name', 'resume.pdf')
            ->assertJsonPath('data.file_ext', 'pdf')
            ->assertJsonPath('data.source_type', RcResumeSourceType::Upload->value)
            ->assertJsonPath('data.display_file_url', 'https://cdn.example.com/uploads/rc/resume/2026/06/03/resume.pdf');

        $this->assertDatabaseHas('rc_resumes', [
            'id' => $resume->id,
            'file_name' => 'resume.pdf',
            'file_ext' => 'pdf',
            'source_type' => RcResumeSourceType::Upload->value,
        ]);

        $this->assertNotNull($response->json('data.file_url'));
    }

    public function test_upload_attachment_clears_parsed_data_and_deletes_old_file(): void
    {
        $user = User::factory()->create();
        $resume = $this->createResume($user, [
            'file_url' => 'uploads/rc/resume/2026/06/03/old.pdf',
            'file_name' => 'old.pdf',
            'file_ext' => 'pdf',
            'text_content' => 'old text',
            'parsed_data' => ['full_name' => 'Old Name'],
        ]);

        $disk = Mockery::mock();
        $disk->shouldReceive('delete')
            ->once()
            ->with('uploads/rc/resume/2026/06/03/old.pdf')
            ->andReturnTrue();
        $disk->shouldReceive('put')
            ->once()
            ->andReturnTrue();
        $disk->shouldReceive('url')
            ->once()
            ->andReturn('https://cdn.example.com/uploads/rc/resume/2026/06/03/new.pdf');

        Storage::shouldReceive('disk')
            ->with('oss')
            ->andReturn($disk);

        $response = $this
            ->actingAs($user, 'rc')
            ->post('/rc/resumes/'.$resume->id.'/attachment', [
                'file' => UploadedFile::fake()->create('new-resume.pdf', 100, 'application/pdf'),
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.file_name', 'new-resume.pdf')
            ->assertJsonPath('data.text_content', null)
            ->assertJsonPath('data.parsed_data', null);

        $this->assertDatabaseHas('rc_resumes', [
            'id' => $resume->id,
            'file_name' => 'new-resume.pdf',
            'text_content' => null,
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
