<?php

namespace Tests\Feature\Rc;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarUploadControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_avatar_upload_stores_image_and_returns_url(): void
    {
        Storage::fake('oss');

        $response = $this->withHeader('Accept', 'application/json')
            ->post('/rc/upload/avatar', [
                'file' => UploadedFile::fake()->image('avatar.jpg'),
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonStructure(['data' => ['url']]);

        $files = Storage::disk('oss')->allFiles('uploads/rc/avatar');
        $this->assertCount(1, $files);
    }

    public function test_avatar_upload_rejects_non_image_file(): void
    {
        Storage::fake('oss');

        $this->withHeader('Accept', 'application/json')
            ->post('/rc/upload/avatar', [
                'file' => UploadedFile::fake()->create('avatar.pdf', 10, 'application/pdf'),
            ])
            ->assertUnprocessable();
    }
}
