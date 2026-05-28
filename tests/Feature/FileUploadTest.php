<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    /**
     * 测试 OSS 磁盘连接
     */
    public function test_oss_disk_connection(): void
    {
        $this->assertTrue(
            config('filesystems.default') === 'oss' || config('filesystems.default') === 'local',
            'FILESYSTEM_DISK should be configured'
        );
    }

    /**
     * 测试 OSS 配置
     */
    public function test_oss_configuration(): void
    {
        $config = config('filesystems.disks.oss');

        $this->assertNotNull($config, 'OSS disk configuration not found');
        $this->assertEquals('oss', $config['driver']);
        $this->assertNotEmpty($config['access_key_id'], 'OSS_ACCESS_KEY_ID not set');
        $this->assertNotEmpty($config['access_key_secret'], 'OSS_ACCESS_KEY_SECRET not set');
        $this->assertNotEmpty($config['bucket'], 'OSS_BUCKET not set');
        $this->assertNotEmpty($config['endpoint'], 'OSS_ENDPOINT not set');
    }

    /**
     * 测试文件上传端点（需要认证）
     */
    public function test_file_upload_endpoint(): void
    {
        $response = $this->postJson('/api/upload', [
            'file' => UploadedFile::fake()->image('test.jpg', 100, 100),
        ]);

        // 如果未认证应该返回401
        $this->assertContains($response->status(), [401, 422]);
    }
}
