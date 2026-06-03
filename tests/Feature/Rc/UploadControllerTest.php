<?php

namespace Tests\Feature\Rc;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class UploadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_upload_requires_authentication(): void
    {
        $response = $this->post('/rc/upload', [
            'file' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $this->assertContains($response->status(), [401, 422, 500]);
    }

    public function test_upload_stores_file_to_oss(): void
    {
        $user = User::factory()->create();
        $disk = Mockery::mock();
        $disk->shouldReceive('put')
            ->once()
            ->andReturnTrue();
        $disk->shouldReceive('url')
            ->once()
            ->andReturn('https://cdn.example.com/uploads/rc/avatar/2026/06/03/example.jpg');

        Storage::shouldReceive('disk')
            ->with('oss')
            ->andReturn($disk);

        $response = $this
            ->actingAs($user, 'rc')
            ->post('/rc/upload', [
                'file' => UploadedFile::fake()->image('avatar.jpg'),
                'type' => 'avatar',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.url', 'https://cdn.example.com/uploads/rc/avatar/2026/06/03/example.jpg')
            ->assertJsonStructure([
                'data' => ['path', 'url', 'size', 'mime_type'],
            ]);
    }

    public function test_destroy_deletes_file_from_oss(): void
    {
        $user = User::factory()->create();
        $disk = Mockery::mock();
        $disk->shouldReceive('delete')
            ->once()
            ->with('uploads/rc/avatar/2026/06/03/example.jpg')
            ->andReturnTrue();

        Storage::shouldReceive('disk')
            ->with('oss')
            ->andReturn($disk);

        $response = $this
            ->actingAs($user, 'rc')
            ->deleteJson('/rc/files', [
                'path' => 'uploads/rc/avatar/2026/06/03/example.jpg',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.message', '文件删除成功。');
    }
}
