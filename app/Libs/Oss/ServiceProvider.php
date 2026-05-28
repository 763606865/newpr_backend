<?php

namespace App\Libs\Oss;

use App\Libs\Oss\Adapter\OssAdapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use League\Flysystem\Filesystem;
use OSS\OssClient;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void {}

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Storage::extend('oss', static function ($app, $config) {
            $endpoint = $config['endpoint'];
            // 移除协议前缀（如果有）
            $endpoint = preg_replace('|^https?://|', '', $endpoint);

            try {
                // The configured endpoint is the regional OSS domain, not a custom CNAME.
                set_error_handler(static fn (int $severity, string $message, string $file): bool => $severity === E_DEPRECATED && str_contains($file, 'aliyuncs/oss-sdk-php'));

                try {
                    $client = new OssClient($config['access_key_id'], $config['access_key_secret'], $endpoint, false);
                } finally {
                    restore_error_handler();
                }

                $adapter = new OssAdapter($client, $config['bucket']);
                $driver = new Filesystem($adapter);

                return new FilesystemAdapter($driver, $adapter, $config);
            } catch (\Exception $e) {
                throw new \RuntimeException('OSS 客户端初始化失败: '.$e->getMessage());
            }
        });
    }
}
